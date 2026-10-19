
<?php
session_start();

require_once __DIR__ . "/../../config/database.php";

// Pastikan pengguna sudah login
if (!isset($_SESSION["ID_User"])) {
    header("Location: ../login_form.php");
    exit;
}

$id_user = (int) $_SESSION["ID_User"];

$bobotSkill = [
    1  => ['A'=>[1=>5,2=>2], 'B'=>[2=>5,4=>2], 'C'=>[3=>5,1=>2], 'D'=>[4=>5,5=>2]],
    2  => ['A'=>[1=>5,5=>2], 'B'=>[2=>5,1=>2], 'C'=>[3=>5,4=>2], 'D'=>[5=>5,4=>2]],
    3  => ['A'=>[1=>5,4=>2], 'B'=>[2=>5,3=>2], 'C'=>[3=>5,1=>2], 'D'=>[5=>5,4=>2]],
    4  => ['A'=>[4=>5,1=>2], 'B'=>[2=>5,5=>2], 'C'=>[3=>5,2=>2], 'D'=>[5=>5,4=>2]],
    5  => ['A'=>[1=>5,4=>2], 'B'=>[2=>5,3=>2], 'C'=>[3=>5,4=>2], 'D'=>[5=>5,4=>2]],
    6  => ['A'=>[1=>5,3=>2], 'B'=>[2=>5,4=>2], 'C'=>[3=>5,1=>2], 'D'=>[5=>5,4=>2]],
    7  => ['A'=>[1=>5,4=>2], 'B'=>[2=>5,5=>2], 'C'=>[3=>5,2=>2], 'D'=>[5=>5,4=>2]],
    8  => ['A'=>[1=>5,3=>2], 'B'=>[2=>5,4=>2], 'C'=>[3=>5,2=>2], 'D'=>[5=>5,4=>2]],
    9  => ['A'=>[1=>5,4=>2], 'B'=>[2=>5,5=>2], 'C'=>[3=>5,2=>2], 'D'=>[5=>5,4=>2]],
    10 => ['A'=>[1=>5,4=>2], 'B'=>[2=>5,5=>2], 'C'=>[3=>5,1=>2], 'D'=>[4=>5,5=>2]],
    11 => ['A'=>[1=>5,4=>2], 'B'=>[2=>5,1=>2], 'C'=>[3=>5,2=>2], 'D'=>[5=>5,4=>2]],
    12 => ['A'=>[1=>5,3=>2], 'B'=>[2=>5,1=>2], 'C'=>[3=>5,4=>2], 'D'=>[5=>5,4=>2]],
    13 => ['A'=>[1=>5,4=>2], 'B'=>[2=>5,3=>2], 'C'=>[3=>5,1=>2], 'D'=>[5=>5,4=>2]],
    14 => ['A'=>[1=>5,2=>2], 'B'=>[2=>5,5=>2], 'C'=>[3=>5,4=>2], 'D'=>[4=>5,5=>2]],
    15 => ['A'=>[1=>5,4=>2], 'B'=>[2=>5,3=>2], 'C'=>[3=>5,1=>2], 'D'=>[5=>5,4=>2]]
];

try {
    $conn->beginTransaction();

    // Ambil 15 pertanyaan aktif
    $stmt = $conn->query(
        "SELECT ID_Bank_Pertanyaan, Question_Text, Question_Order
         FROM T_Bank_Pertanyaan
         WHERE Is_Active = 1
         ORDER BY RAND()
         LIMIT 15"
    );

    $pertanyaan = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($pertanyaan) !== 15) {
        throw new Exception(
            "Bank pertanyaan harus memiliki minimal 15 pertanyaan aktif."
        );
    }

    // Buat assessment baru
    $stmt = $conn->prepare(
        "INSERT INTO T_Assessment
         (ID_User, Tanggal, Status, Started_at)
         VALUES (:id_user, CURDATE(), 'In Progress', NOW())"
    );

    $stmt->execute([
        ":id_user" => $id_user
    ]);

    $id_assessment = (int) $conn->lastInsertId();

    // Siapkan query penyimpanan pertanyaan
    $stmt_question = $conn->prepare(
        "INSERT INTO T_Assessment_Question
         (ID_Assessment, Question_Text, Question_Order, Generated_By_AI)
         VALUES (:id_assessment, :question_text, :question_order, 0)"
    );

    // Ambil pilihan jawaban dari bank
    $stmt_option_source = $conn->prepare(
        "SELECT Option_Label, Option_Text
         FROM T_Bank_Pilihan
         WHERE ID_Bank_Pertanyaan = :id_bank
         ORDER BY Option_Label"
    );

    // Simpan pilihan untuk pertanyaan assessment
    $stmt_option_target = $conn->prepare(
        "INSERT INTO T_Question_Option
         (ID_Assessment_Question, Option_Label, Option_Text)
         VALUES (:id_question, :label, :option_text)"
    );

    // Simpan bobot setiap pilihan
    $stmt_bobot = $conn->prepare(
        "INSERT INTO T_Option_Skill_Weight
         (ID_Option, ID_Skill, Weight)
         VALUES (:id_option, :id_skill, :weight)"
    );

    foreach ($pertanyaan as $index => $p) {
        $id_bank = (int) $p["ID_Bank_Pertanyaan"];
        $nomor_pertanyaan = (int) $p["Question_Order"];

        // Pastikan pemetaan bobot tersedia
        if (!isset($bobotSkill[$nomor_pertanyaan])) {
            throw new Exception(
                "Pemetaan bobot tidak tersedia untuk nomor pertanyaan "
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

        // Ambil pilihan jawaban pertanyaan ini
        $stmt_option_source->execute([
            ":id_bank" => $id_bank
        ]);

        $options = $stmt_option_source->fetchAll(PDO::FETCH_ASSOC);

        if (count($options) !== 4) {
            throw new Exception(
                "Pertanyaan bank ID " . $id_bank
                . " harus memiliki tepat 4 pilihan."
            );
        }

        foreach ($options as $option) {
            $label = $option["Option_Label"];

            if (!isset($bobotSkill[$nomor_pertanyaan][$label])) {
                throw new Exception(
                    "Pemetaan bobot tidak ditemukan untuk pertanyaan "
                    . $nomor_pertanyaan . ", pilihan " . $label
                );
            }

            // Simpan pilihan jawaban
            $stmt_option_target->execute([
                ":id_question" => $id_question,
                ":label" => $label,
                ":option_text" => $option["Option_Text"]
            ]);

            $id_option = (int) $conn->lastInsertId();

            // Simpan bobot skill untuk pilihan tersebut
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

    // Simpan seluruh perubahan hanya jika semua proses berhasil
    $conn->commit();

    // Simpan ID assessment agar halaman pengerjaan dapat menggunakannya
    $_SESSION["ID_Assessment"] = $id_assessment;

    header("Location: kerjakan.php");
    exit;

} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log("Pembuatan assessment Skillora gagal: " . $e->getMessage());

    http_response_code(500);
    exit(
        "Assessment gagal dibuat. Periksa log error PHP untuk mengetahui "
        . "penyebabnya, lalu coba kembali."
    );
}
