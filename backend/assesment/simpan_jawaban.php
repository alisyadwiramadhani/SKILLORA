
<?php
session_start();

require_once __DIR__ . "/../../config/database.php";

// Pastikan user sudah login
if (!isset($_SESSION["ID_User"])) {
    header("Location: ../login_form.php");
    exit;
}

// Pastikan form dikirim menggunakan POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: kerjakan.php");
    exit;
}

$id_user = (int) $_SESSION["ID_User"];

// Pastikan jawaban dikirim dalam bentuk array
if (
    !isset($_POST["jawaban"]) ||
    !is_array($_POST["jawaban"]) ||
    empty($_POST["jawaban"])
) {
    exit("Silakan pilih jawaban terlebih dahulu.");
}

$jawaban = $_POST["jawaban"];

try {
    // Ambil assessment aktif milik user
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
        exit("Assessment aktif tidak ditemukan. Silakan mulai assessment kembali.");
    }

    $id_assessment = (int) $assessment["ID_Assessment"];

    // Validasi bahwa pertanyaan termasuk assessment aktif
    $cek_pertanyaan = $conn->prepare(
        "SELECT ID_Assessment_Question
         FROM T_Assessment_Question
         WHERE ID_Assessment_Question = :id_pertanyaan
           AND ID_Assessment = :id_assessment"
    );

    // Validasi bahwa opsi benar-benar milik pertanyaan
    $cek_opsi = $conn->prepare(
        "SELECT o.ID_Option
         FROM T_Question_Option o
         INNER JOIN T_Assessment_Question q
             ON q.ID_Assessment_Question = o.ID_Assessment_Question
         WHERE o.ID_Option = :id_option
           AND q.ID_Assessment_Question = :id_pertanyaan
           AND q.ID_Assessment = :id_assessment"
    );

    // Simpan jawaban; perbarui jika jawaban pertanyaan sudah ada
    $simpan = $conn->prepare(
        "INSERT INTO T_Assessment_Answer
            (ID_Assessment, ID_Assessment_Question, ID_Option, Answered_at)
         VALUES
            (:id_assessment, :id_pertanyaan, :id_option, NOW())
         ON DUPLICATE KEY UPDATE
            ID_Option = VALUES(ID_Option),
            Answered_at = NOW()"
    );

    $conn->beginTransaction();

    foreach ($jawaban as $id_pertanyaan => $id_option) {
        // Pastikan ID berupa bilangan bulat positif
        $id_pertanyaan = filter_var(
            $id_pertanyaan,
            FILTER_VALIDATE_INT
        );

        $id_option = filter_var(
            $id_option,
            FILTER_VALIDATE_INT
        );

        if (
            $id_pertanyaan === false ||
            $id_pertanyaan === null ||
            $id_pertanyaan <= 0 ||
            $id_option === false ||
            $id_option === null ||
            $id_option <= 0
        ) {
            throw new Exception("Data jawaban tidak valid.");
        }

        // Pastikan pertanyaan termasuk assessment aktif
        $cek_pertanyaan->execute([
            ":id_pertanyaan" => $id_pertanyaan,
            ":id_assessment" => $id_assessment
        ]);

        if (!$cek_pertanyaan->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception("Pertanyaan tidak valid.");
        }

        // Pastikan pilihan jawaban sesuai dengan pertanyaannya
        $cek_opsi->execute([
            ":id_option" => $id_option,
            ":id_pertanyaan" => $id_pertanyaan,
            ":id_assessment" => $id_assessment
        ]);

        if (!$cek_opsi->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception("Pilihan jawaban tidak valid.");
        }

        // Simpan jawaban menggunakan PDO
        $simpan->execute([
            ":id_assessment" => $id_assessment,
            ":id_pertanyaan" => $id_pertanyaan,
            ":id_option" => $id_option
        ]);
    }

    $conn->commit();

    // Simpan ID assessment untuk proses selanjutnya
    $_SESSION["ID_Assessment"] = $id_assessment;

} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log("Simpan jawaban Skillora gagal: " . $e->getMessage());

    http_response_code(500);
    exit(
        "Gagal menyimpan jawaban. Periksa kembali struktur tabel database "
        . "dan pastikan setiap pertanyaan memiliki pilihan jawaban yang valid."
    );
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jawaban Berhasil Disimpan - Skillora</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: #f0f7f5;
            font-family: Arial, sans-serif;
            color: #183b35;
        }

        .card {
            width: 100%;
            max-width: 480px;
            padding: 36px 28px;
            text-align: center;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 12px 35px rgba(24, 59, 53, 0.09);
        }

        .icon {
            width: 68px;
            height: 68px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: #d9f5e8;
            color: #16845e;
            font-size: 34px;
        }

        h1 {
            margin-bottom: 12px;
            font-size: 25px;
        }

        p {
            color: #64756f;
            line-height: 1.7;
        }

        .button {
            display: block;
            margin-top: 14px;
            padding: 14px 18px;
            border-radius: 10px;
            background: #147d64;
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        .secondary {
            background: #e9f3f0;
            color: #176c58;
        }
    </style>
</head>

<body>
    <main class="card">
        <div class="icon">✓</div>

        <h1>Jawaban Berhasil Disimpan!</h1>

        <p>
            Jawaban assessment kamu sudah tersimpan.
            Selanjutnya, kamu bisa melanjutkan ke proses
            perhitungan skor untuk melihat hasil assessment.
        </p>

        <a class="button" href="hitung_skor_otomatis.php">
            Lihat Hasil Assessment
        </a>

        <a class="button secondary" href="../../index.php">
            Kembali ke Dashboard
        </a>
    </main>
</body>
</html>
