<?php

namespace Biigle\Modules\UserStorage\Http\Requests;

use Biigle\Image;
use Biigle\Modules\UserStorage\Jobs\DeleteStorageRequestFile;
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
     * The file that belongs to a chunked upload (if any).
     *
     * @var StorageRequestFile
     */
    public $storageRequestFile;

    public $chunkOrFileExists;

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
        $fileRules = 'required|file';

        // Skip MIME type check for file chunks (except the first).
        if (!$this->isChunked() || $this->input('chunk_index') === 0) {
            $mimes = implode(',', array_merge(Image::MIMES, Video::MIMES));
            $fileRules .= "|mimetypes:{$mimes}";
        }

        return [
            'file' => $fileRules,
            'prefix' => ['filled', new FilePrefix],
            'chunk_index' => 'filled|integer|required_with:chunk_total|min:0|lt:chunk_total',
            'chunk_total' => 'filled|integer|required_with:chunk_index|min:2',
            'retry' => 'filled|boolean',
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

                return;
            }

            if (!$this->hasFile('file')) {
                return;
            }

            $file = $this->file('file');
            $user = User::convert($this->storageRequest->user);
            $shouldDeletePreviousChunks = false;

            if ($file->getSize() > $user->storage_quota_remaining) {
                $validator->errors()->add('file', 'The file size exceeds the available storage quota.');
                $shouldDeletePreviousChunks = true;
            }

            $maxFileSize = config('user_storage.max_file_size');
            if ($file->getSize() > $maxFileSize) {
                $validator->errors()->add('file', "The file size exceeds the maximum allowed file size of {$maxFileSize} bytes.");
                $shouldDeletePreviousChunks = true;
            }

            $chunkSize = config('user_storage.upload_chunk_size');
            if ($file->getSize() > $chunkSize) {
                if ($this->isChunked()) {
                    $validator->errors()->add('file', "The file size of this chunk exceeds the configured chunk size of {$chunkSize} bytes.");
                } else {
                    $validator->errors()->add('file', "The file is too large and must be uploaded in chunks of a maximum of {$chunkSize} bytes each.");
                }
                $shouldDeletePreviousChunks = true;
            }

            $path = $this->getFilePath();
            $this->storageRequestFile = $this->storageRequest->files()
                    ->where('path', $path)
                    ->first();

            $this->chunkOrFileExists = ($this->isChunked() && $this->storageRequestFile && in_array($this->input('chunk_index'), $this->storageRequestFile->received_chunks))
            || $this->storageRequestFile;

            if($this->chunkOrFileExists && !$this->input('retry')) {
                $validator->errors()->add('uploaded_file', 'The file was already uploaded but the retry option is not enabled.');
            }

            if ($this->isChunked()) {
                if ($this->storageRequestFile) {
                    $combinedSize = $this->storageRequestFile->size + $file->getSize();
                    if ($combinedSize > $maxFileSize) {
                        $validator->errors()->add('file', "The file size exceeds the maximum allowed file size of {$maxFileSize} bytes.");
                        $shouldDeletePreviousChunks = true;
                    }

                    // Delete chunks of an uploaded file if size validation of a single
                    // chunk failed.
                    if ($shouldDeletePreviousChunks) {
                        DeleteStorageRequestFile::dispatch($this->storageRequestFile);
                    }

                    if ($this->storageRequestFile->total_chunks !== (int) $this->input('chunk_total')) {
                        $validator->errors()->add('chunk_total', 'The specified number of chunks does not match the previously specified number for this file.');
                    }

                } elseif ($this->input('chunk_index') > 0) {
                    $validator->errors()->add('chunk_index', 'The first chunk of a new file must be uploaded before the remaining chunks.');
                }
            }

            if (!$validator->valid()) {
                // Return early before checking file existence below.
                return;
            }

            if (strlen($path) > 512) {
                $validator->errors()->add('file', 'The filename and prefix combined must not exceed 512 characters.');
            }
            $existsInOtherRequest = StorageRequestFile::join('storage_requests', 'storage_requests.id', '=', 'storage_request_files.storage_request_id')
                ->where('storage_requests.id', '!=', $this->storageRequest->id)
                ->where('storage_requests.user_id', $this->storageRequest->user_id)
                ->where('storage_request_files.path', $path)
                ->exists();

            // Deny file uploads that would create a request for the same user 
            // with a directory name that already exists and that also contains the same file.
            // This could lead to the following issue:
            // The file exists in request A and B. Its size was added twice during each
            // upload to the used quota of the user. But ultimately the file exists only
            // once in storage. If requests A and B are deleted with all files, the size
            // of the duplicate file will remain in the used quota because it could be
            // deleted only once.
            if ($existsInOtherRequest) {
                $validator->errors()->add('file_duplicated', 'The file already exists in the user storage.');
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

    /**
     * Determine if the file is a chunked upload.
     *
     * @return boolean
     */
    public function isChunked()
    {
        return $this->has('chunk_index');
    }
}
