<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\Models\Integration;
use Illuminate\Support\Facades\Mail;

class EmailNotificationService
{
    /**
     * Send email notification.
     */
    public function send(Integration $integration, array $message): void
    {
        $email = $integration->email;
        
        if (!$email) {
            throw new \Exception('Email not configured for integration');
        }

        Mail::send('emails.integration-notification', $message, function ($mail) use ($email, $message) {
            $mail->to($email)
                ->subject($message['title']);
        });
    }
}
