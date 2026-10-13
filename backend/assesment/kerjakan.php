<?php

session_start();

require_once "../../config/database.php";

// Pastikan user sudah login
if (!isset($_SESSION["ID_User"])) {
    die("Silakan login terlebih dahulu.");
}

// Ambil assessment aktif milik user
$id_user = $_SESSION["ID_User"];

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
    die("Assessment aktif tidak ditemukan. Silakan mulai assessment terlebih dahulu.");
}

$id_assessment = (int) $assessment["ID_Assessment"];

// Ambil pertanyaan assessment
$sql = "SELECT ID_Assessment_Question, Question_Text
        FROM T_Assessment_Question
        WHERE ID_Assessment = ?
        ORDER BY Question_Order ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_assessment);
$stmt->execute();

$questions = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment Skillora</title>
</head>
<body>

    <h1>Assessment Skillora</h1>

    <form action="simpan_jawaban.php" method="POST">

    <?php if ($questions->num_rows > 0): ?>

        <?php while ($question = $questions->fetch_assoc()): ?>

            <h3>
                <?= htmlspecialchars($question["Question_Text"]) ?>
            </h3>

            <?php
            // Ambil pilihan jawaban untuk pertanyaan ini
            $id_pertanyaan = (int) $question["ID_Assessment_Question"];

            $sql_opsi = "SELECT ID_Option, Option_Label, Option_Text
                         FROM T_Question_Option
                         WHERE ID_Assessment_Question = ?
                         ORDER BY ID_Option ASC";

            $stmt_opsi = $conn->prepare($sql_opsi);
            $stmt_opsi->bind_param("i", $id_pertanyaan);
            $stmt_opsi->execute();

            $options = $stmt_opsi->get_result();
            ?>

            <?php if ($options->num_rows > 0): ?>

                <?php while ($option = $options->fetch_assoc()): ?>

                    <p>
                        <label>
                            <input
                            type="radio"
                            name="jawaban[<?= $id_pertanyaan ?>]"
                            value="<?= (int) $option["ID_Option"] ?>"
                            required
                            >

                            <?= htmlspecialchars($option["Option_Label"]) ?>.
                            <?= htmlspecialchars($option["Option_Text"]) ?>
                        </label>
                    </p>

                <?php endwhile; ?>

            <?php else: ?>

                <p>Pilihan jawaban belum tersedia.</p>

            <?php endif; ?>

            <?php $stmt_opsi->close(); ?>

        <?php endwhile; ?>

    <?php else: ?>

        <p>Belum ada pertanyaan untuk assessment ini.</p>

    <?php endif; ?>

        <button type="submit">Simpan Jawaban</button>
</form>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>