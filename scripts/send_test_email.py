"""
Send a test HTML email using SMTP (e.g., Mailtrap, Gmail SMTP).

Usage:
  python scripts/send_test_email.py --to someone@example.com \
      --subject "Uji Pengingat Kas" \
      --html template-reminder

Available templates: template-reminder, template-verification.
Reads SMTP creds from env or flags.
"""
import argparse
import os
import smtplib
from email.message import EmailMessage

REMINDER_HTML = """
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#0f172a;font-family:'Inter',Arial,sans-serif;color:#0f172a;">
  <div style="max-width:640px;margin:28px auto;padding:2px;background:linear-gradient(135deg,#2563eb,#0ea5e9,#22c55e);border-radius:18px;box-shadow:0 16px 48px rgba(15,23,42,0.25);">
    <div style="background:#ffffff;border-radius:16px;overflow:hidden;">
      <div style="background:linear-gradient(135deg,#0b1224 0%,#111827 60%,#1f2937 100%);padding:24px;color:#e5e7eb;">
        <p style="margin:0;font-size:12px;text-transform:uppercase;letter-spacing:0.3px;color:#93c5fd;">Kas Reminder</p>
        <p style="margin:6px 0 0;font-size:22px;font-weight:800;">Uji Pengingat Kas</p>
        <p style="margin:6px 0 0;opacity:0.82;font-size:14px;">Halo, berikut email uji pengingat kas.</p>
      </div>
      <div style="padding:26px 24px;">
        <p style="margin:0 0 16px 0;line-height:1.65;color:#1f2937;font-size:15px;">Ini hanya email pengujian untuk memastikan pengiriman HTML berjalan baik.</p>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin:14px 0 18px;">
          <div style="border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px;background:#f8fafc;">
            <h4 style="margin:0 0 6px 0;font-size:13px;letter-spacing:0.2px;color:#475569;text-transform:uppercase;">Status</h4>
            <p style="margin:0;color:#0f172a;font-size:15px;font-weight:600;">Email uji terkirim</p>
          </div>
          <div style="border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px;background:#f8fafc;">
            <h4 style="margin:0 0 6px 0;font-size:13px;letter-spacing:0.2px;color:#475569;text-transform:uppercase;">Aksi</h4>
            <p style="margin:0;color:#0f172a;font-size:15px;font-weight:600;">Tidak perlu aksi</p>
          </div>
        </div>
        <a href="#" style="display:inline-flex;align-items:center;gap:10px;padding:13px 18px;background:#2563eb;color:#ffffff;text-decoration:none;border-radius:12px;font-weight:700;box-shadow:0 12px 30px rgba(37,99,235,0.35);">Buka Dashboard <small style="font-weight:500;opacity:0.9;">(contoh)</small></a>
        <div style="height:1px;background:#e2e8f0;margin:22px 0 18px;"></div>
        <p style="font-size:13px;color:#6b7280;margin-top:10px;">Jika tombol tidak berfungsi, abaikan karena ini hanya email uji.</p>
      </div>
      <div style="padding:18px 24px 22px;background:#0b1224;color:#cbd5e1;font-size:13px;border-radius:0 0 16px 16px;">
        <p style="margin:0 0 6px 0;"><strong style="color:#ffffff;">Kontak perusahaan</strong></p>
        <p style="margin:2px 0;">Email: no-reply@kas.local</p>
        <p style="margin:2px 0;">WhatsApp/Telp: -</p>
        <p style="margin:10px 0 0 0;opacity:0.8;">Pesan otomatis. Abaikan email uji ini.</p>
      </div>
    </div>
  </div>
</body>
</html>
"""

