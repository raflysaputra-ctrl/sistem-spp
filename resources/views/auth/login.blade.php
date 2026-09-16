<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Petugas TU</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { font-family: Inter, ui-sans-serif, system-ui, sans-serif; color: #191c1e; background: #f7f9fb; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; background: #f7f9fb; }
        .login-shell { display: grid; grid-template-columns: minmax(18rem, .9fr) minmax(22rem, 1.1fr); min-height: 100vh; }
        .login-intro { position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 3rem; background: #00288e; color: #fff; }
        .brand { position: absolute; top: 2rem; left: 2rem; display: flex; align-items: center; gap: .75rem; font-size: .75rem; font-weight: 700; letter-spacing: .04em; }
        .brand-mark { display: grid; width: 2.5rem; height: 2.5rem; place-items: center; border: 1px solid rgb(255 255 255 / 45%); border-radius: .25rem; font-size: .75rem; letter-spacing: .08em; }
        .intro-copy { width: min(100%, 29rem); text-align: left; }
        .school-logo { display: block; width: 7rem; height: 7rem; object-fit: contain; }
        .intro-copy p { margin: 0 0 .75rem; color: #a8b8ff; font-size: .75rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        .intro-copy h1 { max-width: 24rem; margin: 0; font-size: clamp(2rem, 4vw, 3rem); line-height: 1.2; letter-spacing: -.02em; }
        .intro-copy span { display: block; margin-top: 1rem; color: #dde1ff; font-size: .95rem; line-height: 1.6; }
        .login-panel { display: grid; min-height: 100%; place-items: center; padding: 2rem; background: #f7f9fb; }
        .login-card { width: min(100%, 25rem); padding: 2rem; border: 1px solid #c4c5d5; border-radius: .5rem; background: #fff; box-shadow: 0 4px 6px rgb(25 28 30 / 4%); }
        .login-card h2 { margin: 0; color: #191c1e; font-size: 1.5rem; letter-spacing: -.01em; text-align: center; }
        .login-card > p { margin: .5rem 0 1.75rem; color: #444653; font-size: .875rem; line-height: 1.5; text-align: center; }
        .field { margin-top: 1rem; }
        label { display: block; margin-bottom: .4rem; color: #444653; font-size: .75rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
        input { width: 100%; padding: .7rem .75rem; border: 1px solid #c4c5d5; border-radius: .25rem; background: #fff; color: #191c1e; font: inherit; font-size: .9rem; }
        input:focus { outline: 0; border-color: #00288e; box-shadow: 0 0 0 2px rgb(0 40 142 / 18%); }
        .error { margin: .45rem 0 0; color: #ba1a1a; font-size: .8rem; line-height: 1.4; }
        .submit-button { width: 100%; margin-top: 1.5rem; padding: .75rem 1rem; border: 0; border-radius: .25rem; background: #00288e; color: #fff; cursor: pointer; font: inherit; font-size: .75rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
        .submit-button:hover { background: #1e40af; }
        @media (max-width: 720px) { .login-shell { display: block; } .login-intro { min-height: 13rem; padding: 1.5rem; } .brand { top: 1rem; left: 1rem; font-size: .65rem; } .school-logo { width: 4rem; height: 4rem; } .intro-copy { margin-top: 2rem; } .intro-copy h1 { font-size: 1.7rem; } .intro-copy span { display: none; } .login-panel { min-height: auto; padding: 1.5rem 1rem; } .login-card { padding: 1.5rem; } }
    </style>
</head>
<body>
    <main class="login-shell">
        <section class="login-intro">
            <div class="brand">
                <img class="school-logo" src="{{ asset('images/cbi.png') }}" alt="Logo SMK Informatika CBI">
                <span>SMK Informatika CBI</span>
            </div>

            <div class="intro-copy">
                <p>Administrasi Sekolah</p>
                <h1>Pembayaran&nbsp;SPP<br>SMK Informatika CBI</h1>
                <span>Masuk untuk mengelola data pembayaran SPP sekolah.</span>
            </div>
        </section>

        <section class="login-panel" aria-labelledby="login-heading">
            <div class="login-card">
                <h2 id="login-heading">Login Petugas TU</h2>
                <p>Gunakan akun petugas yang telah terdaftar.</p>

                <form method="POST" action="{{ route('login.attempt') }}">
                    @csrf

                    <div class="field">
                        <label for="username">Username</label>
                        <input id="username" name="username" type="text" value="{{ old('username') }}" autocomplete="username" required autofocus>
                        @error('username')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" required>
                        @error('password')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>

                    <button class="submit-button" type="submit">Masuk</button>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
