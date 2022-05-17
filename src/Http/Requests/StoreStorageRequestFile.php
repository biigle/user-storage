<?php

namespace Biigle\Modules\UserStorage\Http\Requests;

use Biigle\Image;
use Biigle\Modules\UserStorage\Rules\FilePrefix;
use Biigle\Modules\UserStorage\StorageRequest;
use Biigle\Modules\UserStorage\StorageRequestFile;
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

        $maxQuota = $user->storage_quota_remaining;
        $maxFile = config('user_storage.max_file_size');

        // The "max" rule expects kilobyte but the quota is in byte.
        $maxKb = intval(round(min($maxQuota, $maxFile) / 1000));

        $mimes = implode(',', array_merge(Image::MIMES, Video::MIMES));

        return [
            'file' => "required|file|max:{$maxKb}|mimetypes:{$mimes}",
            'prefix' => ['filled', new FilePrefix],
            'chunk_index' => 'filled|integer|required_with:chunk_total|min:0|lt:chunk_total',
            'chunk_total' => 'filled|integer|required_with:chunk_index|min:2',
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
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        if ($this->input('prefix')) {
            $this->merge([
                // Remove double slashes.
                'prefix' => $this->sanitizePrefix($this->input('prefix')),
            ]);
        }
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

            if (!$validator->valid() || !$this->hasFile('file')) {
                // Return early before checking file existence below.
                return;
            }

            $path = $this->getFilePath();

            if (strlen($path) > 512) {
                $validator->errors()->add('file', 'The filename and prefix combined must not exceed 512 characters.');
            }

            $existsInOtherRequest = StorageRequestFile::join('storage_requests', 'storage_requests.id', '=', 'storage_request_files.storage_request_id')
                ->where('storage_requests.id', '!=', $this->storageRequest->id)
                ->where('storage_requests.user_id', $this->storageRequest->user_id)
                ->where('storage_request_files.path', $path)
                ->exists();

            // Deny uploading of files that already exist in another request of the same
            // user. This could lead to the following issue:
            // The file exists in request A and B. Its size was added twice during each
            // upload to the used quota of the user. But ultimately the file exists only
            // once in storage. If requests A and B are deleted with all files, the size
            // of the duplicate file will remain in the used quota because it could be
            // deleted only once.
            if ($existsInOtherRequest) {
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

    /**
     * Sanitize the path prefix.
     *
     * @param string $prefix
     *
     * @return string
     */
    public function sanitizePrefix($prefix)
    {
        // Remove double slashes.
        $prefix = preg_replace('/\/+/', '/', $this->input('prefix'));
        // Remove trailing slash.
        $prefix = rtrim($prefix, '/');

        return $prefix;
    }
}
