<?php
session_start();

require_once "../../config/database.php";

if (!isset($_SESSION["ID_User"])) {
    header("Location: ../login_form.php");
    exit;
}

$id_user = (int) $_SESSION["ID_User"];

try {
    $conn->begin_transaction();

    // Buat assessment baru
    $stmt = $conn->prepare(
        "INSERT INTO T_Assessment
         (ID_User, Tanggal, Status, Started_at)
         VALUES (?, CURDATE(), 'In Progress', NOW())"
    );
    $stmt->bind_param("i", $id_user);
    $stmt->execute();

    $id_assessment = $conn->insert_id;
    $stmt->close();

    // Ambil 15 pertanyaan aktif dari bank secara acak
    $result = $conn->query(
        "SELECT ID_Bank_Pertanyaan, Question_Text, Question_Order
        FROM T_Bank_Pertanyaan
        WHERE Is_Active = 1
        ORDER BY RAND()
        LIMIT 15"
    );

    if ($result->num_rows !== 15) {
        throw new Exception(
            "Bank pertanyaan belum memiliki 15 pertanyaan aktif."
        );
    }

    $pertanyaan = [];
    while ($row = $result->fetch_assoc()) {
        $pertanyaan[] = $row;
    }

    $stmt_question = $conn->prepare(
        "INSERT INTO T_Assessment_Question
         (ID_Assessment, Question_Text, Question_Order, Generated_By_AI)
         VALUES (?, ?, ?, 0)"
    );

    $stmt_option_source = $conn->prepare(
        "SELECT Option_Label, Option_Text
         FROM T_Bank_Pilihan
         WHERE ID_Bank_Pertanyaan = ?
         ORDER BY Option_Label"
    );

    $stmt_option_target = $conn->prepare(
        "INSERT INTO T_Question_Option
         (ID_Assessment_Question, Option_Label, Option_Text)
         VALUES (?, ?, ?)"
    );

    
$bobotSkill = [
    1 => ['A'=>[1=>5,2=>2], 'B'=>[2=>5,4=>2], 'C'=>[3=>5,1=>2], 'D'=>[4=>5,5=>2]],
    2 => ['A'=>[1=>5,5=>2], 'B'=>[2=>5,1=>2], 'C'=>[3=>5,4=>2], 'D'=>[5=>5,4=>2]],
    3 => ['A'=>[1=>5,4=>2], 'B'=>[2=>5,3=>2], 'C'=>[3=>5,1=>2], 'D'=>[5=>5,4=>2]],
    4 => ['A'=>[4=>5,1=>2], 'B'=>[2=>5,5=>2], 'C'=>[3=>5,2=>2], 'D'=>[5=>5,4=>2]],
    5 => ['A'=>[1=>5,4=>2], 'B'=>[2=>5,3=>2], 'C'=>[3=>5,4=>2], 'D'=>[5=>5,4=>2]],
    6 => ['A'=>[1=>5,3=>2], 'B'=>[2=>5,4=>2], 'C'=>[3=>5,1=>2], 'D'=>[5=>5,4=>2]],
    7 => ['A'=>[1=>5,4=>2], 'B'=>[2=>5,5=>2], 'C'=>[3=>5,2=>2], 'D'=>[5=>5,4=>2]],
    8 => ['A'=>[1=>5,3=>2], 'B'=>[2=>5,4=>2], 'C'=>[3=>5,2=>2], 'D'=>[5=>5,4=>2]],
    9 => ['A'=>[1=>5,4=>2], 'B'=>[2=>5,5=>2], 'C'=>[3=>5,2=>2], 'D'=>[5=>5,4=>2]],
    10 => ['A'=>[1=>5,4=>2], 'B'=>[2=>5,5=>2], 'C'=>[3=>5,1=>2], 'D'=>[4=>5,5=>2]],
    11 => ['A'=>[1=>5,4=>2], 'B'=>[2=>5,1=>2], 'C'=>[3=>5,2=>2], 'D'=>[5=>5,4=>2]],
    12 => ['A'=>[1=>5,3=>2], 'B'=>[2=>5,1=>2], 'C'=>[3=>5,4=>2], 'D'=>[5=>5,4=>2]],
    13 => ['A'=>[1=>5,4=>2], 'B'=>[2=>5,3=>2], 'C'=>[3=>5,1=>2], 'D'=>[5=>5,4=>2]],
    14 => ['A'=>[1=>5,2=>2], 'B'=>[2=>5,5=>2], 'C'=>[3=>5,4=>2], 'D'=>[4=>5,5=>2]],
    15 => ['A'=>[1=>5,4=>2], 'B'=>[2=>5,3=>2], 'C'=>[3=>5,1=>2], 'D'=>[5=>5,4=>2]],
];

$stmt_bobot = $conn->prepare(
    "INSERT INTO T_Option_Skill_Weight
     (ID_Option, ID_Skill, Weight)
     VALUES (?, ?, ?)"
);

    foreach ($pertanyaan as $index => $p) {
        $id_bank = (int) $p["ID_Bank_Pertanyaan"];
        $teks_pertanyaan = $p["Question_Text"];
        $urutan = $index + 1;

        $stmt_question->bind_param(
            "isi",
            $id_assessment,
            $teks_pertanyaan,
            $urutan
        );
        $stmt_question->execute();

        $id_pertanyaan_assessment = $conn->insert_id;

        $stmt_option_source->bind_param("i", $id_bank);
        $stmt_option_source->execute();
        $options = $stmt_option_source->get_result();

        $jumlah_pilihan = 0;

        while ($option = $options->fetch_assoc()) {
            $label = $option["Option_Label"];
            $teks_pilihan = $option["Option_Text"];

            $stmt_option_target->bind_param(
                "iss",
                $id_pertanyaan_assessment,
                $label,
                $teks_pilihan
            );
            
            $stmt_option_target->execute();

            $id_option_baru = $conn->insert_id;

            $nomor_pertanyaan = (int) $p["Question_Order"];
            $label_pilihan = $option["Option_Label"];

    if (!isset($bobotSkill[$nomor_pertanyaan][$label_pilihan])) {
    throw new Exception("Pemetaan bobot tidak ditemukan.");
}

foreach ($bobotSkill[$nomor_pertanyaan][$label_pilihan] as $id_skill => $nilai) {
    $id_skill = (int) $id_skill;
    $nilai = (float) $nilai;

    $stmt_bobot->bind_param(
        "iid",
        $id_option_baru,
        $id_skill,
        $nilai
    );

    $stmt_bobot->execute();
}

$jumlah_pilihan++;
        }

        if ($jumlah_pilihan !== 4) {
            throw new Exception(
                "Salah satu pertanyaan tidak memiliki tepat 4 pilihan."
            );
        }
    }

    $stmt_question->close();
    $stmt_option_source->close();
    $stmt_option_target->close();

    $stmt_bobot->close();

    $conn->commit();

    header("Location: kerjakan.php");
    exit;

} catch (Throwable $e) {
    $conn->rollback();

    http_response_code(500);
    echo "Assessment gagal dibuat. Silakan periksa konfigurasi database.";
    error_log($e->getMessage());
}
?>