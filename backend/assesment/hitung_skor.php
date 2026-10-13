<?php

require_once "../../config/database.php";

// Untuk pengujian, kita gunakan pilihan A
$id_option = 1;

$sql = "SELECT
            s.Nama_Skill,
            osw.Weight
        FROM T_Option_Skill_Weight osw
        JOIN T_Skill s
            ON osw.ID_Skill = s.ID_Skill
        WHERE osw.ID_Option = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_option);
$stmt->execute();

$result = $stmt->get_result();

echo "<h2>Hasil Skor Skill</h2>";

if ($result->num_rows === 0) {
    echo "Bobot untuk pilihan ini belum ditemukan.";
}

while ($row = $result->fetch_assoc()) {
    echo htmlspecialchars($row["Nama_Skill"]);
    echo ": " . $row["Weight"] . "<br>";
}

$stmt->close();
$conn->close();
?>