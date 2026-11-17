
<?php
session_start();
require_once __DIR__ . "/../config/database.php";

if (empty($_SESSION["ID_User"])) {
    header("Location: login_form.php");
    exit;
}

$id_user = (int) $_SESSION["ID_User"];
$pesan = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nama = trim($_POST["nama"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $universitas = trim($_POST["universitas"] ?? "");
    $jurusan = trim($_POST["jurusan"] ?? "");
    $semester = trim($_POST["semester"] ?? "");
    $bio = trim($_POST["bio"] ?? "");

    if ($nama === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Nama dan email harus diisi dengan benar.";
    } elseif (
        $semester !== "" &&
        (
            !ctype_digit($semester) ||
            (int)$semester < 1 ||
            (int)$semester > 20
        )
    ) {
        $error = "Semester harus antara 1 sampai 20.";
    } else {

        try {
            $conn->beginTransaction();

            $cek = $conn->prepare("
                SELECT ID_User
                FROM T_User
                WHERE Email = :email
                  AND ID_User <> :id_user
                LIMIT 1
            ");

            $cek->execute([
                ":email" => $email,
                ":id_user" => $id_user
            ]);

            if ($cek->fetch()) {
                throw new Exception(
                    "Email sudah digunakan akun lain."
                );
            }

            $update = $conn->prepare("
                UPDATE T_User
                SET Nama = :nama,
                    Email = :email
                WHERE ID_User = :id_user
            ");

            $update->execute([
                ":nama" => $nama,
                ":email" => $email,
                ":id_user" => $id_user
            ]);

            $profile = $conn->prepare("
                INSERT INTO T_Profile
                (
                    ID_User,
                    Universitas,
                    Jurusan,
                    Semester,
                    Bio
                )
                VALUES
                (
                    :id_user,
                    :universitas,
                    :jurusan,
                    :semester,
                    :bio
                )
                ON DUPLICATE KEY UPDATE
                    Universitas = VALUES(Universitas),
                    Jurusan = VALUES(Jurusan),
                    Semester = VALUES(Semester),
                    Bio = VALUES(Bio)
            ");

            $profile->execute([
                ":id_user" => $id_user,
                ":universitas" => $universitas ?: null,
                ":jurusan" => $jurusan ?: null,
                ":semester" => $semester !== ""
                    ? (int)$semester
                    : null,
                ":bio" => $bio ?: null
            ]);

            $conn->commit();

            $_SESSION["Nama"] = $nama;

            $pesan = "Profil berhasil diperbarui.";

        } catch (Throwable $e) {

            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            $error = $e->getMessage();
        }
    }
}

$stmt = $conn->prepare("
    SELECT
        u.Nama,
        u.Email,
        p.Universitas,
        p.Jurusan,
        p.Semester,
        p.Bio

    FROM T_User u

    LEFT JOIN T_Profile p
        ON p.ID_User = u.ID_User

    WHERE u.ID_User = :id_user
");

$stmt->execute([
    ":id_user" => $id_user
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Profil Saya | Skillora</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 25px;
    background: #f5faf8;
    font-family: "Segoe UI", Arial, sans-serif;
    color: #30443f;
}

.container {
    width: min(100%, 760px);
    margin: 25px auto;
}

.header {
    padding: 27px;
    color: white;
    background: linear-gradient(120deg,#176e69,#269b85);
    border-radius: 18px;
}

.header h1 {
    margin: 5px 0;
}

.header p {
    margin: 0;
    font-size: 13px;
    color: #e1f4ec;
}

.card {
    margin-top: 20px;
    padding: 27px;
    background: white;
    border: 1px solid #e4eeea;
    border-radius: 18px;
}

.grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.field {
    margin-bottom: 15px;
}

.field.full {
    grid-column: 1 / -1;
}

label {
    display: block;
    margin-bottom: 7px;
    font-size: 12px;
    font-weight: 700;
}

input,
textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #dce9e3;
    border-radius: 9px;
    outline: none;
    font: inherit;
    font-size: 13px;
}

input:focus,
textarea:focus {
    border-color: #208b83;
}

textarea {
    min-height: 100px;
    resize: vertical;
}

button,
.btn {
    display: inline-block;
    padding: 12px 18px;
    border: 0;
    border-radius: 9px;
    color: white;
    background: #208b83;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
}

.btn.secondary {
    color: #176e69;
    background: #e8f5ef;
}

.message {
    padding: 12px;
    margin-bottom: 16px;
    background: #e8f5ef;
    color: #176e69;
    border-radius: 9px;
    font-size: 13px;
}

.error {
    background: #fff0ed;
    color: #ad4c40;
}

.actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 10px;
}

@media(max-width:550px) {
    body {
        padding: 13px;
    }

    .grid {
        grid-template-columns: 1fr;
    }

    .field.full {
        grid-column: auto;
    }

    .card {
        padding: 19px;
    }
}
</style>
</head>

<body>

<main class="container">

    <header class="header">
        <small>AKUN SKILLORA</small>
        <h1>Profil Saya</h1>
        <p>
            Kelola informasi pribadi dan pendidikanmu.
        </p>
    </header>

    <section class="card">

        <?php if ($pesan): ?>
            <div class="message"><?= e($pesan) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST">

            <div class="grid">

                <div class="field">
                    <label>Nama Lengkap</label>
                    <input
                        type="text"
                        name="nama"
                        value="<?= e($user["Nama"]) ?>"
                        maxlength="100"
                        required
                    >
                </div>

                <div class="field">
                    <label>Email</label>
                    <input
                        type="email"
                        name="email"
                        value="<?= e($user["Email"]) ?>"
                        maxlength="100"
                        required
                    >
                </div>

                <div class="field">
                    <label>Universitas</label>
                    <input
                        type="text"
                        name="universitas"
                        value="<?= e($user["Universitas"] ?? "") ?>"
                        maxlength="150"
                    >
                </div>

                <div class="field">
                    <label>Program Studi</label>
                    <input
                        type="text"
                        name="jurusan"
                        value="<?= e($user["Jurusan"] ?? "") ?>"
                        maxlength="150"
                    >
                </div>

                <div class="field">
                    <label>Semester</label>
                    <input
                        type="number"
                        name="semester"
                        min="1"
                        max="20"
                        value="<?= e($user["Semester"] ?? "") ?>"
                    >
                </div>

                <div class="field full">
                    <label>Tentang Saya</label>
                    <textarea
                        name="bio"
                        maxlength="2000"
                        placeholder="Ceritakan minat dan tujuan kariermu..."
                    ><?= e($user["Bio"] ?? "") ?></textarea>
                </div>

            </div>

            <div class="actions">

                <button type="submit">
                    Simpan Profil
                </button>

                <a href="../index.php" class="btn secondary">
                    Kembali
                </a>

            </div>

        </form>

    </section>

</main>

</body>
</html>
