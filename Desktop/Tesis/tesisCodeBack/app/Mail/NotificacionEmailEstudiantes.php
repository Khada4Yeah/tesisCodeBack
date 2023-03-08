<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotificacionEmailEstudiantes extends Mailable
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
            case "SGCC":
                return $this->view(
                    "mail.estudiantes.solicitudGeneradaCambioCarrera"
                )->subject("Generación de solicitud");
                break;

            case "SGCU":
                return $this->view(
                    "mail.estudiantes_externos.solicitudGeneradaCambioUniversidad"
                )->subject("Generación de solicitud");
                break;

            case "SECC":
                return $this->view(
                    "mail.estudiantes.solicitudEnviadaCambioCarrera"
                )->subject("Envío de solicitud");
                break;

            case "SECU":
                return $this->view(
                    "mail.estudiantes_externos.solicitudEnviadaCambioUniversidad"
                )->subject("Envío de solicitud");
                break;

            case "SR":
                return $this->view(
                    "mail.estudiantes_comun.solicitudRechazada"
                )->subject("Rechazo de solicitud");
                break;

            case "SC":
                return $this->view(
                    "mail.estudiantes_comun.solicitudCorreciones"
                )->subject("Correción de solicitud");
                break;

            case "SA":
                return $this->view(
                    "mail.estudiantes_comun.solicitudAceptada"
                )->subject("Aceptación de solicitud");
                break;

            default:
                # code...
                break;
        }
    }
}
