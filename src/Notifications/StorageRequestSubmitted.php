<?php

namespace Biigle\Modules\UserStorage\Notifications;

use Biigle\Modules\UserStorage\StorageRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class StorageRequestSubmitted extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The storage request that was submitted
     *
     * @var StorageRequest
     */
    protected $request;

    /**
     * Ignore this job if the image does not exist any more.
     *
     * @var bool
     */
    protected $deleteWhenMissingModels = true;

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
        $fileCount = count($this->request->files);
        $name = "{$this->request->user->firstname} {$this->request->user->lastname}";
        $affiliation = $this->request->user->affiliation ?: 'no affiliation';

        $message = (new MailMessage)
            ->subject('New storage request')
            ->line("A new storage request with {$fileCount} file(s) was created by {$name} ($affiliation).")
            ->action('Review', route('review-storage-request', $this->request->id));

        return $message;
    }
}
