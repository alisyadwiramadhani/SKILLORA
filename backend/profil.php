
<?php
session_start();

require_once __DIR__ . "/../config/database.php";

// ==========================================
// 1. CEK LOGIN
// ==========================================

if (!isset($_SESSION["ID_User"])) {
    header("Location: login_form.php");
    exit;
}

$id_user = (int) $_SESSION["ID_User"];

// ==========================================
// 2. TOKEN CSRF
// ==========================================

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// ==========================================
// 3. AMBIL DATA PROFIL
// ==========================================

try {
    $stmt = $conn->prepare("
        SELECT
            Nama,
            Email,
            Foto_Profil,
            Tentang_Saya
        FROM T_User
        WHERE ID_User = :id_user
    ");

    $stmt->execute([
        ":id_user" => $id_user
    ]);

    $profil = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profil) {
        die("Data pengguna tidak ditemukan.");
    }

} catch (PDOException $e) {
    error_log("Skillora Profil: " . $e->getMessage());
    die("Gagal mengambil data profil. Periksa struktur tabel T_User.");
}

// ==========================================
// 4. FUNGSI KEAMANAN OUTPUT
// ==========================================

function aman($data) {
    return htmlspecialchars($data ?? "", ENT_QUOTES, "UTF-8");
}

// ==========================================
// 5. STATUS PESAN
// ==========================================

$status = $_GET["status"] ?? "";

$pesan = [
    "success" => ["success", "Profil berhasil diperbarui."],
    "empty" => ["error", "Nama dan email wajib diisi."],
    "invalid_name" => ["error", "Nama maksimal 100 karakter."],
    "invalid_email" => ["error", "Format email tidak valid."],
    "email_exists" => ["error", "Email sudah digunakan oleh akun lain."],
    "invalid_file" => ["error", "Format foto harus JPG, PNG, atau WEBP."],
    "file_too_large" => ["error", "Ukuran foto maksimal 2 MB."],
    "upload_error" => ["error", "Foto gagal diunggah."],
    "invalid_about" => ["error", "Tentang Saya maksimal 1000 karakter."],
    "error" => ["error", "Terjadi kesalahan saat memperbarui profil."]
];

$foto = $profil["Foto_Profil"] ?? "";

