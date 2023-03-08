<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotificacionEmailVicedecanos extends Mailable
{
    use Queueable, SerializesModels;

    protected $tipo;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($tipo)
    {
        $this->tipo = $tipo;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        switch ($this->tipo) {
            case "NS":
                return $this->view(
                    "mail.vicedecanos.solicitudPendiente"
                )->subject("Nueva solicitud");
                break;

            default:
                # code...
                break;
        }
    }
}
