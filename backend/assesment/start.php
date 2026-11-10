<?php
session_start();

require_once __DIR__ . "/../../config/database.php";

// 1. Pastikan pengguna sudah login
if (!isset($_SESSION["ID_User"])) {
    header("Location: ../login_form.php");
    exit;
}

$id_user = (int) $_SESSION["ID_User"];

// 2. Daftar 8 pertanyaan assessment
$daftarSoal = [1, 4, 6, 8, 10, 12, 13, 14];

// 3. Pemetaan bobot skill
// 1 = UI/UX Design
// 2 = Kreativitas
// 3 = Komunikasi
// 4 = Problem Solving
// 5 = Analytical Thinking

$bobotSkill = [
    1 => [
        'A' => [1 => 5, 3 => 2],
        'B' => [2 => 5, 4 => 2],
        'C' => [3 => 5, 1 => 2],
        'D' => [5 => 5, 4 => 2]
    ],
    4 => [
        'A' => [5 => 5, 4 => 2],
        'B' => [2 => 5, 4 => 2],
        'C' => [3 => 5, 5 => 2],
        'D' => [5 => 5, 4 => 2]
    ],
    6 => [
        'A' => [1 => 5, 5 => 2],
        'B' => [2 => 5, 4 => 2],
        'C' => [3 => 5, 4 => 2],
        'D' => [5 => 5, 4 => 2]
    ],
    8 => [
        'A' => [1 => 5, 3 => 2],
        'B' => [2 => 5, 4 => 2],
        'C' => [3 => 5, 2 => 2],
        'D' => [5 => 5, 4 => 2]
    ],
    10 => [
        'A' => [4 => 5, 5 => 2],
        'B' => [2 => 5, 4 => 2],
        'C' => [3 => 5, 4 => 2],
        'D' => [5 => 5, 4 => 2]
    ],
    12 => [
        'A' => [1 => 5, 3 => 2],
        'B' => [2 => 5, 1 => 2],
        'C' => [3 => 5, 1 => 2],
        'D' => [5 => 5, 1 => 2]
    ],
    13 => [
        'A' => [1 => 5, 5 => 2],
        'B' => [2 => 5, 1 => 2],
        'C' => [3 => 5, 1 => 2],
        'D' => [5 => 5, 4 => 2]
    ],
    14 => [
        'A' => [1 => 5, 2 => 2],
        'B' => [2 => 5, 3 => 2],
        'C' => [3 => 5, 4 => 2],
        'D' => [5 => 5, 4 => 2]
    ]
];

try {
    $conn->beginTransaction();

    // 4. Ambil tepat 8 pertanyaan yang sudah ditentukan
    $placeholder = implode(
        ",",
        array_fill(0, count($daftarSoal), "?")
    );

    $sql = "
        SELECT
            ID_Bank_Pertanyaan,
            Question_Text,
            Question_Order
        FROM T_Bank_Pertanyaan
        WHERE Is_Active = 1
          AND Question_Order IN ($placeholder)
        ORDER BY FIELD(Question_Order, $placeholder)
    ";

    $stmt = $conn->prepare($sql);

    $stmt->execute(
        array_merge($daftarSoal, $daftarSoal)
    );

    $pertanyaan = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($pertanyaan) !== 8) {
        throw new Exception(
            "Delapan pertanyaan assessment belum tersedia atau belum aktif."
        );
    }

    // 5. Buat assessment baru
    $stmt = $conn->prepare(
        "INSERT INTO T_Assessment
        (ID_User, Tanggal, Status, Started_at)
        VALUES (:id_user, CURDATE(), 'In Progress', NOW())"
    );

    $stmt->execute([
        ":id_user" => $id_user
    ]);

    $id_assessment = (int) $conn->lastInsertId();

    // 6. Query penyimpanan pertanyaan
    $stmt_question = $conn->prepare(
        "INSERT INTO T_Assessment_Question
        (ID_Assessment, Question_Text, Question_Order, Generated_By_AI)
        VALUES (:id_assessment, :question_text, :question_order, 0)"
    );

    // 7. Query mengambil pilihan dari bank pertanyaan
    $stmt_option_source = $conn->prepare(
        "SELECT Option_Label, Option_Text
         FROM T_Bank_Pilihan
         WHERE ID_Bank_Pertanyaan = :id_bank
         ORDER BY Option_Label"
    );

    // 8. Query menyimpan pilihan assessment
    $stmt_option_target = $conn->prepare(
        "INSERT INTO T_Question_Option
        (ID_Assessment_Question, Option_Label, Option_Text)
        VALUES (:id_question, :label, :option_text)"
    );

    // 9. Query menyimpan bobot skill
    $stmt_bobot = $conn->prepare(
        "INSERT INTO T_Option_Skill_Weight
        (ID_Option, ID_Skill, Weight)
        VALUES (:id_option, :id_skill, :weight)"
    );

    // 10. Proses setiap pertanyaan
    foreach ($pertanyaan as $index => $p) {

        $id_bank = (int) $p["ID_Bank_Pertanyaan"];
        $nomor_pertanyaan = (int) $p["Question_Order"];

        if (!isset($bobotSkill[$nomor_pertanyaan])) {
            throw new Exception(
                "Bobot skill tidak ditemukan untuk pertanyaan "
                . $nomor_pertanyaan
            );
        }

        // Simpan pertanyaan
        $stmt_question->execute([
            ":id_assessment" => $id_assessment,
            ":question_text" => $p["Question_Text"],
            ":question_order" => $index + 1
        ]);

        $id_question = (int) $conn->lastInsertId();

        // Ambil pilihan jawaban
        $stmt_option_source->execute([
            ":id_bank" => $id_bank
        ]);

        $options = $stmt_option_source->fetchAll(
            PDO::FETCH_ASSOC
        );

        if (count($options) !== 4) {
            throw new Exception(
                "Pertanyaan ID " . $id_bank
                . " harus memiliki tepat 4 pilihan jawaban."
            );
        }

        // Simpan seluruh pilihan dan bobotnya
        foreach ($options as $option) {

            $label = $option["Option_Label"];

            if (!isset(
                $bobotSkill[$nomor_pertanyaan][$label]
            )) {
                throw new Exception(
                    "Bobot pilihan " . $label
                    . " tidak ditemukan pada pertanyaan "
                    . $nomor_pertanyaan
                );
            }

            // Simpan pilihan jawaban
            $stmt_option_target->execute([
                ":id_question" => $id_question,
                ":label" => $label,
                ":option_text" => $option["Option_Text"]
            ]);

            $id_option = (int) $conn->lastInsertId();

            // Simpan bobot setiap skill
            foreach (
                $bobotSkill[$nomor_pertanyaan][$label]
                as $id_skill => $nilai
            ) {
                $stmt_bobot->execute([
                    ":id_option" => $id_option,
                    ":id_skill" => (int) $id_skill,
                    ":weight" => (float) $nilai
                ]);
            }
        }
    }

    // 11. Simpan seluruh perubahan
    $conn->commit();

    // 12. Simpan ID assessment ke session
    $_SESSION["ID_Assessment"] = $id_assessment;

    // 13. Arahkan pengguna ke halaman pengerjaan
    header("Location: kerjakan.php");
    exit;

} catch (Throwable $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log(
        "Pembuatan assessment Skillora gagal: "
        . $e->getMessage()
    );

    http_response_code(500);

    exit(
        "Assessment gagal dibuat. "
        . "Silakan periksa error log PHP."
    );
}
