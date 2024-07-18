<?php

namespace Biigle\Modules\UserStorage\Http\Requests;

use Biigle\Modules\UserStorage\StorageRequest;
use Illuminate\Foundation\Http\FormRequest;

class DestroyStorageRequestDirectory extends FormRequest
{
    /**
     * Storage request that should be approved.
     *
     * @var StorageRequest
     */
    public $storageRequest;

    /**
     * The files that should be deleted.
     *
     * @var array
     */
    public $srFiles;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $this->storageRequest = StorageRequest::findOrFail($this->route('id'));

        return $this->user()->can('destroy', $this->storageRequest);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'directories' => 'required|array|min:1',
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
            $directories = array_map(function ($item) {
                // Add trailing slash if missing.
                return strpos($item, '/', -1) === false ? "{$item}/" : $item;
            }, $this->input('directories', []));

            $files = $this->storageRequest->files;
            $allFilesCount = $files->count();
            $this->srFiles = $files->filter(function ($file) use ($directories) {
                return array_reduce($directories, function ($carry, $item) use ($file) {
                    return $carry || strpos($file->path, $item) === 0;
                }, false);
            });

            $filesCount = count($this->srFiles);

            if ($filesCount === 0) {
                $validator->errors()->add('directories', 'No files were found for the specified directories.');
            } elseif ($filesCount === $allFilesCount) {
                $validator->errors()->add('directories', 'You cannot delete all files of the storage request this way. Delete the whole request instead.');
            }
        });
    }
}
