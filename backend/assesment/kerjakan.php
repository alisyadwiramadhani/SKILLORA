
<?php
session_start();

require_once __DIR__ . "/../../config/database.php";

// 1. Pastikan pengguna sudah login
if (!isset($_SESSION["ID_User"])) {
    header("Location: ../login_form.php");
    exit;
}

$id_user = (int) $_SESSION["ID_User"];

// 2. Pastikan ID Assessment tersedia di session
if (!isset($_SESSION["ID_Assessment"])) {
    exit("Session assessment tidak ditemukan. Silakan mulai assessment dari dashboard.");
}

$id_assessment = (int) $_SESSION["ID_Assessment"];

if ($id_assessment <= 0) {
    exit("ID Assessment tidak valid.");
}

try {

    // 3. Validasi kepemilikan dan status assessment
    $sql = "
        SELECT ID_Assessment
        FROM T_Assessment
        WHERE ID_Assessment = :id_assessment
          AND ID_User = :id_user
          AND Status = 'In Progress'
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":id_assessment" => $id_assessment,
        ":id_user" => $id_user
    ]);

    $assessment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$assessment) {
        exit(
            "Assessment tidak ditemukan atau sudah selesai. "
            . "Silakan mulai assessment baru dari dashboard."
        );
    }

    // 4. Ambil tepat pertanyaan milik assessment ini
    $sql = "
        SELECT
            ID_Assessment_Question,
            Question_Text,
            Question_Order
        FROM T_Assessment_Question
        WHERE ID_Assessment = :id_assessment
        ORDER BY Question_Order ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":id_assessment" => $id_assessment
    ]);

    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Pastikan jumlah pertanyaan tepat 8
    if (count($questions) !== 8) {
        throw new Exception(
            "Jumlah pertanyaan tidak sesuai. Seharusnya terdapat tepat 8 pertanyaan."
        );
    }

    // 6. Ambil pilihan jawaban setiap pertanyaan
    $stmt_opsi = $conn->prepare("
        SELECT
            ID_Option,
            Option_Label,
            Option_Text
        FROM T_Question_Option
        WHERE ID_Assessment_Question = :id_question
        ORDER BY Option_Label ASC
    ");

    $daftar_pertanyaan = [];

    foreach ($questions as $question) {

        $id_question = (int) $question["ID_Assessment_Question"];

        $stmt_opsi->execute([
            ":id_question" => $id_question
        ]);

        $options = $stmt_opsi->fetchAll(PDO::FETCH_ASSOC);

        // Pastikan setiap pertanyaan memiliki 4 pilihan
        if (count($options) !== 4) {
            throw new Exception(
                "Pertanyaan nomor "
                . $question["Question_Order"]
                . " tidak memiliki tepat 4 pilihan jawaban."
            );
        }

        $question["options"] = $options;
        $daftar_pertanyaan[] = $question;
    }

} catch (Throwable $e) {

    error_log(
        "Kerjakan assessment Skillora: " . $e->getMessage()
    );

    http_response_code(500);

    exit(
        "Terjadi kesalahan saat memuat assessment. "
        . "Silakan periksa log PHP."
    );
}

