<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>503 | Situs Sedang Maintenance</title>
  <link href="https://fonts.googleapis.com/css2?family=Lexend+Deca:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    /* 🎨 Warna & Font */
    :root {
      --primary: #2563eb;
      --secondary: #1e40af;
      --accent: #facc15;
      --bg: radial-gradient(circle at 30% 30%, #e0f2fe 0%, #eff6ff 100%);
      --text: #1f2937;
      --muted: #6b7280;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Lexend Deca', sans-serif;
    }

    body {
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--bg);
      overflow: hidden;
      color: var(--text);
    }

    /* 🌟 Wrapper */
    .wrapper {
      text-align: center;
      max-width: 600px;
      padding: 2rem;
      border-radius: 20px;
      background: rgba(255, 255, 255, 0.6);
      backdrop-filter: blur(10px);
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
      animation: fadeIn 1.2s ease;
    }

    /* 3D Text Effect */
    .error-3d {
      position: relative;
      font-size: 6rem;
      font-weight: 800;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      -webkit-background-clip: text;
      color: transparent;
      letter-spacing: 3px;
      text-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
      animation: float 3s ease-in-out infinite;
    }

    /* 🧱 Kotak 3D Inventory */
    .inventory-box {
      position: relative;
      width: 90px;
      height: 90px;
      margin: 25px auto 10px;
      transform: rotateX(20deg) rotateY(30deg);
      transform-style: preserve-3d;
      animation: rotateBox 10s linear infinite;
    }

    .inventory-box div {
      position: absolute;
      width: 90px;
      height: 90px;
      background: linear-gradient(135deg, #facc15, #f59e0b);
      border: 2px solid #d97706;
      opacity: 0.95;
      box-shadow: 0 0 10px rgba(245, 158, 11, 0.3);
    }

    .front  { transform: translateZ(45px); }
    .back   { transform: rotateY(180deg) translateZ(45px); }
    .right  { transform: rotateY(90deg) translateZ(45px); }
    .left   { transform: rotateY(-90deg) translateZ(45px); }
    .top    { transform: rotateX(90deg) translateZ(45px); }
    .bottom { transform: rotateX(-90deg) translateZ(45px); }

    /* 🧭 Teks */
    h1 {
      font-size: 1.7rem;
      margin-top: 0.75rem;
      color: var(--secondary);
    }

    p {
      color: var(--muted);
      margin-top: 0.75rem;
      line-height: 1.6;
      font-size: 1rem;
    }

    /* ⏳ Spinner */
    .spinner {
      width: 40px;
      height: 40px;
      border: 3px solid #e5e7eb;
      border-top: 3px solid var(--primary);
      border-radius: 50%;
      margin: 25px auto;
      animation: spin 1s linear infinite;
    }

    /* 📜 Footer */
    footer {
      margin-top: 1.5rem;
      font-size: 0.85rem;
      color: #9ca3af;
    }

    /* ✨ Animations */
    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes float {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-12px); }
    }

    @keyframes rotateBox {
      0% { transform: rotateX(20deg) rotateY(30deg); }
      100% { transform: rotateX(20deg) rotateY(390deg); }
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="error-3d">503</div>

    <div class="inventory-box">
      <div class="front"></div>
      <div class="back"></div>
      <div class="right"></div>
      <div class="left"></div>
      <div class="top"></div>
      <div class="bottom"></div>
    </div>

    <h1>Situs Sedang Maintenance</h1>
    <p>
      Kami sedang melakukan pembaruan sistem inventory agar lebih cepat dan stabil.<br>
      Silakan kembali beberapa saat lagi.
    </p>

    <div class="spinner"></div>

    @if(isset($exception) && $exception->getMessage())
      <p style="margin-top:1rem; font-size:0.9rem; color:#9ca3af;">
        {{ $exception->getMessage() }}
      </p>
    @endif

    <footer>&copy; {{ date('Y') }} — <strong>{{ config('app.name') }}</strong></footer>
  </div>
</body>
</html>
