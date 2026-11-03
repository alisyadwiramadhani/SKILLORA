
<?php

require_once __DIR__ . "/../../config/database.php";

$sql = "SELECT
            qo.Option_Label,
            qo.Option_Text,
            s.Nama_Skill,
            osw.Weight
        FROM T_Option_Skill_Weight osw
        JOIN T_Question_Option qo
            ON osw.ID_Option = qo.ID_Option
        JOIN T_Skill s
            ON osw.ID_Skill = s.ID_Skill
        ORDER BY qo.ID_Option, s.ID_Skill";

try {
    $stmt = $conn->query($sql);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Pilihan " . htmlspecialchars($row["Option_Label"]) . ": ";
        echo htmlspecialchars($row["Option_Text"]) . "<br>";

        echo "Skill: " . htmlspecialchars($row["Nama_Skill"]) . "<br>";
        echo "Bobot: " . htmlspecialchars((string) $row["Weight"]) . "<br><hr>";
    }

    $stmt->closeCursor();

} catch (PDOException $e) {
    error_log("Gagal mengambil data bobot: " . $e->getMessage());
    http_response_code(500);
    echo "Gagal mengambil data bobot.";
}

?>