// 7. Fungsi keamanan output HTML
function h($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

$total_pertanyaan = count($daftar_pertanyaan);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Assessment | Skillora</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            color: #263b36;
            background: #f1f7f5;
            line-height: 1.6;
        }

        header {
            padding: 18px 6%;
            background: #176b5b;
            color: white;
        }

        header a {
            color: white;
            text-decoration: none;
            font-size: 20px;
            font-weight: 800;
        }

        main {
            width: min(92%, 980px);
            margin: 32px auto;
        }

        .intro,
        .question-card,
        .empty-card {
            background: #fff;
            border: 1px solid #e0ebe6;
            border-radius: 22px;
            padding: clamp(22px, 4vw, 38px);
            box-shadow: 0 8px 28px rgba(30, 72, 60, .05);
        }

        .intro {
            margin-bottom: 25px;
        }

        .tag {
            display: inline-block;
            padding: 6px 12px;
            background: #e5f4ef;
            color: #176b5b;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .4px;
        }

        h1 {
            margin: 12px 0 5px;
            color: #176b5b;
            font-size: 30px;
        }

        .muted {
            color: #65756f;
        }

        .progress-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin: 0 0 10px;
            font-size: 14px;
            font-weight: 700;
            color: #176b5b;
        }

        .progress-track {
            width: 100%;
            height: 8px;
            background: #e6efeb;
            border-radius: 99px;
            overflow: hidden;
            margin-bottom: 24px;
        }

        .progress-fill {
            width: 0;
            height: 100%;
            background: #218b69;
            border-radius: inherit;
            transition: width .25s ease;
        }

        .question-card {
            display: none;
        }

        .question-card.active {
            display: block;
            animation: slideIn .25s ease;
        }

        @keyframes slideIn {
            from {
                opacity: .5;
                transform: translateX(18px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .question-number {
            color: #176b5b;
            font-size: 14px;
            font-weight: 800;
            margin: 0 0 12px;
        }

        .question-card h2 {
            font-size: clamp(20px, 2.6vw, 27px);
            line-height: 1.55;
            margin: 0 0 26px;
            color: #263b36;
        }

        .option {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 18px 20px;
            margin: 12px 0;
            border: 1px solid #dce8e2;
            border-radius: 15px;
            cursor: pointer;
            font-size: clamp(15px, 1.8vw, 19px);
            transition: background .2s, border-color .2s;
        }

        .option:hover {
            background: #f5faf7;
            border-color: #8dbdaf;
        }

        .option:has(input:checked) {
            background: #e5f4ef;
            border-color: #176b5b;
        }

        .option input {
            width: 19px;
            height: 19px;
            flex: 0 0 19px;
            accent-color: #176b5b;
        }

        .option strong {
            color: #193b35;
        }

        .navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-top: 24px;
        }

        button {
            border: none;
            border-radius: 11px;
            padding: 13px 24px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: .2s;
        }

        button:disabled {
            opacity: .45;
            cursor: not-allowed;
        }

        .btn-prev {
            background: #e8f0ec;
            color: #176b5b;
        }

        .btn-next,
        .btn-submit {
            background: #176b5b;
            color: white;
        }

        button:not(:disabled):hover {
            transform: translateY(-2px);
            opacity: .9;
        }

        .warning {
            display: none;
            margin-top: 16px;
            padding: 12px 15px;
            background: #fff2e7;
            color: #a04a17;
            border-radius: 10px;
            font-size: 14px;
        }

        .question-hint {
            margin-top: 18px;
            font-size: 13px;
            color: #718079;
        }

        @media (max-width: 600px) {
            main {
                margin: 20px auto;
            }

            .navigation {
                flex-wrap: wrap;
            }

            .navigation button {
                flex: 1;
            }

            h1 {
                font-size: 25px;
            }

            .option {
                padding: 15px;
            }
        }
    </style>
</head>

<body>

<header>
    <a href="../../index.php">Skillora</a>
</header>

<main>

    <section class="intro">

        <span class="tag">SKILL ASSESSMENT</span>

        <h1>Kenali Potensi Dirimu</h1>

        <p class="muted">
            Jawablah seluruh pertanyaan berikut sesuai dengan
            kemampuan dan pengalaman dirimu. Pilih satu jawaban
            yang paling menggambarkan dirimu.
        </p>

        <p>
            <strong>Total pertanyaan:</strong>
            <?= $total_pertanyaan; ?> pertanyaan
        </p>

    </section>

    <div class="progress-top">
        <span id="progress-text">Pertanyaan 1 dari 8</span>
        <span id="progress-percent">0%</span>
    </div>

    <div class="progress-track">
        <div class="progress-fill" id="progress-fill"></div>
    </div>

    <form
        action="simpan_jawaban.php"
        method="POST"
        id="assessment-form"
    >

        <?php foreach ($daftar_pertanyaan as $index => $question): ?>

            <section
                class="question-card <?= $index === 0 ? 'active' : ''; ?>"
                data-index="<?= $index; ?>"
            >

                <p class="question-number">
                    PERTANYAAN <?= $index + 1; ?> DARI <?= $total_pertanyaan; ?>
                </p>

                <h2>
                    <?= h($question["Question_Text"]); ?>
                </h2>

                <?php foreach ($question["options"] as $option): ?>

                    <label class="option">

                        <input
                            type="radio"
                            name="jawaban[<?= (int) $question["ID_Assessment_Question"]; ?>]"
                            value="<?= (int) $option["ID_Option"]; ?>"
                        >

                        <span>
                            <strong>
                                <?= h($option["Option_Label"]); ?>.
                            </strong>

                            <?= h($option["Option_Text"]); ?>
                        </span>

                    </label>

                <?php endforeach; ?>

            </section>

        <?php endforeach; ?>

        <div class="warning" id="warning-message">
            Silakan pilih salah satu jawaban sebelum melanjutkan.
        </div>

        <div class="navigation">

            <button
                type="button"
                class="btn-prev"
                id="prev-button"
                disabled
            >
                Sebelumnya
            </button>

            <button
                type="button"
                class="btn-next"
                id="next-button"
            >
                Selanjutnya
            </button>

            <button
                type="submit"
                class="btn-submit"
                id="submit-button"
                style="display: none;"
            >
                Kirim Jawaban
            </button>

        </div>

        <p class="question-hint">
            Pastikan semua pertanyaan telah dijawab sebelum
            mengirimkan assessment.
        </p>

    </form>

</main>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const slides = document.querySelectorAll(".question-card");
    const prevButton = document.getElementById("prev-button");
    const nextButton = document.getElementById("next-button");
    const submitButton = document.getElementById("submit-button");
    const progressFill = document.getElementById("progress-fill");
    const progressText = document.getElementById("progress-text");
    const progressPercent = document.getElementById("progress-percent");
    const warningMessage = document.getElementById("warning-message");
    const form = document.getElementById("assessment-form");

    const totalQuestions = slides.length;
    let currentIndex = 0;

    // Pastikan jumlah slide sama dengan 8
    if (totalQuestions !== 8) {
        alert("Jumlah pertanyaan tidak sesuai. Seharusnya 8 pertanyaan.");
        return;
    }

    function isAnswered(index) {
        return slides[index].querySelector(
            'input[type="radio"]:checked'
        ) !== null;
    }

    function updateDisplay() {

        slides.forEach(function (slide, index) {
            slide.classList.toggle("active", index === currentIndex);
        });

        prevButton.disabled = currentIndex === 0;

        if (currentIndex === totalQuestions - 1) {
            nextButton.style.display = "none";
            submitButton.style.display = "inline-block";
        } else {
            nextButton.style.display = "inline-block";
            submitButton.style.display = "none";
        }

        const progress = ((currentIndex + 1) / totalQuestions) * 100;

        progressFill.style.width = progress + "%";
        progressText.textContent =
            "Pertanyaan " + (currentIndex + 1) +
            " dari " + totalQuestions;

        progressPercent.textContent =
            Math.round(progress) + "%";

        warningMessage.style.display = "none";
    }

    nextButton.addEventListener("click", function () {

        if (!isAnswered(currentIndex)) {
            warningMessage.textContent =
                "Silakan pilih salah satu jawaban sebelum melanjutkan.";

            warningMessage.style.display = "block";
            return;
        }

        if (currentIndex < totalQuestions - 1) {
            currentIndex++;
            updateDisplay();
            window.scrollTo({ top: 0, behavior: "smooth" });
        }
    });

    prevButton.addEventListener("click", function () {

        if (currentIndex > 0) {
            currentIndex--;
            updateDisplay();
            window.scrollTo({ top: 0, behavior: "smooth" });
        }
    });

    form.addEventListener("submit", function (event) {

        for (let i = 0; i < totalQuestions; i++) {

            if (!isAnswered(i)) {

                event.preventDefault();

                currentIndex = i;
                updateDisplay();

                warningMessage.textContent =
                    "Pertanyaan nomor " + (i + 1) +
                    " belum dijawab. Silakan pilih salah satu jawaban.";

                warningMessage.style.display = "block";

                window.scrollTo({ top: 0, behavior: "smooth" });

                return;
            }
        }

        const confirmed = confirm(
            "Apakah kamu yakin ingin mengirim seluruh jawaban assessment?"
        );

        if (!confirmed) {
            event.preventDefault();
            return;
        }

        submitButton.disabled = true;
        submitButton.textContent = "Menyimpan...";
    });

    updateDisplay();

});
</script>

</body>
</html>
