<?php
session_start();

require_once __DIR__ . "/../../config/database.php";

// Pastikan pengguna sudah login
if (!isset($_SESSION["ID_User"])) {
    header("Location: ../login_form.php");
    exit;
}

$id_user = (int) $_SESSION["ID_User"];

try {
    // Cari assessment aktif milik pengguna
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
        exit(
            "Assessment aktif tidak ditemukan. "
            . "Silakan klik Mulai Assessment dari dashboard."
        );
    }

    $id_assessment = (int) $assessment["ID_Assessment"];
    $_SESSION["ID_Assessment"] = $id_assessment;

    // Ambil pertanyaan assessment
    $sql = "SELECT ID_Assessment_Question, Question_Text, Question_Order
            FROM T_Assessment_Question
            WHERE ID_Assessment = :id_assessment
            ORDER BY Question_Order ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":id_assessment" => $id_assessment
    ]);

    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ambil semua pilihan jawaban untuk setiap pertanyaan
    $stmt_opsi = $conn->prepare(
        "SELECT ID_Option, Option_Label, Option_Text
         FROM T_Question_Option
         WHERE ID_Assessment_Question = :id_question
         ORDER BY Option_Label ASC"
    );

    $daftar_pertanyaan = [];

    foreach ($questions as $question) {
        $id_question = (int) $question["ID_Assessment_Question"];

        $stmt_opsi->execute([
            ":id_question" => $id_question
        ]);

        $question["options"] = $stmt_opsi->fetchAll(PDO::FETCH_ASSOC);
        $daftar_pertanyaan[] = $question;
    }

} catch (PDOException $e) {
    error_log("Kerjakan assessment Skillora: " . $e->getMessage());
    http_response_code(500);
    exit("Terjadi kesalahan saat memuat assessment. Periksa log PHP.");
}

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment | Skillora</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            color: #263b36;
            background: #f3f8f7;
            line-height: 1.6;
        }

        header {
            padding: 20px 6%;
            background: #176b5b;
            color: white;
        }

        header a {
            color: white;
            text-decoration: none;
            font-size: 27px;
            font-weight: 800;
        }

        main {
            width: 92%;
            max-width: 850px;
            margin: 35px auto;
        }

        .intro, .question-card {
            background: white;
            border: 1px solid #e4eeea;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 18px rgba(30, 72, 60, 0.05);
        }

        .tag {
            display: inline-block;
            padding: 6px 12px;
            background: #e5f4ef;
            color: #176b5b;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        h1 {
            margin: 12px 0 5px;
            color: #176b5b;
            font-size: 30px;
        }

        .muted {
            color: #65756f;
        }

        .question-number {
            color: #176b5b;
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .question-card h2 {
            font-size: 18px;
            margin: 0 0 18px;
        }

        .option {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 13px 15px;
            margin: 10px 0;
            border: 1px solid #e0eae5;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.2s, border-color 0.2s;
        }

        .option:hover {
            background: #f1f8f5;
            border-color: #8dbdaf;
        }

        .option:has(input:checked) {
            background: #e5f4ef;
            border-color: #176b5b;
        }

        .option input {
            margin-top: 5px;
            accent-color: #176b5b;
        }

        .submit-button {
            display: block;
            width: 100%;
            border: none;
            border-radius: 10px;
            padding: 15px;
            margin-top: 25px;
            color: white;
            background: #176b5b;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
        }

        .submit-button:hover {
            background: #105447;
        }

        footer {
            padding: 22px;
            text-align: center;
            color: #65756f;
            font-size: 13px;
        }

        @media (max-width: 600px) {
            main {
                margin-top: 20px;
            }

            .intro, .question-card {
                padding: 19px;
            }

            h1 {
                font-size: 25px;
            }
        }
    </style>
</head>

<body>
<header>
    <a href="../../index.php">🌿 Skillora</a>
</header>

<main>
    <section class="intro">
        <span class="tag">ASSESSMENT KETERAMPILAN</span>
        <h1>Kenali Potensimu</h1>
        <p class="muted">
            Pilih satu jawaban yang paling menggambarkan dirimu
            untuk setiap pertanyaan. Pastikan semua pertanyaan dijawab
            sebelum mengirimkan hasil.
        </p>
        <p><strong>Total pertanyaan:</strong> <?= count($daftar_pertanyaan) ?></p>
    </section>

    <?php if (empty($daftar_pertanyaan)): ?>

        <section class="question-card">
            <h2>Pertanyaan belum tersedia</h2>
            <p class="muted">
                Assessment ini belum memiliki pertanyaan. Silakan
                kembali ke dashboard dan mulai assessment kembali.
            </p>
            <a href="../../index.php">Kembali ke Dashboard</a>
        </section>

    <?php else: ?>

        <form action="simpan_jawaban.php" method="POST">
            <?php foreach ($daftar_pertanyaan as $index => $question): ?>

                <section class="question-card">
                    <p class="question-number">
                        PERTANYAAN <?= $index + 1 ?>
                    </p>

                    <h2><?= h($question["Question_Text"]) ?></h2>

                    <?php if (empty($question["options"])): ?>

                        <p class="muted">
                            Pilihan jawaban belum tersedia untuk pertanyaan ini.
                        </p>

                    <?php else: ?>

                        <?php foreach ($question["options"] as $option): ?>
                            <label class="option">
                                <input
                                    type="radio"
                                    name="jawaban[<?= (int) $question["ID_Assessment_Question"] ?>]"
                                    value="<?= (int) $option["ID_Option"] ?>"
                                    required
                                >

                                <span>
                                    <strong><?= h($option["Option_Label"]) ?>.</strong>
                                    <?= h($option["Option_Text"]) ?>
                                </span>
                            </label>
                        <?php endforeach; ?>

                    <?php endif; ?>
                </section>

            <?php endforeach; ?>

            <button class="submit-button" type="submit">
                Simpan Jawaban &amp; Lanjutkan
            </button>
        </form>

    <?php endif; ?>
</main>

<footer>
    &copy; <?= date("Y") ?> Skillora — Kenali potensimu, temukan arah kariermu.
</footer>
</body>
</html>
