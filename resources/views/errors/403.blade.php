<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>403 | Akses Ditolak</title>
  <link href="https://fonts.googleapis.com/css2?family=Lexend+Deca:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --primary: #dc2626; /* merah tegas */
      --secondary: #991b1b;
      --bg: #f9fafb;
      --text: #1f2937;
      --muted: #6b7280;
      --white: #ffffff;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Lexend Deca', sans-serif !important;
    }

    body {
      background: radial-gradient(circle at top left, #fee2e2, #fef2f2);
      color: var(--text);
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
      overflow: hidden;
      perspective: 1000px;
    }

    .wrapper {
      text-align: center;
      max-width: 600px;
      animation: fadeIn 1s ease;
    }

    .error-3d {
      position: relative;
      font-size: 6rem;
      font-weight: 800;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: transparent;
      -webkit-background-clip: text;
      text-shadow: 0 6px 18px rgba(0, 0, 0, 0.2);
      letter-spacing: 2px;
      animation: float 3s ease-in-out infinite;
    }

    /* 🔒 3D LOCK */
    .lock-3d {
      position: relative;
      width: 80px;
      height: 100px;
      margin: 25px auto 15px;
      transform-style: preserve-3d;
      transform: rotateX(10deg) rotateY(20deg);
      animation: rotateLock 10s linear infinite;
    }

    .lock-body {
      position: absolute;
      width: 80px;
      height: 70px;
      background: linear-gradient(145deg, #ef4444, #b91c1c);
      border: 2px solid #7f1d1d;
      border-radius: 10px;
      transform: translateZ(15px);
      box-shadow: 0 0 25px rgba(239, 68, 68, 0.4);
    }

    .lock-shackle {
      position: absolute;
      width: 50px;
      height: 40px;
      border: 6px solid #991b1b;
      border-bottom: none;
      border-radius: 25px 25px 0 0;
      background: transparent;
      top: -30px;
      left: 14px;
      box-shadow: inset 0 0 8px rgba(255,255,255,0.3);
    }

    h1 {
      font-size: 1.6rem;
      margin-top: 0.5rem;
      color: var(--text);
    }

    p {
      color: var(--muted);
      margin-top: 0.75rem;
      line-height: 1.6;
      font-size: 0.95rem;
    }

    .btn-home {
      display: inline-block;
      margin-top: 1.5rem;
      padding: 10px 22px;
      background: var(--primary);
      color: #fff;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 500;
      transition: background 0.3s ease;
    }

    .btn-home:hover {
      background: var(--secondary);
    }

    footer {
      margin-top: 2rem;
      font-size: 0.85rem;
      color: #9ca3af;
    }

    @keyframes rotateLock {
      0% { transform: rotateX(10deg) rotateY(20deg); }
      100% { transform: rotateX(10deg) rotateY(380deg); }
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes float {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-10px); }
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="error-3d">403</div>

    <div class="lock-3d">
      <svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 93.63 122.88"><defs><style>.cls-1{fill:#fbd734;}.cls-1,.cls-2{fill-rule:evenodd;}.cls-2{fill:#36464e;}</style></defs><title>padlock</title><path class="cls-1" d="M6,47.51H87.64a6,6,0,0,1,6,6v63.38a6,6,0,0,1-6,6H6a6,6,0,0,1-6-6V53.5a6,6,0,0,1,6-6Z"/><path class="cls-2" d="M41.89,89.26l-6.47,16.95H58.21L52.21,89a11.79,11.79,0,1,0-10.32.24Z"/><path class="cls-2" d="M83.57,47.51H72.22V38.09a27.32,27.32,0,0,0-7.54-19,24.4,24.4,0,0,0-35.73,0,27.32,27.32,0,0,0-7.54,19v9.42H10.06V38.09A38.73,38.73,0,0,1,20.78,11.28a35.69,35.69,0,0,1,52.07,0A38.67,38.67,0,0,1,83.57,38.09v9.42Z"/></svg>
    </div>

    <h1>Akses Ditolak</h1>
    <p>
      Anda tidak memiliki izin untuk mengakses halaman ini.<br>
      Silakan hubungi administrator atau kembali ke halaman utama.
    </p>

    <a href="/" class="btn-home">Kembali ke Dashboard</a>

    <footer>&copy; {{ date('Y') }} — <strong>{{ config('app.name') }}</strong></footer>
  </div>
</body>
</html>
