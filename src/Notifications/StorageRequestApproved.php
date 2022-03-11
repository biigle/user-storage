<?php

namespace Biigle\Modules\UserStorage\Notifications;

use Biigle\Modules\UserStorage\StorageRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StorageRequestApproved extends Notification
{
    /**
     * The storage request that was confirmed
     *
     * @var StorageRequest
     */
    protected $request;

    /**
     * Create a new notification instance.
     *
     * @param StorageRequest $request
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
        $message = (new MailMessage)
            ->subject('Your BIIGLE storage request was approved')
            ->line("You can now use the uploaded files to create new volumes!");

        // TODO implement action button with link to request view
        // if (config('app.url')) {
        //     $message = $message->action('Download report', $this->report->getUrl());
        // }

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
            'title' => 'Your BIIGLE storage request was approved',
            'message' => "You can now use the uploaded files to create new volumes!",
        ];

        // TODO implement action button with link to request view
        // if (config('app.url')) {
        //     $array['action'] = 'Download report';
        //     $array['actionLink'] = $this->report->getUrl();
        // }

        return $array;
    }
}
