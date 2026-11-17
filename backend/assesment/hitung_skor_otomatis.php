
<?php
session_start();

require_once __DIR__ . "/../../config/database.php";

// 1. Validasi login
if (!isset($_SESSION["ID_User"])) {
    header("Location: ../login_form.php");
    exit;
}

$id_user = (int) $_SESSION["ID_User"];

// 2. Ambil ID assessment dari session
$id_assessment = isset($_SESSION["ID_Assessment"])
    ? (int) $_SESSION["ID_Assessment"]
    : 0;

try {

    // 3. Jika session assessment tidak tersedia,
    // cari assessment terakhir milik pengguna.
    if ($id_assessment <= 0) {

        $stmt = $conn->prepare("
            SELECT ID_Assessment
            FROM T_Assessment
            WHERE ID_User = :id_user
            ORDER BY ID_Assessment DESC
            LIMIT 1
        ");

        $stmt->execute([
            ":id_user" => $id_user
        ]);

        $id_assessment = (int) $stmt->fetchColumn();
    }

    if ($id_assessment <= 0) {
        throw new Exception(
            "Assessment tidak ditemukan. Silakan mulai assessment terlebih dahulu."
        );
    }

    // 4. Pastikan assessment milik pengguna
    $stmt = $conn->prepare("
        SELECT ID_Assessment, Status
        FROM T_Assessment
        WHERE ID_Assessment = :id_assessment
          AND ID_User = :id_user
        LIMIT 1
    ");

    $stmt->execute([
        ":id_assessment" => $id_assessment,
        ":id_user" => $id_user
    ]);

    $assessment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$assessment) {
        throw new Exception(
            "Assessment tidak ditemukan atau bukan milik akun kamu."
        );
    }

    $_SESSION["ID_Assessment"] = $id_assessment;

    // 5. Hitung jumlah pertanyaan
    $stmt = $conn->prepare("
        SELECT COUNT(*)
        FROM T_Assessment_Question
        WHERE ID_Assessment = :id_assessment
    ");

    $stmt->execute([
        ":id_assessment" => $id_assessment
    ]);

    $total_pertanyaan = (int) $stmt->fetchColumn();

    // 6. Hitung jawaban valid
    // Jawaban harus sesuai dengan pertanyaan
    // dan pilihan yang tersedia pada assessment tersebut.
    $stmt = $conn->prepare("
        SELECT
            COUNT(DISTINCT q.ID_Assessment_Question)
                AS total_terjawab
        FROM T_Assessment_Answer a

        INNER JOIN T_Assessment_Question q
            ON q.ID_Assessment_Question =
               a.ID_Assessment_Question

           AND q.ID_Assessment =
               a.ID_Assessment

        INNER JOIN T_Question_Option o
            ON o.ID_Option = a.ID_Option

           AND o.ID_Assessment_Question =
               q.ID_Assessment_Question

        WHERE a.ID_Assessment = :id_assessment
    ");

    $stmt->execute([
        ":id_assessment" => $id_assessment
    ]);

    $total_terjawab = (int) $stmt->fetchColumn();

    // 7. Validasi delapan pertanyaan dan jawaban
    if (
        $total_pertanyaan !== 8 ||
        $total_terjawab !== 8
    ) {

        $pesan = "Assessment belum lengkap.";

        if ($total_pertanyaan !== 8) {
            $pesan = "Assessment hanya memiliki "
                . $total_pertanyaan
                . " pertanyaan. Sistem membutuhkan tepat 8 pertanyaan.";
        } elseif ($total_terjawab !== 8) {
            $pesan = "Kamu baru menjawab "
                . $total_terjawab
                . " dari 8 pertanyaan.";
        }

        throw new Exception($pesan);
    }

    // 8. Hitung skor berdasarkan bobot jawaban
    $stmt = $conn->prepare("
        SELECT
            s.ID_Skill,
            s.Nama_Skill,
            s.Deskripsi,
            COALESCE(SUM(w.Weight), 0) AS Total_Score

        FROM T_Skill s

        LEFT JOIN T_Option_Skill_Weight w
            ON w.ID_Skill = s.ID_Skill

        LEFT JOIN T_Question_Option o
            ON o.ID_Option = w.ID_Option

        LEFT JOIN T_Assessment_Answer a
            ON a.ID_Option = o.ID_Option

           AND a.ID_Assessment = :id_assessment

        LEFT JOIN T_Assessment_Question q
            ON q.ID_Assessment_Question =
               a.ID_Assessment_Question

           AND q.ID_Assessment =
               a.ID_Assessment

        GROUP BY
            s.ID_Skill,
            s.Nama_Skill,
            s.Deskripsi

        ORDER BY Total_Score DESC
    ");

    $stmt->execute([
        ":id_assessment" => $id_assessment
    ]);

    $hasil = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($hasil)) {
        throw new Exception(
            "Data keterampilan belum tersedia."
        );
    }

    // 9. Simpan hasil perhitungan
    if ($assessment["Status"] !== "Completed") {

        $conn->beginTransaction();

        // Hapus hasil lama jika ada
        $hapus = $conn->prepare("
            DELETE FROM T_Hasil_Assessment
            WHERE ID_Assessment = :id_assessment
        ");

        $hapus->execute([
            ":id_assessment" => $id_assessment
        ]);

        // Simpan hasil terbaru
        $insert = $conn->prepare("
            INSERT INTO T_Hasil_Assessment
            (
                ID_Assessment,
                ID_Skill,
                Score
            )
            VALUES
            (
                :id_assessment,
                :id_skill,
                :score
            )
        ");

        foreach ($hasil as $item) {

            $insert->execute([
                ":id_assessment" => $id_assessment,
                ":id_skill" => (int) $item["ID_Skill"],
                ":score" => (float) $item["Total_Score"]
            ]);
        }

        // 10. Ubah status assessment menjadi selesai
        $update = $conn->prepare("
            UPDATE T_Assessment
            SET Status = 'Completed'
            WHERE ID_Assessment = :id_assessment
              AND ID_User = :id_user
              AND Status = 'In Progress'
        ");

        $update->execute([
            ":id_assessment" => $id_assessment,
            ":id_user" => $id_user
        ]);

        $conn->commit();
    }

} catch (Throwable $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log(
        "Skillora hitung skor: " . $e->getMessage()
    );

    $error_message = $e->getMessage();
}

function h($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Hasil Assessment | Skillora</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 30px 16px;
    font-family: Arial, sans-serif;
    background: #f3f8f7;
    color: #263b36;
}

.container {
    max-width: 800px;
    margin: 35px auto;
    padding: 32px;
    background: white;
    border-radius: 20px;
    box-shadow: 0 8px 28px rgba(20,70,55,.08);
}

.success {
    display: inline-block;
    padding: 9px 14px;
    color: #176b5b;
    background: #e5f4ef;
    border-radius: 20px;
    font-size: 13px;
    font-weight: bold;
}

h1 {
    color: #176b5b;
    margin-bottom: 10px;
}

.description {
    color: #65756f;
    line-height: 1.7;
    margin-bottom: 28px;
}

.score {
    margin: 18px 0;
}

.score-header {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    margin-bottom: 9px;
}

.score-name {
    font-weight: bold;
}

.score-value {
    color: #176b5b;
    font-weight: bold;
}

.progress {
    height: 11px;
    overflow: hidden;
    background: #e8f0ec;
    border-radius: 20px;
}

.progress-bar {
    height: 100%;
    background: linear-gradient(
        90deg,
        #176b5b,
        #58ad8c
    );
    border-radius: 20px;
}

.error {
    padding: 20px;
    background: #fff1ed;
    color: #a33e2f;
    border-radius: 12px;
    line-height: 1.7;
}

.button {
    display: inline-block;
    margin-top: 25px;
    padding: 13px 20px;
    color: white;
    background: #176b5b;
    border-radius: 10px;
    text-decoration: none;
    font-weight: bold;
}

.button:hover {
    background: #105447;
}

.secondary {
    background: #e8f0ec;
    color: #176b5b;
}

.note {
    margin-top: 25px;
    padding: 15px;
    background: #f0f7f5;
    color: #60766d;
    border-radius: 10px;
    font-size: 13px;
    line-height: 1.7;
}

@media(max-width: 600px) {

    .container {
        padding: 22px 17px;
    }

    h1 {
        font-size: 25px;
    }

}

</style>

</head>

<body>

<main class="container">

<?php if (isset($error_message)): ?>

    <h1>Assessment Belum Lengkap</h1>

    <div class="error">
        <?= h($error_message); ?>
    </div>

    <a class="button" href="kerjakan.php">
        Kembali ke Assessment
    </a>

    <a class="button secondary" href="../../index.php">
        Dashboard
    </a>

<?php else: ?>

    <span class="success">
        ✓ Perhitungan Berhasil
    </span>

    <h1>Hasil Skor Assessment</h1>

    <p class="description">
        Berikut adalah hasil pemetaan keterampilan
        berdasarkan delapan jawaban assessment yang
        telah kamu selesaikan.
    </p>

    <?php

    $max_score = 0;

    foreach ($hasil as $item) {
        if ((float) $item["Total_Score"] > $max_score) {
            $max_score = (float) $item["Total_Score"];
        }
    }

    $max_score = max(1, $max_score);

    ?>

    <?php foreach ($hasil as $item): ?>

        <?php
        $score = (float) $item["Total_Score"];

        $persentase = ($score / $max_score) * 100;
        ?>

        <div class="score">

            <div class="score-header">

                <span class="score-name">
                    <?= h($item["Nama_Skill"]); ?>
                </span>

                <span class="score-value">
                    <?= number_format($score, 1); ?> poin
                </span>

            </div>

            <div class="progress">

                <div
                    class="progress-bar"
                    style="width: <?= $persentase; ?>%"
                ></div>

            </div>

            <?php if (!empty($item["Deskripsi"])): ?>

                <p style="font-size:13px;color:#718079">
                    <?= h($item["Deskripsi"]); ?>
                </p>

            <?php endif; ?>

        </div>

    <?php endforeach; ?>

    <div class="note">
        Hasil ini merupakan pemetaan keterampilan
        berdasarkan jawaban yang diberikan.
        Gunakan hasilnya sebagai referensi untuk
        mengenali potensi dan mengembangkan kemampuan diri.
    </div>

    <a class="button"
       href="rekomendasi_karier.php">

        Lihat Rekomendasi Karier →

    </a>

    <a class="button secondary"
       href="../../index.php">

        Kembali ke Dashboard

    </a>

<?php endif; ?>

</main>

</body>
</html>
