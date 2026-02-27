<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VehicleReadyNotification extends Notification
{
    use Queueable;

    public function __construct(public Invoice $invoice) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if (config('mail.default') !== 'log' && config('mail.from.address')) {
            $channels[] = 'mail';
        }

        // TODO: Add WhatsApp channel when Twilio is configured
        // if (config('services.twilio.sid')) {
        //     $channels[] = TwilioWhatsAppChannel::class;
        // }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $estimate = $this->invoice->estimate;
        $shopName = config('app.name');
        $unit = $estimate->unit;

        $vehicleInfo = $unit ? "{$unit->make} {$unit->model}" : 'your vehicle';

        return (new MailMessage)
            ->subject("Your Vehicle is Ready for Pickup — {$shopName}")
            ->greeting("Hello {$estimate->customer->name},")
            ->line("Great news! The work on {$vehicleInfo} has been completed.")
            ->line("Invoice: {$this->invoice->invoice_number}")
            ->line('Total: $'.number_format((float) $this->invoice->total, 2))
            ->line('Please contact us to arrange pickup at your convenience.')
            ->salutation("Thank you for choosing {$shopName}!");
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
        ];
    }

    // TODO: Uncomment and implement when Twilio is configured
    // public function toTwilioWhatsApp(object $notifiable): string
    // {
    //     $estimate = $this->invoice->estimate;
    //     $shopName = config('app.name');
    //     $unit = $estimate->unit;
    //     $vehicleInfo = $unit ? "{$unit->make} {$unit->model}" : 'your vehicle';
    //
    //     return "Hello {$estimate->customer->name}! "
    //         . "Great news — the work on {$vehicleInfo} has been completed. "
    //         . "Invoice: {$this->invoice->invoice_number}. "
    //         . "Total: $" . number_format((float) $this->invoice->total, 2) . ". "
    //         . "Please contact us to arrange pickup. "
    //         . "— {$shopName}";
    // }
}
