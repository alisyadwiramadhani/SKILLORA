
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
    $stmt->execute([":id_user" => $id_user]);
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
    $stmt->execute([":id_assessment" => $id_assessment]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ambil pilihan jawaban untuk setiap pertanyaan
    $stmt_opsi = $conn->prepare(
        "SELECT ID_Option, Option_Label, Option_Text
         FROM T_Question_Option
         WHERE ID_Assessment_Question = :id_question
         ORDER BY Option_Label ASC"
    );

    $daftar_pertanyaan = [];

    foreach ($questions as $question) {
        $id_question = (int) $question["ID_Assessment_Question"];

        $stmt_opsi->execute([":id_question" => $id_question]);

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
            font-size: 26px;
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

        /* Hanya slide aktif yang ditampilkan */
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

        .nav-button,
        .submit-button {
            border: 0;
            border-radius: 11px;
            padding: 13px 22px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: background .2s, opacity .2s;
        }

        .previous-button {
            background: #e7efeb;
            color: #36564c;
        }

        .previous-button:hover:not(:disabled) {
            background: #d9e7e0;
        }

        .next-button,
        .submit-button {
            background: #176b5b;
            color: #fff;
        }

        .next-button:hover,
        .submit-button:hover {
            background: #105447;
        }

        .nav-button:disabled {
            opacity: .4;
            cursor: not-allowed;
        }

        .submit-button {
            display: none;
        }

        .message {
            display: none;
            color: #a13d32;
            background: #fff1ef;
            border-radius: 10px;
            padding: 10px 13px;
            margin-top: 16px;
            font-size: 14px;
        }

        footer {
            padding: 22px;
            text-align: center;
            color: #65756f;
            font-size: 13px;
        }

        @media (max-width: 600px) {
            main {
                margin: 20px auto;
            }

            .intro,
            .question-card,
            .empty-card {
                border-radius: 16px;
                padding: 20px;
            }

            h1 {
                font-size: 25px;
            }

            .option {
                padding: 15px;
                gap: 11px;
            }

            .navigation {
                gap: 8px;
            }

            .nav-button,
            .submit-button {
                padding: 12px 14px;
                font-size: 13px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation: none !important;
                transition: none !important;
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
            Pilih satu jawaban yang paling menggambarkan dirimu.
            Gunakan tombol Sebelumnya dan Selanjutnya untuk berpindah
            pertanyaan.
        </p>

        <p>
            <strong>Total pertanyaan:</strong>
            <?= count($daftar_pertanyaan) ?>
        </p>
    </section>

    <?php if (empty($daftar_pertanyaan)): ?>

        <section class="empty-card">
            <h2>Pertanyaan belum tersedia</h2>
            <p class="muted">
                Assessment ini belum memiliki pertanyaan.
                Silakan kembali ke dashboard.
            </p>
            <a href="../../index.php">Kembali ke Dashboard</a>
        </section>

    <?php else: ?>

        <div class="progress-top">
            <span id="progress-label">
                Pertanyaan 1 dari <?= count($daftar_pertanyaan) ?>
            </span>
            <span id="progress-percent">0% selesai</span>
        </div>

        <div class="progress-track" aria-label="Progres assessment">
            <div class="progress-fill" id="progress-fill"></div>
        </div>

        <form id="assessment-form" action="simpan_jawaban.php" method="POST">

            <?php foreach ($daftar_pertanyaan as $index => $question): ?>

                <section
                    class="question-card<?= $index === 0 ? ' active' : '' ?>"
                    data-question-index="<?= $index ?>"
                    aria-hidden="<?= $index === 0 ? 'false' : 'true' ?>"
                >
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

            <p class="message" id="validation-message" role="alert">
                Pilih satu jawaban sebelum melanjutkan.
            </p>

            <div class="navigation">
                <button
                    type="button"
                    class="nav-button previous-button"
                    id="previous-button"
                    disabled
                >
                    ← Sebelumnya
                </button>

                <button
                    type="button"
                    class="nav-button next-button"
                    id="next-button"
                >
                    Selanjutnya →
                </button>

                <button
                    type="submit"
                    class="submit-button"
                    id="submit-button"
                >
                    Simpan Jawaban &amp; Lanjutkan
                </button>
            </div>

        </form>

    <?php endif; ?>
</main>

<footer>
    &copy; <?= date("Y") ?> Skillora — Kenali potensimu, temukan arah kariermu.
</footer>

<script>
(() => {
    const form = document.getElementById("assessment-form");

    if (!form) {
        return;
    }

    const slides = Array.from(
        form.querySelectorAll(".question-card[data-question-index]")
    );

    if (slides.length === 0) {
        return;
    }

    const previousButton = document.getElementById("previous-button");
    const nextButton = document.getElementById("next-button");
    const submitButton = document.getElementById("submit-button");
    const progressLabel = document.getElementById("progress-label");
    const progressPercent = document.getElementById("progress-percent");
    const progressFill = document.getElementById("progress-fill");
    const message = document.getElementById("validation-message");

    let current = 0;

    function isAnswered(slide) {
        return slide.querySelector(
            'input[type="radio"]:checked'
        ) !== null;
    }

    function updateProgress() {
        const answered = slides.filter(isAnswered).length;
        const percent = Math.round((answered / slides.length) * 100);

        progressLabel.textContent =
            `Pertanyaan ${current + 1} dari ${slides.length}`;

        progressPercent.textContent = `${percent}% selesai`;
        progressFill.style.width = `${percent}%`;
    }

    function showSlide(index) {
        current = index;

        slides.forEach((slide, i) => {
            const isActive = i === current;

            slide.classList.toggle("active", isActive);
            slide.setAttribute(
                "aria-hidden",
                isActive ? "false" : "true"
            );
        });

        previousButton.disabled = current === 0;

        nextButton.style.display =
            current === slides.length - 1 ? "none" : "inline-block";

        submitButton.style.display =
            current === slides.length - 1 ? "inline-block" : "none";

        message.style.display = "none";

        updateProgress();

        window.scrollTo({
            top: 0,
            behavior: "smooth"
        });
    }

    function validateCurrentSlide() {
        if (!isAnswered(slides[current])) {
            message.textContent =
                "Pilih satu jawaban sebelum melanjutkan.";

            message.style.display = "block";
            return false;
        }

        message.style.display = "none";
        return true;
    }

    previousButton.addEventListener("click", () => {
        if (current > 0) {
            showSlide(current - 1);
        }
    });

    nextButton.addEventListener("click", () => {
        if (!validateCurrentSlide()) {
            return;
        }

        if (current < slides.length - 1) {
            showSlide(current + 1);
        }
    });

    form.addEventListener("change", (event) => {
        if (event.target.matches('input[type="radio"]')) {
            message.style.display = "none";
            updateProgress();
        }
    });

    form.addEventListener("submit", (event) => {
        const unansweredIndex = slides.findIndex(
            slide => !isAnswered(slide)
        );

        if (unansweredIndex !== -1) {
            event.preventDefault();

            showSlide(unansweredIndex);

            message.textContent =
                "Pertanyaan ini belum dijawab. Silakan pilih satu jawaban.";

            message.style.display = "block";
        }
    });

    showSlide(0);
})();
</script>

</body>
</html>
