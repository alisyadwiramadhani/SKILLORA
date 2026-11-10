<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar | Skillora</title>

    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/registrasi.css">
</head>

<body>
    <main class="container">

        <!-- Bagian ilustrasi -->
        <section class="illustration">
            <a class="brand" href="../landing.php">Skillora</a>

            <div class="illustration-content">
                <div class="illustration-icon"></div>

                <h1>Mulai Perjalananmu Bersama Skillora</h1>

                <p>
                    Kenali minat, pahami keterampilan, dan temukan
                    arah karier yang sesuai dengan potensimu.
                </p>
            </div>

            <small>
                Setiap langkah adalah kesempatan untuk berkembang.
            </small>
        </section>

        <!-- Bagian formulir pendaftaran -->
        <section class="form-panel">
            <h2>Buat Akun</h2>

            <p class="subtitle">
                Daftar sekarang dan mulai kenali potensi dirimu.
            </p>

            <form action="register.php" method="POST">

                <div class="field">
                    <label for="nama">Nama Lengkap</label>
                    <input
                        type="text"
                        id="nama"
                        name="nama"
                        placeholder="Masukkan nama lengkap"
                        autocomplete="name"
                        required
                    >
                </div>

                <div class="field">
                    <label for="email">Alamat Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="nama@email.com"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Minimal 8 karakter"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <label class="terms">
                    <input type="checkbox" name="persetujuan" value="1" required>

                    <span>
                        Saya menyetujui ketentuan penggunaan dan kebijakan
                        privasi Skillora.
                    </span>
                </label>

                <button type="submit" class="btn">
                    Buat Akun
                </button>

            </form>

            <p class="bottom-text">
                Sudah punya akun?
                <a href="login_form.php">
                    <strong>Masuk di sini</strong>
                </a>
            </p>

        </section>
    </main>
</body>
</html>
