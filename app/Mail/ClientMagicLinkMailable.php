<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ClientMagicLinkMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $link)
    {
    }

    public function build()
    {
        return $this->subject('Your portal login link')
            ->view('emails.client-magic-link')
            ->with([
                'user' => $this->user,
                'link' => $this->link,
                'productName' => config('app.name', 'Renlo'),
            ]);
    }
}
