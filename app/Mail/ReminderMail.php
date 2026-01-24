<?php

namespace App\Mail;

use App\Models\CompanyContact;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $title;
    public string $bodyText;
    public User $user;
    public ?CompanyContact $companyContact;

    public function __construct(string $title, string $bodyText, User $user, ?CompanyContact $companyContact)
    {
        $this->title = $title;
        $this->bodyText = $bodyText;
        $this->user = $user;
        $this->companyContact = $companyContact;
    }

    public function build(): self
    {
        return $this
            ->subject($this->title)
            ->view('emails.reminder');
    }
}
