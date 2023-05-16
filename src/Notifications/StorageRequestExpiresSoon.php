<?php

namespace Biigle\Modules\UserStorage\Notifications;

use Biigle\Modules\UserStorage\StorageRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class StorageRequestExpiresSoon extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The storage request that was confirmed
     *
     * @var StorageRequest
     */
    protected $request;

    /**
     * Ignore this job if the request does not exist any more.
     *
     * @var bool
     */
    protected $deleteWhenMissingModels = true;

    /**
     * Create a new notification instance.
     *
     * @param StorageRequest $request
     * @param string $reason
     * @return void
     */
    public function __construct(StorageRequest $request)
    {
        $this->request = $request;
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
        $diff = $this->request->expires_at->diffForHumans();

        $message = (new MailMessage)
            ->subject('Your BIIGLE storage request will expire soon')
            ->line("Your storage request will expire {$diff}.")
            ->action("View storage request", route('index-storage-requests'));

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
        $diff = $this->request->expires_at->diffForHumans();

        $array = [
            'title' => 'Your storage request will expire soon',
            'message' => "Your storage request will expire {$diff}.",
            'action' => 'View storage request',
            'actionLink' => route('index-storage-requests'),
        ];

        return $array;
    }
}
