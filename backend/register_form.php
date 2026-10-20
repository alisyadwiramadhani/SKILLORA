<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar | Skillora</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            min-height: 100vh;
            background: #f3f8f5;
            color: #193b35;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .container {
            width: 100%;
            max-width: 960px;
            min-height: 570px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 15px 45px rgba(26, 74, 61, .10);
        }

        .illustration {
            background: linear-gradient(145deg, #dff3e8, #bce5d2);
            padding: 42px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .brand {
            color: #18785e;
            font-size: 27px;
            font-weight: bold;
            text-decoration: none;
        }

        .brand span {
            color: #173e34;
        }

        .illustration-content {
            text-align: center;
            padding: 25px 5px;
        }

        .illustration-icon {
            font-size: 90px;
            margin-bottom: 20px;
        }

        .illustration h1 {
            font-size: 29px;
            line-height: 1.4;
            margin-bottom: 14px;
        }

        .illustration p {
            color: #547269;
            line-height: 1.8;
            font-size: 14px;
        }

        .illustration small {
            color: #547269;
            text-align: center;
        }

        .form-panel {
            padding: 45px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-panel h2 {
            font-size: 30px;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #7b8c86;
            font-size: 14px;
            line-height: 1.7;
            margin-bottom: 27px;
        }

        .field {
            margin-bottom: 18px;
        }

        .field label {
            display: block;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 9px;
        }

        .field input {
            width: 100%;
            padding: 14px 15px;
            border: 1px solid #dce7e1;
            border-radius: 10px;
            outline: none;
            font-size: 14px;
        }

        .field input:focus {
            border-color: #269b75;
            box-shadow: 0 0 0 3px rgba(38, 155, 117, .12);
        }

        .terms {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            color: #667a72;
            font-size: 13px;
            line-height: 1.6;
            margin: 3px 0 22px;
        }

        .terms input {
            margin-top: 3px;
            flex-shrink: 0;
        }

        a {
            color: #188663;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .btn {
            width: 100%;
            border: none;
            background: #218b69;
            color: white;
            padding: 15px;
            border-radius: 10px;
            font-weight: bold;
            font-size: 15px;
            cursor: pointer;
        }

        .btn:hover {
            background: #176f53;
        }

        .bottom-text {
            text-align: center;
            color: #7b8c86;
            font-size: 14px;
            margin-top: 24px;
        }

        @media (max-width: 700px) {
            .container {
                max-width: 440px;
                grid-template-columns: 1fr;
            }

            .illustration {
                padding: 25px;
            }

            .illustration-content {
                padding: 25px 0 10px;
            }

            .illustration-icon {
                font-size: 60px;
                margin-bottom: 10px;
            }

            .illustration h1 {
                font-size: 23px;
            }

            .illustration small {
                display: none;
            }

            .form-panel {
                padding: 35px 25px;
            }
        }
    </style>
</head>

<body>
    <main class="container">
        <section class="illustration">
            <a class="brand" href="../beranda.php">
                skill<span>ora.</span>
            </a>

            <div class="illustration-content">
                <div class="illustration-icon">🚀</div>

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
                    <input type="checkbox" required>
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
