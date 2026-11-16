
<?php
session_start();

require_once __DIR__ . "/../../config/database.php";

// 1. Validasi login
if (!isset($_SESSION["ID_User"])) {
    header("Location: ../login_form.php");
    exit;
}

$id_user = (int) $_SESSION["ID_User"];

// 2. Validasi metode request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: kerjakan.php");
    exit;
}

// 3. Validasi session assessment
if (!isset($_SESSION["ID_Assessment"])) {
    exit("Session assessment tidak ditemukan. Silakan mulai assessment kembali.");
}

$id_assessment = (int) $_SESSION["ID_Assessment"];

if ($id_assessment <= 0) {
    exit("ID Assessment tidak valid.");
}

// 4. Validasi data jawaban
if (
    !isset($_POST["jawaban"]) ||
    !is_array($_POST["jawaban"])
) {
    exit("Data jawaban tidak ditemukan.");
}

$jawaban = $_POST["jawaban"];

try {

    // 5. Mulai transaksi
    $conn->beginTransaction();

    // 6. Pastikan assessment milik user dan masih berjalan
    $stmt = $conn->prepare("
        SELECT ID_Assessment
        FROM T_Assessment
        WHERE ID_Assessment = :id_assessment
          AND ID_User = :id_user
          AND Status = 'In Progress'
        LIMIT 1
        FOR UPDATE
    ");

    $stmt->execute([
        ":id_assessment" => $id_assessment,
        ":id_user" => $id_user
    ]);

    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception(
            "Assessment tidak ditemukan atau sudah selesai."
        );
    }

    // 7. Ambil daftar pertanyaan assessment
    $stmt = $conn->prepare("
        SELECT ID_Assessment_Question
        FROM T_Assessment_Question
        WHERE ID_Assessment = :id_assessment
        ORDER BY Question_Order ASC
    ");

    $stmt->execute([
        ":id_assessment" => $id_assessment
    ]);

    $daftar_id = array_map(
        "intval",
        $stmt->fetchAll(PDO::FETCH_COLUMN)
    );

    // 8. Pastikan jumlah pertanyaan tepat 8
    if (count($daftar_id) !== 8) {
        throw new Exception(
            "Assessment harus memiliki tepat 8 pertanyaan."
        );
    }

    // 9. Pastikan jumlah jawaban tepat 8
    if (count($jawaban) !== 8) {
        throw new Exception(
            "Semua 8 pertanyaan wajib dijawab."
        );
    }

    // 10. Validasi pilihan jawaban
    $cek_opsi = $conn->prepare("
        SELECT o.ID_Option
        FROM T_Question_Option o
        INNER JOIN T_Assessment_Question q
            ON q.ID_Assessment_Question = o.ID_Assessment_Question
        WHERE o.ID_Option = :id_option
          AND q.ID_Assessment_Question = :id_pertanyaan
          AND q.ID_Assessment = :id_assessment
        LIMIT 1
    ");

    // 11. Query simpan jawaban
    $simpan = $conn->prepare("
        INSERT INTO T_Assessment_Answer (
            ID_Assessment,
            ID_Assessment_Question,
            ID_Option,
            Answered_at
        )
        VALUES (
            :id_assessment,
            :id_pertanyaan,
            :id_option,
            NOW()
        )
        ON DUPLICATE KEY UPDATE
            ID_Option = VALUES(ID_Option),
            Answered_at = NOW()
    ");

    $id_terjawab = [];

    foreach ($jawaban as $id_pertanyaan => $id_option) {

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
            throw new Exception(
                "ID pertanyaan atau pilihan jawaban tidak valid."
            );
        }

        // Pastikan pertanyaan termasuk assessment
        if (!in_array($id_pertanyaan, $daftar_id, true)) {
            throw new Exception(
                "Pertanyaan tidak sesuai dengan assessment."
            );
        }

        // Pastikan opsi milik pertanyaan tersebut
        $cek_opsi->execute([
            ":id_option" => $id_option,
            ":id_pertanyaan" => $id_pertanyaan,
            ":id_assessment" => $id_assessment
        ]);

        if (!$cek_opsi->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception(
                "Pilihan jawaban tidak sesuai dengan pertanyaan."
            );
        }

        // Simpan jawaban
        $simpan->execute([
            ":id_assessment" => $id_assessment,
            ":id_pertanyaan" => $id_pertanyaan,
            ":id_option" => $id_option
        ]);

        $id_terjawab[] = $id_pertanyaan;
    }

    // 12. Pastikan seluruh pertanyaan sudah terjawab
    sort($daftar_id);
    sort($id_terjawab);

    if ($daftar_id !== $id_terjawab) {
        throw new Exception(
            "Jawaban belum mencakup seluruh pertanyaan."
        );
    }

    // 13. Pastikan tepat 8 jawaban unik tersimpan
    $stmt = $conn->prepare("
        SELECT
            COUNT(*) AS total,
            COUNT(DISTINCT ID_Assessment_Question) AS total_unik
        FROM T_Assessment_Answer
        WHERE ID_Assessment = :id_assessment
    ");

    $stmt->execute([
        ":id_assessment" => $id_assessment
    ]);

    $hasil = $stmt->fetch(PDO::FETCH_ASSOC);

    if (
        (int) $hasil["total"] !== 8 ||
        (int) $hasil["total_unik"] !== 8
    ) {
        throw new Exception(
            "Jumlah jawaban tersimpan tidak sesuai."
        );
    }

    // 14. Commit transaksi
    $conn->commit();

    $_SESSION["ID_Assessment"] = $id_assessment;

} catch (Throwable $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log(
        "Simpan jawaban Skillora: " . $e->getMessage()
    );

    http_response_code(500);

    exit(
        "Gagal menyimpan jawaban assessment. "
        . "Silakan periksa kembali database atau log PHP."
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
            background: white;
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
            Seluruh 8 jawaban assessment kamu berhasil disimpan.
            Selanjutnya sistem akan menghitung skor keterampilan
            dan menampilkan rekomendasi karier yang sesuai.
        </p>

        <a
            class="button"
            href="hitung_skor_otomatis.php"
        >
            Lihat Hasil Assessment
        </a>

        <a
            class="button secondary"
            href="../../index.php"
        >
            Kembali ke Dashboard
        </a>

    </main>

</body>

</html>
