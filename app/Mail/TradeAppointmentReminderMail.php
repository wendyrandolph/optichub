<?php

namespace App\Mail;

use App\Models\Tenant;
use App\Models\TradeAppointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TradeAppointmentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public TradeAppointment $appointment,
        public int $offsetMinutes
    ) {}

    public function build()
    {
        return $this
            ->subject('Upcoming appointment reminder')
            ->view('emails.trade-appointment-reminder', [
                'tenant' => $this->tenant,
                'appointment' => $this->appointment,
                'offsetMinutes' => $this->offsetMinutes,
            ]);
    }
}
