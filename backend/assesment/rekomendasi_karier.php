<?php

session_start();

require_once "../../config/database.php";

if (!isset($_SESSION["ID_User"])) {
    die("Silakan login terlebih dahulu.");
}

$id_user = (int) $_SESSION["ID_User"];

// Ambil assessment terbaru yang sudah memiliki skor
$sql = "SELECT a.ID_Assessment
        FROM T_Assessment a
        JOIN T_Hasil_Assessment h
            ON a.ID_Assessment = h.ID_Assessment
        WHERE a.ID_User = ?
          AND a.Status = 'In Progress'
        GROUP BY a.ID_Assessment
        ORDER BY a.ID_Assessment DESC
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_user);
$stmt->execute();

$assessment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$assessment) {
    die("Assessment dengan hasil skor tidak ditemukan.");
}

$id_assessment = (int) $assessment["ID_Assessment"];

// Ambil total skor skill pengguna
$sql = "SELECT SUM(Score) AS Total_Score
        FROM T_Hasil_Assessment
        WHERE ID_Assessment = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_assessment);
$stmt->execute();

$total = $stmt->get_result()->fetch_assoc();
$stmt->close();

$total_skor = (float) $total["Total_Score"];

if ($total_skor <= 0) {
    die("Total skor belum valid.");
}

// Ambil skor maksimum yang mungkin untuk setiap skill
$sql = "SELECT
            q.ID_Assessment,
            mx.ID_Skill,
            SUM(mx.Max_Weight) AS Max_Score
        FROM T_Assessment_Question q
        JOIN (
            SELECT
                o.ID_Assessment_Question,
                osw.ID_Skill,
                MAX(osw.Weight) AS Max_Weight
            FROM T_Question_Option o
            JOIN T_Option_Skill_Weight osw
                ON o.ID_Option = osw.ID_Option
            GROUP BY
                o.ID_Assessment_Question,
                osw.ID_Skill
        ) mx
            ON q.ID_Assessment_Question =
               mx.ID_Assessment_Question
        WHERE q.ID_Assessment = ?
        GROUP BY q.ID_Assessment, mx.ID_Skill";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_assessment);
$stmt->execute();

$result = $stmt->get_result();
$skor_maksimum = [];

while ($row = $result->fetch_assoc()) {
    $skor_maksimum[(int) $row["ID_Skill"]] =
        (float) $row["Max_Score"];
}

$stmt->close();

// Ambil skor aktual dan kebutuhan skill setiap karier
$sql = "SELECT
            k.ID_Karir,
            k.Nama_Karier,
            k.Bidang,
            k.Deskripsi,
            SUM(
                COALESCE(h.Score, 0) * ks.Tingkat_Kebutuhan
            ) AS Skor_Cocok
        FROM T_Karir k
        JOIN T_Karir_Skill ks
            ON k.ID_Karir = ks.ID_Karir
        LEFT JOIN T_Hasil_Assessment h
            ON h.ID_Skill = ks.ID_Skill
           AND h.ID_Assessment = ?
        GROUP BY
            k.ID_Karir,
            k.Nama_Karier,
            k.Bidang,
            k.Deskripsi
        ORDER BY Skor_Cocok DESC, k.Nama_Karier ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_assessment);
$stmt->execute();

$result = $stmt->get_result();
$karier = [];

while ($row = $result->fetch_assoc()) {

    $skor_cocok = (float) $row["Skor_Cocok"];
    $skor_maks = 0;

    // Hitung skor maksimum sesuai kebutuhan skill karier
    $query_maks = $conn->prepare(
        "SELECT ID_Skill, Tingkat_Kebutuhan
         FROM T_Karir_Skill
         WHERE ID_Karir = ?"
    );

    $id_karir = (int) $row["ID_Karir"];
    $query_maks->bind_param("i", $id_karir);
    $query_maks->execute();

    $hasil_maks = $query_maks->get_result();

    while ($skill = $hasil_maks->fetch_assoc()) {

        $id_skill = (int) $skill["ID_Skill"];
        $tingkat = (float) $skill["Tingkat_Kebutuhan"];

        $max_skill = $skor_maksimum[$id_skill] ?? 0;

        $skor_maks += $max_skill * $tingkat;
    }

    $query_maks->close();

    $persentase = $skor_maks > 0
        ? ($skor_cocok / $skor_maks) * 100
        : 0;

    $row["Match_Score"] = round(
        max(0, min(100, $persentase)),
        2
    );

    $karier[] = $row;
}

$stmt->close();

if (empty($karier)) {
    die("Data karier atau pemetaan skill belum tersedia.");
}

// Urutkan berdasarkan persentase kecocokan yang baru
usort($karier, function ($a, $b) {
    return $b["Match_Score"] <=> $a["Match_Score"];
});

// Simpan seluruh rekomendasi dalam satu transaksi
$conn->begin_transaction();

try {

    // Hapus rekomendasi lama agar tidak menumpuk
    $hapus = $conn->prepare(
        "DELETE FROM T_Rekomendasi_Karier
         WHERE ID_Assessment = ?"
    );

    $hapus->bind_param("i", $id_assessment);
    $hapus->execute();
    $hapus->close();

    $insert = $conn->prepare(
    "INSERT INTO T_Rekomendasi_Karier
     (ID_Assessment, ID_Karir, Match_Score,
      Ranking, Tanggal_Rekomendasi)
     VALUES (?, ?, ?, ?, CURDATE())"
    );

    $ranking = 1;

    foreach ($karier as $item) {

        $id_karir = (int) $item["ID_Karir"];
        $match_score = (float) $item["Match_Score"];

        $insert->bind_param(
            "iidi",
            $id_assessment,
            $id_karir,
            $match_score,
            $ranking
        );

        $insert->execute();

        $ranking++;
    }

    $insert->close();
    $conn->commit();


} catch (Throwable $e) {

    $conn->rollback();

    echo "<h2>Detail Error untuk Debugging</h2>";
    echo "<pre>" .
         htmlspecialchars($e->getMessage()) .
         "</pre>";

    $conn->close();
    exit;

}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekomendasi Karier Skillora</title>
</head>
<body>

    <h1>Rekomendasi Karier Skillora</h1>

    <p>
        Berikut karier yang paling sesuai berdasarkan
        skor skill dari assessment kamu.
    </p>

    <?php foreach ($karier as $item): ?>

        <div style="border: 1px solid #ddd; padding: 15px; margin-bottom: 12px; border-radius: 8px;">

            <h2>
                <?= (int) array_search($item, $karier) + 1 ?>.
                <?= htmlspecialchars($item["Nama_Karier"]) ?>
            </h2>

            <p>
                Bidang:
                <?= htmlspecialchars($item["Bidang"]) ?>
            </p>

            <p>
                <?= htmlspecialchars($item["Deskripsi"]) ?>
            </p>

            <p>
                Kecocokan:
                <strong><?= number_format($item["Match_Score"], 2) ?>%</strong>
            </p>

        </div>

    <?php endforeach; ?>

</body>
</html>