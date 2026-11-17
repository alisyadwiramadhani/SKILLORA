
<?php
session_start();
require_once __DIR__ . "/../config/database.php";

if (empty($_SESSION["ID_User"])) {
    header("Location: login_form.php");
    exit;
}

$id_user = (int) $_SESSION["ID_User"];

$stmt = $conn->prepare("
    SELECT ID_Assessment, Tanggal
    FROM T_Assessment
    WHERE ID_User = :id_user
      AND Status = 'Completed'
    ORDER BY ID_Assessment DESC
    LIMIT 1
");

$stmt->execute([":id_user" => $id_user]);
$assessment = $stmt->fetch(PDO::FETCH_ASSOC);

$hasil = [];

if ($assessment) {
    $stmt = $conn->prepare("
        SELECT
            s.Nama_Skill,
            s.Kategori,
            s.Deskripsi,
            h.Score
        FROM T_Hasil_Assessment h
        INNER JOIN T_Skill s
            ON s.ID_Skill = h.ID_Skill
        WHERE h.ID_Assessment = :id_assessment
        ORDER BY h.Score DESC
    ");

    $stmt->execute([
        ":id_assessment" => $assessment["ID_Assessment"]
    ]);

    $hasil = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$maxScore = 1;

foreach ($hasil as $item) {
    $maxScore = max(
        $maxScore,
        (float) $item["Score"]
    );
}

function e($value) {
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
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Hasil Assessment | Skillora</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 25px;
    background: #f5faf8;
    color: #30443f;
    font-family: "Segoe UI", Arial, sans-serif;
}

.container {
    width: min(100%, 900px);
    margin: 25px auto;
}

.header {
    padding: 30px;
    background: linear-gradient(120deg, #176e69, #269b85);
    color: white;
    border-radius: 20px;
}

.header h1 {
    margin: 7px 0;
}

.header p {
    margin: 0;
    color: #e3f5ef;
    font-size: 13px;
}

.panel {
    margin-top: 20px;
    padding: 27px;
    background: white;
    border: 1px solid #e4eeea;
    border-radius: 18px;
    box-shadow: 0 5px 20px rgba(31,87,73,.04);
}

.skill {
    margin: 22px 0;
}

.skill-head {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    font-size: 13px;
    font-weight: 700;
}

.score {
    color: #208b83;
}

.track {
    height: 10px;
    margin-top: 9px;
    background: #eaf2ee;
    border-radius: 20px;
    overflow: hidden;
}

.fill {
    height: 100%;
    background: linear-gradient(90deg,#4cb79a,#208b83);
    border-radius: 20px;
}

.description {
    color: #84958e;
    font-size: 12px;
    line-height: 1.7;
}

.btn {
    display: inline-block;
    margin-top: 15px;
    padding: 12px 17px;
    background: #208b83;
    color: white;
    text-decoration: none;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 700;
}

.btn.secondary {
    background: #e8f5ef;
    color: #176e69;
}

.empty {
    padding: 30px;
    background: #f5faf8;
    border-radius: 12px;
    text-align: center;
    color: #84958e;
}

@media(max-width:600px) {
    body {
        padding: 14px;
    }

    .header,
    .panel {
        padding: 21px;
    }
}
</style>
</head>

<body>

<main class="container">

    <header class="header">
        <small>SKILLORA ASSESSMENT</small>
        <h1>Hasil Pemetaan Keterampilan</h1>
        <p>
            Lihat gambaran keterampilan berdasarkan
            jawaban assessment yang sudah diselesaikan.
        </p>
    </header>

    <section class="panel">

        <h2>Ringkasan Keterampilan</h2>

        <?php if (empty($hasil)): ?>

            <div class="empty">
                <h3>Belum ada hasil assessment</h3>
                <p>
                    Silakan selesaikan assessment terlebih dahulu.
                </p>

                <a href="assesment/start.php" class="btn">
                    Mulai Assessment
                </a>
            </div>

        <?php else: ?>

            <p class="description">
                Tanggal assessment:
                <?= e($assessment["Tanggal"]) ?>
            </p>

            <?php foreach ($hasil as $item): ?>

                <?php
                $score = (float) $item["Score"];
                $width = ($score / $maxScore) * 100;
                ?>

                <div class="skill">

                    <div class="skill-head">
                        <span>
                            <?= e($item["Nama_Skill"]) ?>
                        </span>

                        <span class="score">
                            <?= number_format($score, 1) ?> poin
                        </span>
                    </div>

                    <div class="track">
                        <div
                            class="fill"
                            style="width:<?= $width ?>%">
                        </div>
                    </div>

                    <p class="description">
                        <?= e($item["Deskripsi"] ?? "") ?>
                    </p>

                </div>

            <?php endforeach; ?>

            <a href="assesment/rekomendasi_karier.php"
               class="btn">
                Lihat Rekomendasi Karier →
            </a>

        <?php endif; ?>

        <a href="../index.php" class="btn secondary">
            Kembali ke Dashboard
        </a>

    </section>

</main>

</body>
</html>
