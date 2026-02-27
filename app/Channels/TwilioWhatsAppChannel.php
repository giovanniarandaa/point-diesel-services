<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Twilio\Rest\Client;

class TwilioWhatsAppChannel
{
    /**
     * @param  Notification&\App\Notifications\VehicleReadyNotification  $notification
     */
    public function send(object $notifiable, Notification $notification): void
    {
        $message = $notification->toTwilioWhatsApp($notifiable);

        /** @var string|null $to */
        $to = $notifiable->routeNotificationFor('twilio_whatsapp', $notification);

        if (! $to) {
            return;
        }

        /** @var string $sid */
        $sid = config('services.twilio.sid');
        /** @var string $token */
        $token = config('services.twilio.auth_token');
        /** @var string $from */
        $from = config('services.twilio.whatsapp_from');

        $client = new Client($sid, $token);

        $client->messages->create(
            "whatsapp:{$to}",
            [
                'from' => "whatsapp:{$from}",
                'body' => $message,
            ]
        );
    }
}
