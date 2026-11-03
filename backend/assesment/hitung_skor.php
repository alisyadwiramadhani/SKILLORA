
<?php

require_once __DIR__ . "/../../config/database.php";

// Untuk pengujian, kita gunakan pilihan A
$id_option = 1;

$sql = "SELECT
            s.Nama_Skill,
            osw.Weight
        FROM T_Option_Skill_Weight osw
        JOIN T_Skill s
            ON osw.ID_Skill = s.ID_Skill
        WHERE osw.ID_Option = :id_option";

try {
    $stmt = $conn->prepare($sql);

    $stmt->execute([
        ":id_option" => $id_option
    ]);

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h2>Hasil Skor Skill</h2>";

    if (empty($result)) {
        echo "Bobot untuk pilihan ini belum ditemukan.";
    }

    foreach ($result as $row) {
        echo htmlspecialchars($row["Nama_Skill"]);
        echo ": " . htmlspecialchars((string) $row["Weight"]) . "<br>";
    }

    $stmt->closeCursor();

} catch (PDOException $e) {
    error_log("Gagal menghitung skor skill: " . $e->getMessage());

    http_response_code(500);
    echo "Gagal mengambil data skor skill.";
}

?>
