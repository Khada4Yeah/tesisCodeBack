<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotificacionEmailReComision extends Mailable
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
            case "value":
                return $this->view("view.name");
                break;

            default:
                # code...
                break;
        }
    }
}
