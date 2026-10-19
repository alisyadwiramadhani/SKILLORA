<?php
session_start();

require_once __DIR__ . "/../../config/database.php";

// Pastikan user sudah login
if (!isset($_SESSION["ID_User"])) {
    header("Location: ../login_form.php");
    exit;
}

$id_user = (int) $_SESSION["ID_User"];

try {
    // Ambil assessment terbaru milik user yang sudah memiliki skor
    $sql = "SELECT a.ID_Assessment
            FROM T_Assessment a
            WHERE a.ID_User = :id_user
              AND EXISTS (
                  SELECT 1
                  FROM T_Hasil_Assessment h
                  WHERE h.ID_Assessment = a.ID_Assessment
              )
            ORDER BY a.ID_Assessment DESC
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->execute([":id_user" => $id_user]);
    $assessment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$assessment) {
        exit(
            "Hasil assessment belum tersedia. "
            . "Silakan selesaikan assessment dan hitung skor terlebih dahulu."
        );
    }

    $id_assessment = (int) $assessment["ID_Assessment"];

    // Ambil skor skill pengguna
    $sql = "SELECT ID_Skill, SUM(Score) AS Total_Score
            FROM T_Hasil_Assessment
            WHERE ID_Assessment = :id_assessment
            GROUP BY ID_Skill";

    $stmt = $conn->prepare($sql);
    $stmt->execute([":id_assessment" => $id_assessment]);

    $skor_skill = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $skor_skill[(int) $row["ID_Skill"]] =
            max(0, (float) $row["Total_Score"]);
    }

    if (empty($skor_skill)) {
        exit("Skor skill belum tersedia untuk assessment ini.");
    }

    // Ambil skor maksimum setiap skill berdasarkan pilihan
    // yang tersedia pada setiap pertanyaan assessment
    $sql = "SELECT
                q.ID_Assessment,
                mx.ID_Skill,
                SUM(mx.Max_Weight) AS Max_Score
            FROM T_Assessment_Question q
            INNER JOIN (
                SELECT
                    o.ID_Assessment_Question,
                    osw.ID_Skill,
                    MAX(osw.Weight) AS Max_Weight
                FROM T_Question_Option o
                INNER JOIN T_Option_Skill_Weight osw
                    ON o.ID_Option = osw.ID_Option
                GROUP BY
                    o.ID_Assessment_Question,
                    osw.ID_Skill
            ) mx
                ON q.ID_Assessment_Question = mx.ID_Assessment_Question
            WHERE q.ID_Assessment = :id_assessment
            GROUP BY q.ID_Assessment, mx.ID_Skill";

    $stmt = $conn->prepare($sql);
    $stmt->execute([":id_assessment" => $id_assessment]);

    $skor_maksimum = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $skor_maksimum[(int) $row["ID_Skill"]] =
            (float) $row["Max_Score"];
    }

    // Ambil kebutuhan skill untuk setiap karier
    $sql = "SELECT
                k.ID_Karir,
                k.Nama_Karier,
                k.Bidang,
                k.Deskripsi,
                ks.ID_Skill,
                ks.Tingkat_Kebutuhan
            FROM T_Karir k
            INNER JOIN T_Karir_Skill ks
                ON k.ID_Karir = ks.ID_Karir
            ORDER BY k.Nama_Karier ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $data_karier = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id_karir = (int) $row["ID_Karir"];

        if (!isset($data_karier[$id_karir])) {
            $data_karier[$id_karir] = [
                "ID_Karir" => $id_karir,
                "Nama_Karier" => $row["Nama_Karier"],
                "Bidang" => $row["Bidang"],
                "Deskripsi" => $row["Deskripsi"],
                "Skor_Cocok" => 0,
                "Skor_Maks" => 0
            ];
        }

        $id_skill = (int) $row["ID_Skill"];
        $tingkat = max(0, (float) $row["Tingkat_Kebutuhan"]);

        // Gunakan hanya skill yang memiliki skor assessment
        if (array_key_exists($id_skill, $skor_skill)) {
            $data_karier[$id_karir]["Skor_Cocok"] +=
                $skor_skill[$id_skill] * $tingkat;

            $data_karier[$id_karir]["Skor_Maks"] +=
                ($skor_maksimum[$id_skill] ?? 0) * $tingkat;
        }
    }

    // Hitung persentase kecocokan
    $karier = [];

    foreach ($data_karier as $item) {
        if ($item["Skor_Maks"] <= 0) {
            continue;
        }

        $persentase = (
            $item["Skor_Cocok"] / $item["Skor_Maks"]
        ) * 100;

        $item["Match_Score"] = round(
            max(0, min(100, $persentase)),
            2
        );

        $karier[] = $item;
    }

    if (empty($karier)) {
        exit(
            "Rekomendasi belum dapat dibuat. "
            . "Pastikan data T_Karir, T_Karir_Skill, "
            . "dan T_Option_Skill_Weight sudah tersedia."
        );
    }

    // Urutkan dari persentase kecocokan tertinggi
    usort($karier, function ($a, $b) {
        return ($b["Match_Score"] <=> $a["Match_Score"])
            ?: ($a["Nama_Karier"] <=> $b["Nama_Karier"]);
    });

    // Simpan rekomendasi terbaru untuk assessment ini
    $conn->beginTransaction();

    $hapus = $conn->prepare(
        "DELETE FROM T_Rekomendasi_Karier
         WHERE ID_Assessment = :id_assessment"
    );

    $hapus->execute([
        ":id_assessment" => $id_assessment
    ]);

    $insert = $conn->prepare(
        "INSERT INTO T_Rekomendasi_Karier
            (ID_Assessment, ID_Karir, Match_Score,
             Ranking, Tanggal_Rekomendasi)
         VALUES
            (:id_assessment, :id_karir, :match_score,
             :ranking, CURDATE())"
    );

    foreach ($karier as $index => $item) {
        $insert->execute([
            ":id_assessment" => $id_assessment,
            ":id_karir" => $item["ID_Karir"],
            ":match_score" => $item["Match_Score"],
            ":ranking" => $index + 1
        ]);
    }

    $conn->commit();

} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log("Rekomendasi karier Skillora gagal: " . $e->getMessage());

    http_response_code(500);
    exit(
        "Terjadi kesalahan saat membuat rekomendasi. "
        . "Periksa log error PHP untuk mengetahui penyebabnya."
    );
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Rekomendasi Karier | Skillora</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f3f7f6;
            color: #193b35;
            font-family: Arial, sans-serif;
        }

        .container {
            width: min(100% - 32px, 1050px);
            margin: 0 auto;
            padding: 38px 0;
        }

        .header {
            padding: 32px;
            margin-bottom: 28px;
            border-radius: 22px;
            background: linear-gradient(135deg, #145e50, #218b70);
            color: white;
        }

        .header .label {
            margin: 0 0 10px;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            opacity: 0.85;
        }

        .header h1 {
            margin: 0 0 12px;
            font-size: clamp(27px, 4vw, 38px);
        }

        .header p {
            max-width: 680px;
            margin: 0;
            line-height: 1.7;
            color: #e1f4ed;
        }

        .info {
            margin: 0 0 22px;
            color: #64766f;
            line-height: 1.7;
        }

        .career-card {
            padding: 25px;
            margin-bottom: 18px;
            border: 1px solid #e2ebe7;
            border-radius: 18px;
            background: white;
            box-shadow: 0 5px 18px rgba(22, 65, 53, 0.04);
        }

        .career-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
        }

        .rank {
            color: #168064;
            font-size: 13px;
            font-weight: bold;
        }

        .career-card h2 {
            margin: 8px 0;
            font-size: 22px;
        }

        .field {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 20px;
            background: #e8f6ef;
            color: #176b53;
            font-size: 13px;
            font-weight: bold;
        }

        .description {
            color: #60716b;
            line-height: 1.8;
        }

        .score {
            flex-shrink: 0;
            min-width: 100px;
            padding: 12px;
            border-radius: 14px;
            background: #eff8f3;
            text-align: center;
        }

        .score strong {
            display: block;
            color: #147d5e;
            font-size: 24px;
        }

        .score span {
            color: #65766f;
            font-size: 12px;
        }

        .progress {
            height: 9px;
            margin-top: 18px;
            overflow: hidden;
            border-radius: 20px;
            background: #e7efeb;
        }

        .progress-fill {
            height: 100%;
            border-radius: inherit;
            background: #219574;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 28px;
        }

        .button {
            display: inline-block;
            padding: 13px 19px;
            border-radius: 10px;
            background: #167b61;
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        .button.secondary {
            background: #e2eee9;
            color: #185b49;
        }

        .empty {
            padding: 25px;
            border-radius: 14px;
            background: white;
            color: #64766f;
            line-height: 1.7;
        }

        @media (max-width: 600px) {
            .container {
                padding-top: 20px;
            }

            .header {
                padding: 24px;
            }

            .career-card {
                padding: 20px;
            }

            .career-top {
                flex-direction: column;
            }

            .score {
                width: 100%;
            }
        }
    </style>
</head>

<body>
<main class="container">

    <header class="header">
        <p class="label">Skillora Career Match</p>
        <h1>Rekomendasi Karier Kamu</h1>
        <p>
            Temukan pilihan karier yang paling sesuai dengan hasil
            assessment dan kecocokan skill kamu.
        </p>
    </header>

    <p class="info">
        Berikut adalah urutan karier berdasarkan persentase kecocokan
        skill dari hasil assessment terbaru kamu. Persentase ini
        merupakan indikator kecocokan berdasarkan data skill yang tersedia,
        bukan jaminan keberhasilan dalam suatu karier.
    </p>

    <?php foreach ($karier as $index => $item): ?>
        <article class="career-card">
            <div class="career-top">
                <div>
                    <div class="rank">
                        REKOMENDASI #<?= $index + 1 ?>
                    </div>

                    <h2>
                        <?= htmlspecialchars(
                            $item["Nama_Karier"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </h2>

                    <span class="field">
                        <?= htmlspecialchars(
                            $item["Bidang"] ?? "Bidang belum ditentukan",
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </span>
                </div>

                <div class="score">
                    <strong>
                        <?= number_format($item["Match_Score"], 2) ?>%
                    </strong>
                    <span>Kecocokan skill</span>
                </div>
            </div>

            <p class="description">
                <?= htmlspecialchars(
                    $item["Deskripsi"] ?? "Belum ada deskripsi karier.",
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>
            </p>

            <div
                class="progress"
                role="progressbar"
                aria-label="Persentase kecocokan skill"
                aria-valuemin="0"
                aria-valuemax="100"
                aria-valuenow="<?= $item["Match_Score"] ?>"
            >
                <div
                    class="progress-fill"
                    style="width: <?= $item["Match_Score"] ?>%;"
                ></div>
            </div>
        </article>
    <?php endforeach; ?>

    <div class="actions">
        <a class="button" href="../../index.php">
            Kembali ke Dashboard
        </a>

        <a class="button secondary" href="hitung_skor_otomatis.php">
            Lihat Skor Skill
        </a>
    </div>

</main>
</body>
</html>