VERIFICATION_HTML = """
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#0f172a;font-family:'Inter','Segoe UI',Arial,sans-serif;color:#0f172a;">
  <div style="max-width:560px;margin:28px auto;padding:2px;background:linear-gradient(130deg,#2563eb,#7c3aed,#22c55e);border-radius:18px;box-shadow:0 16px 48px rgba(15,23,42,0.28);">
    <div style="background:#ffffff;border-radius:16px;overflow:hidden;">
      <div style="background:linear-gradient(120deg,#0b1224 0%,#111827 60%,#1f2937 100%);padding:22px 24px;color:#e5e7eb;">
        <h1 style="margin:0;font-size:21px;font-weight:800;">Verifikasi Email Kas</h1>
        <p style="margin:8px 0 0;opacity:0.85;">Kode uji: 123456</p>
      </div>
      <div style="padding:26px 24px;">
        <p style="margin:0 0 12px 0;">Masukkan kode berikut di aplikasi (contoh uji):</p>
        <div style="font-size:30px;letter-spacing:7px;font-weight:800;background:#0f172a;color:#ffffff;padding:14px 18px;border-radius:14px;display:inline-block;box-shadow:0 12px 32px rgba(15,23,42,0.3);">123456</div>
        <ul style="margin:18px 0 6px;padding:0;list-style:none;">
          <li style="margin:0 0 10px 0;padding:10px 12px;border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0;font-size:14px;color:#0f172a;">Buka aplikasi Kas.</li>
          <li style="margin:0 0 10px 0;padding:10px 12px;border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0;font-size:14px;color:#0f172a;">Masuk ke halaman verifikasi email.</li>
          <li style="margin:0 0 10px 0;padding:10px 12px;border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0;font-size:14px;color:#0f172a;">Tempelkan kode lalu kirim.</li>
        </ul>
        <p style="margin-top:16px;color:#475569;font-size:13px;line-height:1.6;">Jika Anda tidak meminta kode, abaikan email ini.</p>
      </div>
      <div style="padding:18px 24px 22px;background:#0b1224;color:#cbd5e1;font-size:13px;border-radius:0 0 16px 16px;">
        <p style="margin:0 0 6px 0;"><strong style="color:#ffffff;">Kontak perusahaan</strong></p>
        <p style="margin:2px 0;">Email: no-reply@kas.local</p>
        <p style="margin:2px 0;">WhatsApp/Telp: -</p>
        <p style="margin:10px 0 0 0;opacity:0.8;">Pesan otomatis. Jangan balas.</p>
      </div>
    </div>
  </div>
</body>
</html>
"""

def build_message(subject: str, sender: str, recipient: str, html_body: str) -> EmailMessage:
    msg = EmailMessage()
    msg["Subject"] = subject
    msg["From"] = sender
    msg["To"] = recipient
    msg.set_content("Email ini memerlukan klien yang mendukung HTML.")
    msg.add_alternative(html_body, subtype="html")
    return msg


def send_email(host: str, port: int, username: str | None, password: str | None, sender: str, recipient: str, msg: EmailMessage, use_tls: bool = True) -> None:
    with smtplib.SMTP(host, port, timeout=15) as smtp:
        if use_tls:
            smtp.starttls()
        if username and password:
            smtp.login(username, password)
        smtp.send_message(msg)


def main() -> None:
    parser = argparse.ArgumentParser(description="Send test HTML email")
    parser.add_argument("--to", required=True, help="Recipient email")
    parser.add_argument("--subject", required=True, help="Email subject")
    parser.add_argument("--html", choices=["template-reminder", "template-verification"], default="template-reminder", help="Which template to send")
    parser.add_argument("--host", default=os.getenv("MAIL_HOST", "127.0.0.1"), help="SMTP host")
    parser.add_argument("--port", type=int, default=int(os.getenv("MAIL_PORT", "2525")), help="SMTP port")
    parser.add_argument("--user", default=os.getenv("MAIL_USERNAME"), help="SMTP username")
    parser.add_argument("--password", default=os.getenv("MAIL_PASSWORD"), help="SMTP password")
    parser.add_argument("--from", dest="sender", default=os.getenv("MAIL_FROM_ADDRESS", "no-reply@kas.local"), help="Sender email")
    parser.add_argument("--no-tls", dest="no_tls", action="store_true", help="Disable STARTTLS")
    args = parser.parse_args()

    html_body = REMINDER_HTML if args.html == "template-reminder" else VERIFICATION_HTML
    msg = build_message(args.subject, args.sender, args.to, html_body)

    send_email(
        host=args.host,
        port=args.port,
        username=args.user,
        password=args.password,
        sender=args.sender,
        recipient=args.to,
        msg=msg,
        use_tls=not args.no_tls,
    )

    print(f"Email terkirim ke {args.to} dengan template {args.html}")


if __name__ == "__main__":
    main()
