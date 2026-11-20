
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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: profil.php");
    exit;
}

$id_user = (int) $_SESSION["ID_User"];

// ==========================================
// 2. VALIDASI TOKEN CSRF
// ==========================================

$csrf_token = $_POST["csrf_token"] ?? "";

if (
    empty($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $csrf_token)
) {
    die("Permintaan tidak valid. Silakan muat ulang halaman profil.");
}

// ==========================================
// 3. AMBIL DATA FORM
// ==========================================

$nama = trim($_POST["nama"] ?? "");
$email = trim($_POST["email"] ?? "");
$tentang_saya = trim($_POST["tentang_saya"] ?? "");

// ==========================================
// 4. VALIDASI INPUT
// ==========================================

if ($nama === "" || $email === "") {
    header("Location: profil.php?status=empty");
    exit;
}

if (mb_strlen($nama) > 100) {
    header("Location: profil.php?status=invalid_name");
    exit;
}

if (
    strlen($email) > 100 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL)
) {
    header("Location: profil.php?status=invalid_email");
    exit;
}

if (mb_strlen($tentang_saya) > 1000) {
    header("Location: profil.php?status=invalid_about");
    exit;
}

// ==========================================
// 5. PERSIAPAN UPLOAD FOTO
// ==========================================

$upload_dir = __DIR__ . "/uploads/profile/";

if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        header("Location: profil.php?status=upload_error");
        exit;
    }
}

$foto_lama = null;
$foto_baru = null;
$foto_baru_diunggah = false;

// ==========================================
// 6. PROSES UPDATE DATABASE DAN FOTO
// ==========================================

try {

    // Ambil foto profil lama

    $stmt = $conn->prepare("
        SELECT Foto_Profil
        FROM T_User
        WHERE ID_User = :id_user
    ");

    $stmt->execute([
        ":id_user" => $id_user
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("Data pengguna tidak ditemukan.");
    }

    $foto_lama = $user["Foto_Profil"] ?? null;
    $foto_baru = $foto_lama;

    // ======================================
    // PROSES UPLOAD FOTO
    // ======================================

    if (
        isset($_FILES["foto_profil"]) &&
        $_FILES["foto_profil"]["error"] !== UPLOAD_ERR_NO_FILE
    ) {

        $file = $_FILES["foto_profil"];

        if ($file["error"] !== UPLOAD_ERR_OK) {
            header("Location: profil.php?status=upload_error");
            exit;
        }

        // Batas ukuran 2 MB

        if ($file["size"] > 2 * 1024 * 1024) {
            header("Location: profil.php?status=file_too_large");
            exit;
        }

        // Periksa tipe file

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($file["tmp_name"]);

        $allowed_types = [
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/webp" => "webp"
        ];

        if (!isset($allowed_types[$mime_type])) {
            header("Location: profil.php?status=invalid_file");
            exit;
        }

        // Pastikan file benar-benar gambar

        if (getimagesize($file["tmp_name"]) === false) {
            header("Location: profil.php?status=invalid_file");
            exit;
        }

        // Buat nama file acak

        $extension = $allowed_types[$mime_type];
        $nama_file = bin2hex(random_bytes(16)) . "." . $extension;
        $target_file = $upload_dir . $nama_file;

        if (!move_uploaded_file($file["tmp_name"], $target_file)) {
            header("Location: profil.php?status=upload_error");
            exit;
        }

        $foto_baru = $nama_file;
        $foto_baru_diunggah = true;
    }

    // ======================================
    // 7. MULAI TRANSAKSI DATABASE
    // ======================================

    $conn->beginTransaction();

    // ======================================
    // UPDATE DATA T_USER
    // ======================================

    $stmt = $conn->prepare("
        UPDATE T_User
        SET
            Nama = :nama,
            Email = :email,
            Foto_Profil = :foto,
            Tentang_Saya = :tentang_saya
        WHERE ID_User = :id_user
    ");

    $stmt->execute([
        ":nama" => $nama,
        ":email" => $email,
        ":foto" => $foto_baru,
        ":tentang_saya" => $tentang_saya,
        ":id_user" => $id_user
    ]);

    // ======================================
    // 8. SIMPAN PERUBAHAN
    // ======================================

    $conn->commit();

    // ======================================
    // 9. HAPUS FOTO LAMA
    // ======================================

    if (
        !empty($foto_lama) &&
        $foto_baru !== $foto_lama
    ) {

        $old_file = $upload_dir . basename($foto_lama);

        if (is_file($old_file)) {
            unlink($old_file);
        }
    }

    // ======================================
    // 10. PERBARUI SESSION
    // ======================================

    $_SESSION["Nama"] = $nama;

    // Perbarui token CSRF

    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

    header("Location: profil.php?status=success");
    exit;

} catch (PDOException $e) {

    // Batalkan transaksi jika gagal

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    // Hapus foto baru jika database gagal

    if (
        $foto_baru_diunggah &&
        !empty($foto_baru)
    ) {

        $new_file = $upload_dir . basename($foto_baru);

        if (is_file($new_file)) {
            unlink($new_file);
        }
    }

    // Periksa email duplikat

    if ($e->getCode() === "23000") {
        header("Location: profil.php?status=email_exists");
        exit;
    }

    error_log("Skillora Profil Update: " . $e->getMessage());

    header("Location: profil.php?status=error");
    exit;

} catch (Throwable $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    // Bersihkan foto baru jika proses gagal

    if (
        $foto_baru_diunggah &&
        !empty($foto_baru)
    ) {

        $new_file = $upload_dir . basename($foto_baru);

        if (is_file($new_file)) {
            unlink($new_file);
        }
    }

    error_log("Skillora Profil Update: " . $e->getMessage());

    header("Location: profil.php?status=error");
    exit;
}
