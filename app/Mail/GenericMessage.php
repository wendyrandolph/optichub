<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

class GenericMessage extends Mailable
{
  use Queueable;

  public function __construct(
    public string $subjectLine,
    public ?string $html = null,
    public ?string $text = null,
    public ?string $fromEmail = null,
    public ?string $fromName = null,
  ) {}

  public function build(): self
  {
    $m = $this->subject($this->subjectLine);

    if ($this->fromEmail) {
      $m->from($this->fromEmail, $this->fromName ?: $this->fromEmail);
    }

    if ($this->html) {
      // Uses a generic Blade to render safe HTML
      return $m->view('emails.generic-html', ['html' => $this->html])
        ->text('emails.generic-text', ['text' => $this->text ?? strip_tags($this->html)]);
    }

    return $m->text('emails.generic-text', ['text' => $this->text ?? '(no content)']);
  }
}
