<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;

class CustomerPasswordResetNotification extends ResetPassword
{
    protected function resetUrl($notifiable): string
    {
        return url(route('customer.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
    }
}