$foto_url = !empty($foto)
    ? "uploads/profile/" . rawurlencode(basename($foto))
    : "";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Profil Saya | SKILLORA</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f5faf8;
            color: #263b39;
            padding: 40px 20px;
        }

        .container {
            max-width: 780px;
            margin: auto;
        }

        .header {
            margin-bottom: 25px;
        }

        .header h2 {
            color: #168b83;
            font-size: 27px;
            margin-bottom: 8px;
        }

        .header p {
            color: #71817e;
            font-size: 14px;
        }

        .profile-card {
            background: #ffffff;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.06);
        }

        .profile-title {
            margin-bottom: 25px;
            padding-bottom: 17px;
            border-bottom: 1px solid #e8efed;
        }

        .profile-title h3 {
            font-size: 20px;
            margin-bottom: 7px;
        }

        .profile-title p {
            font-size: 13px;
            color: #82908e;
        }

        .photo-section {
            display: flex;
            align-items: center;
            gap: 22px;
            margin-bottom: 28px;
            padding-bottom: 25px;
            border-bottom: 1px solid #e8efed;
        }

        .photo-preview {
            width: 115px;
            height: 115px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #e0f2ef;
            background: #e8f5f2;
        }

        .photo-placeholder {
            width: 115px;
            height: 115px;
            border-radius: 50%;
            background: #e0f2ef;
            color: #168b83;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
            font-weight: bold;
            border: 4px solid #e0f2ef;
        }

        .photo-info h4 {
            margin-bottom: 8px;
            font-size: 15px;
        }

        .photo-info p {
            font-size: 12px;
            color: #899895;
            margin-bottom: 12px;
            line-height: 1.5;
        }

        .photo-info input {
            max-width: 100%;
            font-size: 12px;
        }

        .form-group {
            margin-bottom: 21px;
        }

        label {
            display: block;
            margin-bottom: 9px;
            font-size: 14px;
            font-weight: bold;
            color: #354b48;
        }

        input[type="text"],
        input[type="email"],
        textarea {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #dce7e4;
            border-radius: 9px;
            font-size: 14px;
            outline: none;
            color: #263b39;
            background: #ffffff;
        }

        input:focus,
        textarea:focus {
            border-color: #168b83;
            box-shadow: 0 0 0 3px rgba(22, 139, 131, 0.10);
        }

        textarea {
            min-height: 145px;
            resize: vertical;
            line-height: 1.6;
        }

        .description {
            font-size: 12px;
            color: #899895;
            margin-top: 7px;
        }

        .alert {
            padding: 13px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .success {
            background: #e5f7ee;
            color: #16784c;
        }

        .error {
            background: #fdeaea;
            color: #bd3636;
        }

        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 27px;
        }

        .btn {
            padding: 13px 22px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
        }

        .btn-save {
            background: #168b83;
            color: white;
        }

        .btn-save:hover {
            background: #11746e;
        }

        .btn-back {
            background: #edf3f1;
            color: #52635f;
        }

        @media (max-width: 600px) {
            body {
                padding: 25px 15px;
            }

            .profile-card {
                padding: 22px;
            }

            .photo-section {
                align-items: flex-start;
                flex-direction: column;
            }

            .button-group {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="header">
        <h2>Profil Saya</h2>
        <p>Kelola informasi pribadi dan perkenalkan dirimu di SKILLORA.</p>
    </div>

    <div class="profile-card">

        <div class="profile-title">
            <h3>Informasi Profil</h3>
            <p>Perbarui foto dan informasi pribadi kamu.</p>
        </div>

        <?php if (isset($pesan[$status])): ?>
            <div class="alert <?= aman($pesan[$status][0]); ?>">
                <?= aman($pesan[$status][1]); ?>
            </div>
        <?php endif; ?>

        <form
            action="profil_update.php"
            method="POST"
            enctype="multipart/form-data"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= aman($_SESSION["csrf_token"]); ?>"
            >

            <!-- FOTO PROFIL -->
            <div class="photo-section">

                <?php if (!empty($foto_url)): ?>

                    <img
                        src="<?= aman($foto_url); ?>?v=<?= file_exists(__DIR__ . "/" . $foto_url) ? filemtime(__DIR__ . "/" . $foto_url) : time(); ?>"
                        alt="Foto Profil"
                        class="photo-preview"
                    >

                <?php else: ?>

                    <div class="photo-placeholder">
                        <?= strtoupper(substr($profil["Nama"], 0, 1)); ?>
                    </div>

                <?php endif; ?>

                <div class="photo-info">
                    <h4>Foto Profil</h4>

                    <p>
                        Pilih foto terbaik untuk profil kamu.
                        Format JPG, PNG, atau WEBP. Maksimal 2 MB.
                    </p>

                    <input
                        type="file"
                        name="foto_profil"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    >
                </div>

            </div>

            <!-- NAMA -->
            <div class="form-group">
                <label for="nama">Nama Lengkap</label>

                <input
                    type="text"
                    id="nama"
                    name="nama"
                    value="<?= aman($profil["Nama"]); ?>"
                    placeholder="Masukkan nama lengkap"
                    maxlength="100"
                    required
                >
            </div>

            <!-- EMAIL -->
            <div class="form-group">
                <label for="email">Email</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= aman($profil["Email"]); ?>"
                    placeholder="Masukkan alamat email"
                    maxlength="100"
                    required
                >
            </div>

            <!-- TENTANG SAYA -->
            <div class="form-group">
                <label for="tentang_saya">Tentang Saya</label>

                <textarea
                    id="tentang_saya"
                    name="tentang_saya"
                    maxlength="1000"
                    placeholder="Ceritakan tentang dirimu, minat, keahlian, pengalaman, atau tujuan belajarmu..."
                ><?= aman($profil["Tentang_Saya"]); ?></textarea>

                <p class="description">
                    Bagikan sedikit informasi tentang dirimu agar lebih mudah dikenal.
                </p>
            </div>

            <div class="button-group">

                <button type="submit" class="btn btn-save">
                    Simpan Perubahan
                </button>

                <a href="../index.php" class="btn btn-back">
                    Kembali
                </a>

            </div>

        </form>

    </div>

</div>

</body>
</html>
