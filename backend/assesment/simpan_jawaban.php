<?php

session_start();

require_once "../../config/database.php";

// Pastikan user sudah login
if (!isset($_SESSION["ID_User"])) {
    die("Silakan login terlebih dahulu.");
}

// Pastikan form dikirim dengan POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Metode permintaan tidak valid.");
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

// Pastikan ada jawaban yang dikirim
if (empty($_POST["jawaban"]) || !is_array($_POST["jawaban"])) {
    die("Silakan pilih jawaban terlebih dahulu.");
}

$jawaban = $_POST["jawaban"];

// Siapkan query validasi opsi
$cek_opsi = $conn->prepare(
    "SELECT q.ID_Assessment_Question
     FROM T_Question_Option o
     JOIN T_Assessment_Question q
       ON o.ID_Assessment_Question = q.ID_Assessment_Question
     WHERE o.ID_Option = ?
       AND q.ID_Assessment_Question = ?
       AND q.ID_Assessment = ?"
);

// Siapkan query simpan jawaban
$simpan = $conn->prepare(
    "INSERT INTO T_Assessment_Answer
     (ID_Assessment, ID_Assessment_Question, ID_Option, Answered_at)
     VALUES (?, ?, ?, NOW())
     ON DUPLICATE KEY UPDATE
        ID_Option = VALUES(ID_Option),
        Answered_at = NOW()"
);

$conn->begin_transaction();

try {

    foreach ($jawaban as $id_pertanyaan => $id_option) {

        $id_pertanyaan = filter_var(
            $id_pertanyaan,
            FILTER_VALIDATE_INT
        );

        $id_option = filter_var(
            $id_option,
            FILTER_VALIDATE_INT
        );

        if (!$id_pertanyaan || !$id_option) {
            throw new Exception("Data jawaban tidak valid.");
        }

        // Pastikan opsi benar-benar milik pertanyaan
        $cek_opsi->bind_param(
            "iii",
            $id_option,
            $id_pertanyaan,
            $id_assessment
        );

        $cek_opsi->execute();

        if ($cek_opsi->get_result()->num_rows !== 1) {
            throw new Exception("Pilihan jawaban tidak valid.");
        }

        // Simpan jawaban
        $simpan->bind_param(
            "iii",
            $id_assessment,
            $id_pertanyaan,
            $id_option
        );

        $simpan->execute();
    }

    $conn->commit();

    echo "<h2>Jawaban berhasil disimpan!</h2>";
    echo "<p>Jawaban assessment kamu sudah tercatat.</p>";
    echo '<a href="kerjakan.php">Kembali ke Assessment</a>';

} catch (Throwable $e) {

    $conn->rollback();

    echo "Gagal menyimpan jawaban. Silakan periksa kembali dan coba lagi.";

}

$cek_opsi->close();
$simpan->close();
$conn->close();
?>