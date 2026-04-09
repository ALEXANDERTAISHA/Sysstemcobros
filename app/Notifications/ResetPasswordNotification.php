<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(private string $token)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $expireMinutes = (int) config('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60);
        $appName = (string) config('app.name', 'SystemCobros');

        return (new MailMessage)
            ->subject('Restablece tu contraseña en ' . $appName)
            ->greeting('Hola ' . ($notifiable->name ?: ''))
            ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta en ' . $appName . '.')
            ->line('Si tú hiciste esta solicitud, confirma el cambio desde el siguiente botón.')
            ->action('Restablecer Contraseña', $resetUrl)
            ->line('Este enlace de seguridad expirará en ' . $expireMinutes . ' minutos.')
            ->line('Si no solicitaste este cambio, puedes ignorar este correo con tranquilidad.')
            ->salutation('Equipo ' . $appName);
    }
}
