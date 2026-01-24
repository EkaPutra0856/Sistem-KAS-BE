<?php

namespace App\Mail;

use App\Models\CompanyContact;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $code;
    public User $user;
    public ?CompanyContact $companyContact;

    public function __construct(string $code, User $user, ?CompanyContact $companyContact)
    {
        $this->code = $code;
        $this->user = $user;
        $this->companyContact = $companyContact;
    }

    public function build(): self
    {
        return $this
            ->subject('Verifikasi Email Kas')
            ->view('emails.verification');
    }
}
