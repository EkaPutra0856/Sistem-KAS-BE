<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { margin:0; padding:0; background:#0f172a; font-family:'Inter','Segoe UI',Arial,sans-serif; color:#0f172a; }
        .shell { max-width:560px; margin:28px auto; padding:2px; background:linear-gradient(130deg, #2563eb, #7c3aed, #22c55e); border-radius:18px; box-shadow:0 16px 48px rgba(15,23,42,0.28); }
        .container { background:#ffffff; border-radius:16px; overflow:hidden; }
        .hero { background:linear-gradient(120deg, #0b1224 0%, #111827 60%, #1f2937 100%); padding:22px 24px; color:#e5e7eb; }
        .hero h1 { margin:0; font-size:21px; font-weight:800; }
        .hero p { margin:8px 0 0; opacity:0.85; }
        .body { padding:26px 24px; }
        .code { font-size:30px; letter-spacing:7px; font-weight:800; background:#0f172a; color:#ffffff; padding:14px 18px; border-radius:14px; display:inline-block; box-shadow:0 12px 32px rgba(15,23,42,0.3); }
        .meta { margin-top:16px; color:#475569; font-size:13px; line-height:1.6; }
        .steps { margin:18px 0 6px; padding:0; list-style:none; }
        .steps li { margin:0 0 10px 0; padding:10px 12px; border-radius:12px; background:#f8fafc; border:1px solid #e2e8f0; font-size:14px; color:#0f172a; }
        .footer { padding:18px 24px 22px; background:#0b1224; color:#cbd5e1; font-size:13px; border-radius:0 0 16px 16px; }
        .footer strong { color:#ffffff; }
    </style>
</head>
<body>
    <div class="shell">
        <div class="container">
            <div class="hero">
                <h1>Verifikasi Email Kas</h1>
                <p>Hai {{ $user->name }}, masukkan kode berikut untuk menyelesaikan verifikasi.</p>
            </div>
            <div class="body">
                <p style="margin:0 0 12px 0;">Kode verifikasi (berlaku 10 menit):</p>
                <div class="code">{{ $code }}</div>
                <ul class="steps">
                    <li>Buka aplikasi Kas.</li>
                    <li>Masuk ke halaman verifikasi email.</li>
                    <li>Tempelkan kode di atas lalu kirim.</li>
                </ul>
                <p class="meta">Jika Anda tidak merasa mendaftar, abaikan email ini atau hubungi kami.</p>
            </div>
            <div class="footer">
                <p style="margin:0 0 6px 0;"><strong>Kontak perusahaan</strong></p>
                @if($companyContact)
                    <p style="margin:2px 0;">Email: {{ $companyContact->email }}</p>
                    <p style="margin:2px 0;">WhatsApp/Telp: {{ $companyContact->phone ?: '-' }}</p>
                @else
                    <p style="margin:2px 0;">Email: {{ config('mail.from.address') }}</p>
                @endif
                <p style="margin:10px 0 0 0; opacity:0.8;">Pesan otomatis. Jangan balas email ini.</p>
            </div>
        </div>
    </div>
</body>
</html>
