<?php

namespace Biigle\Modules\UserStorage\Http\Requests;

use Biigle\Image;
use Biigle\Modules\UserStorage\StorageRequest;
use Biigle\Modules\UserStorage\User;
use Biigle\Video;
use Illuminate\Foundation\Http\FormRequest;
use Storage;

class StoreStorageRequestFile extends FormRequest
{
    /**
     * Storage request to which the file should be uploaded.
     *
     * @var StorageRequest
     */
    public $storageRequest;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $this->storageRequest = StorageRequest::findOrFail($this->route('id'));

        return $this->user()->can('update', $this->storageRequest);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $user = User::convert($this->storageRequest->user);

        // The "max" rule expects kilobyte but the quota is in byte.
        $maxKb = intval(round(($user->storage_quota_available - $user->storage_quota_used) / 1024));

        $mimes = implode(',', array_merge(Image::MIMES, Video::MIMES));

        return [
            'file' => "required|file|max:{$maxKb}|mimetypes:{$mimes}",
            'prefix' => 'filled',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'file.max' => 'The file size exceeds the available storage quota.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!is_null($this->storageRequest->submitted_at)) {
                $validator->errors()->add('file', 'The storage request was already submitted and no new files can be uploaded.');
            }

            if (!$validator->valid()) {
                // Return early before checking file existence below.
                return;
            }

            $path = $this->storageRequest->getStoragePath($this->getFilePath());
            $disk = config('user_storage.user_disk');
            if (Storage::disk($disk)->exists($path)) {
                $validator->errors()->add('file', 'The file already exists in the user storage.');
            }
        });
    }

    /**
     * Get the full path where the file should be stored.
     *
     * @return string
     */
    public function getFilePath()
    {
        $filename = $this->file('file')->getClientOriginalName();
        if ($prefix = $this->input('prefix')) {
            $filename = "{$prefix}/{$filename}";
        }

        return $filename;
    }
}
