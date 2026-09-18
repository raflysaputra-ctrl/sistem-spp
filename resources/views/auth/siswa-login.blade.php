<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Siswa | Sistem Pembayaran SPP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { font-family: Inter, ui-sans-serif, system-ui, sans-serif; color: #191c1e; background: #f7f9fb; }
        * { box-sizing: border-box; } body { display: grid; min-height: 100vh; margin: 0; place-items: center; padding: 1.5rem; }
        main { width: min(100%, 27rem); padding: 2rem; border: 1px solid #c4c5d5; border-radius: .5rem; background: #fff; box-shadow: 0 4px 6px rgb(25 28 30 / 4%); }
        .eyebrow { margin: 0; color: #505f76; font-size: .72rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; } h1 { margin: .4rem 0; color: #00288e; font-size: 1.5rem; } p { color: #444653; line-height: 1.55; }
        label { display: block; margin: 1rem 0 .4rem; color: #444653; font-size: .72rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
        input { width: 100%; min-height: 2.7rem; padding: .65rem .7rem; border: 1px solid #c4c5d5; border-radius: .25rem; font: inherit; } input:focus { outline: 0; border-color: #00288e; box-shadow: 0 0 0 2px rgb(0 40 142 / 18%); }
        button { width: 100%; margin-top: 1.5rem; padding: .75rem; border: 0; border-radius: .25rem; background: #00288e; color: #fff; cursor: pointer; font: inherit; font-size: .75rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; } .error { margin: .4rem 0 0; color: #ba1a1a; font-size: .8rem; }
    </style>
</head>
<body>
    <main>
        <p class="eyebrow">Sistem Pembayaran SPP</p>
        <h1>Portal Siswa</h1>
        <p>Lihat status SPP dan unggah foto kwitansi fisik milik Anda.</p>
        <form method="POST" action="{{ route('siswa.login.attempt') }}">
            @csrf
            <label for="username">Username</label>
            <input id="username" name="username" type="text" value="{{ old('username') }}" autocomplete="username" required autofocus>
            @error('username')<p class="error">{{ $message }}</p>@enderror
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
            @error('password')<p class="error">{{ $message }}</p>@enderror
            <button type="submit">Masuk</button>
        </form>
    </main>
</body>
</html>
