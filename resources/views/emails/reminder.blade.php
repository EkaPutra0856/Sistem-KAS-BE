<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { margin:0; padding:0; background:#0f172a; font-family:'Inter', 'Segoe UI', Arial, sans-serif; color:#0f172a; }
        .shell { max-width:640px; margin:28px auto; padding:2px; background:linear-gradient(135deg, #2563eb, #0ea5e9, #22c55e); border-radius:18px; box-shadow:0 16px 48px rgba(15,23,42,0.25); }
        .wrapper { background:#ffffff; border-radius:16px; overflow:hidden; }
        .header { background:linear-gradient(135deg, #0b1224 0%, #111827 60%, #1f2937 100%); padding:24px; color:#e5e7eb; }
        .brand { display:flex; align-items:center; gap:10px; }
        .badge { background:rgba(37,99,235,0.16); color:#93c5fd; padding:6px 10px; border-radius:999px; font-size:12px; letter-spacing:0.3px; text-transform:uppercase; }
        .title { font-size:22px; font-weight:800; margin:6px 0 0; }
        .subtitle { margin:6px 0 0; opacity:0.82; font-size:14px; }
        .content { padding:26px 24px; }
        .body-text { margin:0 0 16px 0; line-height:1.65; color:#1f2937; font-size:15px; }
        .info-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:12px; margin:14px 0 18px; }
        .card { border:1px solid #e2e8f0; border-radius:12px; padding:14px 16px; background:#f8fafc; }
        .card h4 { margin:0 0 6px 0; font-size:13px; letter-spacing:0.2px; color:#475569; text-transform:uppercase; }
        .card p { margin:0; color:#0f172a; font-size:15px; font-weight:600; }
        .cta { display:inline-flex; align-items:center; gap:10px; padding:13px 18px; background:#2563eb; color:#ffffff; text-decoration:none; border-radius:12px; font-weight:700; box-shadow:0 12px 30px rgba(37,99,235,0.35); }
        .cta small { font-weight:500; opacity:0.9; }
        .divider { height:1px; background:#e2e8f0; margin:22px 0 18px; }
        .meta { font-size:13px; color:#6b7280; margin-top:10px; }
        .footer { padding:18px 24px 22px; background:#0b1224; color:#cbd5e1; font-size:13px; border-radius:0 0 16px 16px; }
        .footer strong { color:#ffffff; }
    </style>
</head>
<body>
    <div class="shell">
        <div class="wrapper">
            <div class="header">
                <div class="brand">
                    <span class="badge">Kas Reminder</span>
                </div>
                <p class="title">{{ $title }}</p>
                <p class="subtitle">Halo {{ $user->name }}, berikut pengingat terbaru untuk setoran kas.</p>
            </div>
            <div class="content">
                <p class="body-text">{{ $bodyText }}</p>
                <div class="info-grid">
                    <div class="card">
                        <h4>Status</h4>
                        <p>Dikirim {{ now()->format('d M Y, H:i') }}</p>
                    </div>
                    <div class="card">
                        <h4>Dashboard</h4>
                        <p>{{ config('app.url') }}</p>
                    </div>
                </div>
                <a class="cta" href="{{ config('app.url') ?? '#' }}" target="_blank" rel="noopener">
                    Buka Dashboard <small>Kelola pembayaran</small>
                </a>
                <div class="divider"></div>
                <p class="meta">Jika tombol tidak berfungsi, salin alamat berikut ke browser: {{ config('app.url') }}</p>
            </div>
            <div class="footer">
                <p style="margin:0 0 6px 0;"><strong>Kontak perusahaan</strong></p>
                @if($companyContact)
                    <p style="margin:2px 0;">Email: {{ $companyContact->email }}</p>
                    <p style="margin:2px 0;">WhatsApp/Telp: {{ $companyContact->phone ?: '-' }}</p>
                @else
                    <p style="margin:2px 0;">Email: {{ config('mail.from.address') }}</p>
                @endif
                <p style="margin:10px 0 0 0; opacity:0.8;">Pesan otomatis. Abaikan jika sudah melakukan pembayaran.</p>
            </div>
        </div>
    </div>
</body>
</html>
