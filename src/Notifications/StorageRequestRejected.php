<?php

namespace Biigle\Modules\UserStorage\Notifications;

use Biigle\Modules\UserStorage\StorageRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class StorageRequestRejected extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Reason why the request was rejected.
     *
     * @var string
     */
    protected $reason;

    /**
     * Create a new notification instance.
     *
     * @param string $reason
     * @return void
     */
    public function __construct($reason)
    {
        $this->reason = $reason;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $settings = config('user_storage.notifications.default_settings');

        if (config('user_storage.notifications.allow_user_settings') === true) {
            $settings = $notifiable->getSettings('storage_request_notifications', $settings);
        }

        if ($settings === 'web') {
            return ['database'];
        }

        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $message = (new MailMessage)
            ->subject('Your BIIGLE storage request was rejected')
            ->line("Your storage request was rejected.")
            ->line("All uploaded files have been deleted.")
            ->line("Reason: {$this->reason}");

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $array = [
            'title' => 'Your BIIGLE storage request was rejected',
            'message' => "Your storage request was rejected. All uploaded files have been deleted. Reason: {$this->reason}",
        ];

        return $array;
    }
}
