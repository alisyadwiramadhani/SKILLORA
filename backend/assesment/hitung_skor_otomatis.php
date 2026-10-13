<?php

session_start();

require_once "../../config/database.php";

// Pastikan user sudah login
if (!isset($_SESSION["ID_User"])) {
    die("Silakan login terlebih dahulu.");
}

$id_user = (int) $_SESSION["ID_User"];

// Ambil assessment aktif milik user
$sql = "SELECT ID_Assessment
        FROM T_Assessment
        WHERE ID_User = ?
          AND Status = 'In Progress'
        ORDER BY ID_Assessment DESC
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_user);
$stmt->execute();

$assessment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$assessment) {
    die("Assessment aktif tidak ditemukan.");
}

$id_assessment = (int) $assessment["ID_Assessment"];

// Hitung skor berdasarkan jawaban yang tersimpan
$sql = "SELECT
            osw.ID_Skill,
            SUM(osw.Weight) AS Total_Score
        FROM T_Assessment_Answer a
        JOIN T_Option_Skill_Weight osw
            ON a.ID_Option = osw.ID_Option
        WHERE a.ID_Assessment = ?
        GROUP BY osw.ID_Skill";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_assessment);
$stmt->execute();

$result = $stmt->get_result();

$skor_skill = [];

while ($row = $result->fetch_assoc()) {
    $skor_skill[] = [
        "ID_Skill" => (int) $row["ID_Skill"],
        "Score" => (float) $row["Total_Score"]
    ];
}

$stmt->close();

// Pastikan ada jawaban sebelum menghitung
if (empty($skor_skill)) {
    die("Belum ada jawaban yang bisa dihitung.");
}

// Simpan ulang skor secara konsisten
$conn->begin_transaction();

try {

    // Hapus skor lama untuk assessment ini
    $hapus = $conn->prepare(
        "DELETE FROM T_Hasil_Assessment
         WHERE ID_Assessment = ?"
    );

    $hapus->bind_param("i", $id_assessment);
    $hapus->execute();
    $hapus->close();

    // Simpan skor hasil perhitungan terbaru
    $insert = $conn->prepare(
        "INSERT INTO T_Hasil_Assessment
         (ID_Assessment, ID_Skill, Score)
         VALUES (?, ?, ?)"
    );

    foreach ($skor_skill as $item) {

        $id_skill = $item["ID_Skill"];
        $score = $item["Score"];

        $insert->bind_param(
            "iid",
            $id_assessment,
            $id_skill,
            $score
        );

        $insert->execute();
    }

    $insert->close();

    $conn->commit();

    echo "<h2>Perhitungan Skor Berhasil!</h2>";
    echo "<p>Berikut hasil skor skill kamu:</p>";

    foreach ($skor_skill as $item) {

        $id_skill = $item["ID_Skill"];
        $score = $item["Score"];

        $query_skill = $conn->prepare(
            "SELECT Nama_Skill
             FROM T_Skill
             WHERE ID_Skill = ?"
        );

        $query_skill->bind_param("i", $id_skill);
        $query_skill->execute();

        $skill = $query_skill->get_result()->fetch_assoc();

        echo htmlspecialchars($skill["Nama_Skill"]);
        echo ": " . $score . "<br>";

        $query_skill->close();
    }

} catch (Throwable $e) {

    $conn->rollback();

    echo "Perhitungan skor gagal. Periksa konfigurasi database.";

}

$conn->close();
?>