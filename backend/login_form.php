<!DOCTYPE html>
<html lang="id">
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk | Skillora</title>
    <link rel="stylesheet" href="../assets/css/global.css">
<link rel="stylesheet" href="../assets/css/login.css">
</head>


<body>
    <main class="container">
        <section class="illustration">
            <a class="brand" href="../landing.php">Skillora</a>

            <div class="illustration-content">
                <div class="illustration-icon"></div>

                <h1>Kenali Potensimu,<br>Raih Masa Depanmu</h1>

                <p>
                    Temukan potensi diri dan jelajahi pilihan karier
                    yang sesuai dengan keterampilanmu.
                </p>
            </div>

            <small>
                Mulai langkah kecil menuju masa depan yang lebih baik.
            </small>
        </section>

        <section class="form-panel">
            <h2>Selamat Datang!</h2>

            <p class="subtitle">
                Masuk ke akun Skillora untuk melanjutkan perjalananmu.
            </p>

            <?php if (
                isset($_GET["registered"]) &&
                $_GET["registered"] === "1"
            ): ?>
                <div class="notice">
                    Registrasi berhasil! Silakan masuk menggunakan akunmu.
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
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
                        placeholder="Masukkan password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <div class="options">
                    <label class="remember">
                        <input type="checkbox" name="remember">
                        Ingat saya
                    </label>

                    <a href="#" onclick="return false;">
                        Lupa password?
                    </a>
                </div>

                <button type="submit" class="btn">
                    Masuk ke Skillora
                </button>
            </form>
            <form action="login.php" method="POST">
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
                        placeholder="Masukkan password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <div class="options">
                    <label class="remember">
                        <input type="checkbox" name="remember">
                        Ingat saya
                    </label>

                    <a href="#" onclick="return false;">
                        Lupa password?
                    </a>
                </div>

                <button type="submit" class="btn">
                    Masuk ke Skillora
                </button>
            </form>

            <p class="bottom-text">
                Belum punya akun?
                <a href="register_form.php">
                    <strong>Daftar sekarang</strong>
                </a>
            </p>
        </section>
    </main>
            <p class="bottom-text">
                Belum punya akun?
                <a href="register_form.php">
                    <strong>Daftar sekarang</strong>
                </a>
            </p>
        </section>
    </main>
</body>
</html>

