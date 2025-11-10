<?php

// app/Mail/LoggedEmail.php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoggedEmail extends Mailable
{
  use Queueable, SerializesModels;

  public function __construct(
    public string $subjectLine,
    public string $htmlBody
  ) {}

  public function build()
  {
    return $this->subject($this->subjectLine)
      ->html($this->htmlBody); // <- Symfony-friendly
    // If you want a Blade view instead:
    // ->view('emails.outbound')->with(['body' => $this->htmlBody]);
  }
}
