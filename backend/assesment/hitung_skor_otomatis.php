<?php
session_start();

require_once __DIR__ . "/../../config/database.php";

// Pastikan pengguna sudah login
if (!isset($_SESSION["ID_User"])) {
    header("Location: ../login_form.php");
    exit;
}

$id_user = (int) $_SESSION["ID_User"];

// Ambil assessment aktif milik pengguna
$sql = "SELECT ID_Assessment
        FROM T_Assessment
        WHERE ID_User = :id_user
          AND Status = 'In Progress'
        ORDER BY ID_Assessment DESC
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute([
    ":id_user" => $id_user
]);

$assessment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$assessment) {
    exit("Assessment aktif tidak ditemukan. Silakan mulai assessment terlebih dahulu.");
}

$id_assessment = (int) $assessment["ID_Assessment"];

// Hitung skor berdasarkan jawaban yang tersimpan
$sql = "SELECT
            osw.ID_Skill,
            SUM(osw.Weight) AS Total_Score
        FROM T_Assessment_Answer a
        JOIN T_Option_Skill_Weight osw
            ON a.ID_Option = osw.ID_Option
        WHERE a.ID_Assessment = :id_assessment
        GROUP BY osw.ID_Skill";

$stmt = $conn->prepare($sql);
$stmt->execute([
    ":id_assessment" => $id_assessment
]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$skor_skill = [];

foreach ($rows as $row) {
    $skor_skill[] = [
        "ID_Skill" => (int) $row["ID_Skill"],
        "Score" => (float) $row["Total_Score"]
    ];
}

// Pastikan terdapat jawaban yang bisa dihitung
if (empty($skor_skill)) {
    exit("Belum ada jawaban yang bisa dihitung. Silakan jawab pertanyaan assessment terlebih dahulu.");
}

try {
    // Mulai transaksi PDO
    $conn->beginTransaction();

    // Hapus skor lama untuk assessment ini
    $hapus = $conn->prepare(
        "DELETE FROM T_Hasil_Assessment
         WHERE ID_Assessment = :id_assessment"
    );

    $hapus->execute([
        ":id_assessment" => $id_assessment
    ]);

    // Simpan skor hasil perhitungan terbaru
    $insert = $conn->prepare(
        "INSERT INTO T_Hasil_Assessment
         (ID_Assessment, ID_Skill, Score)
         VALUES (:id_assessment, :id_skill, :score)"
    );

    foreach ($skor_skill as $item) {
        $insert->execute([
            ":id_assessment" => $id_assessment,
            ":id_skill" => $item["ID_Skill"],
            ":score" => $item["Score"]
        ]);
    }

    // Ambil nama skill untuk ditampilkan
    $query_skill = $conn->prepare(
        "SELECT Nama_Skill
         FROM T_Skill
         WHERE ID_Skill = :id_skill"
    );

    $hasil_tampilan = [];

    foreach ($skor_skill as $item) {
        $query_skill->execute([
            ":id_skill" => $item["ID_Skill"]
        ]);

        $skill = $query_skill->fetch(PDO::FETCH_ASSOC);

        $hasil_tampilan[] = [
            "Nama_Skill" => $skill["Nama_Skill"] ?? "Skill tidak ditemukan",
            "Score" => $item["Score"]
        ];
    }

    // Semua proses berhasil, simpan perubahan
    $conn->commit();

} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log("Perhitungan skor Skillora gagal: " . $e->getMessage());
    http_response_code(500);
    exit("Perhitungan skor gagal. Silakan periksa struktur tabel dan konfigurasi database.");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Perhitungan Skillora</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px 16px;
            font-family: Arial, sans-serif;
            color: #263b36;
            background: #f3f8f7;
        }

        .container {
            max-width: 760px;
            margin: 35px auto;
            padding: 30px;
            background: white;
            border-radius: 18px;
            box-shadow: 0 8px 28px rgba(20, 70, 55, 0.08);
        }

        .success {
            display: inline-block;
            padding: 8px 13px;
            color: #176b5b;
            background: #e5f4ef;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }

        h1 {
            color: #176b5b;
            margin-bottom: 8px;
        }

        .description {
            color: #65756f;
            margin-bottom: 25px;
        }

        .score {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 17px;
            margin: 12px 0;
            border: 1px solid #e4eeea;
            border-radius: 12px;
        }

        .score-name {
            font-weight: bold;
        }

        .score-value {
            color: #176b5b;
            font-weight: bold;
        }

        .button {
            display: inline-block;
            margin-top: 22px;
            padding: 12px 18px;
            color: white;
            background: #176b5b;
            border-radius: 9px;
            text-decoration: none;
        }

        .button:hover {
            background: #105447;
        }

        @media (max-width: 480px) {
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
        <span class="success">✓ Perhitungan berhasil</span>

        <h1>Hasil Skor Assessment</h1>

        <p class="description">
            Berikut adalah pemetaan skor keterampilan berdasarkan
            jawaban assessment kamu.
        </p>

        <?php foreach ($hasil_tampilan as $item): ?>
            <div class="score">
                <span class="score-name">
                    <?= htmlspecialchars(
                        $item["Nama_Skill"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </span>

                <span class="score-value">
                    <?= htmlspecialchars(
                        (string) $item["Score"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </span>
            </div>
        <?php endforeach; ?>

        <a class="button" href="../../index.php">
            ← Kembali ke Dashboard
        </a>
    </main>
</body>
</html>
