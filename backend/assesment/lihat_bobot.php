<?php

require_once "../../config/database.php";

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

$result = $conn->query($sql);

if (!$result) {
    die("Query gagal: " . $conn->error);
}

while ($row = $result->fetch_assoc()) {
    echo "Pilihan " . htmlspecialchars($row["Option_Label"]) . ": ";
    echo htmlspecialchars($row["Option_Text"]) . "<br>";

    echo "Skill: " . htmlspecialchars($row["Nama_Skill"]) . "<br>";
    echo "Bobot: " . $row["Weight"] . "<br><hr>";
}

$conn->close();
?>