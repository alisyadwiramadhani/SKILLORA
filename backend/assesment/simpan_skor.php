<?php

session_start();

require_once "../../config/database.php";

// Data untuk pengujian
$id_assessment = 1;
$id_option = 1;

// Pastikan assessment tersedia
$cek = $conn->prepare(
    "SELECT ID_Assessment
     FROM T_Assessment
     WHERE ID_Assessment = ?"
);

$cek->bind_param("i", $id_assessment);
$cek->execute();

if ($cek->get_result()->num_rows === 0) {
    die("Assessment tidak ditemukan.");
}

$cek->close();

// Ambil bobot skill dari pilihan A
$sql = "SELECT ID_Skill, Weight
        FROM T_Option_Skill_Weight
        WHERE ID_Option = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_option);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Bobot skill belum ditemukan.");
}

// Simpan seluruh skor dalam satu transaksi
$conn->begin_transaction();

try {
    $insert = $conn->prepare(
        "INSERT INTO T_Hasil_Assessment
         (ID_Assessment, ID_Skill, Score)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE Score = Score + VALUES(Score)"
    );

    while ($row = $result->fetch_assoc()) {
        $id_skill = (int) $row["ID_Skill"];
        $score = (float) $row["Weight"];

        $insert->bind_param(
            "iid",
            $id_assessment,
            $id_skill,
            $score
        );

        $insert->execute();
    }

    $conn->commit();

    echo "Skor berhasil disimpan!";

    $insert->close();

} catch (Throwable $e) {
    $conn->rollback();
    echo "Gagal menyimpan skor.";
}

$stmt->close();
$conn->close();
?>