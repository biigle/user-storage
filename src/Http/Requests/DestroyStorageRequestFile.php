<?php

namespace Biigle\Modules\UserStorage\Http\Requests;

use Biigle\Modules\UserStorage\StorageRequest;
use Illuminate\Foundation\Http\FormRequest;

class DestroyStorageRequestFile extends FormRequest
{
    /**
     * Storage request that should be approved.
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
            'files' => 'required|array|min:1',
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
            $files = $this->input('files', []);
            $union = array_unique(array_merge($this->storageRequest->files, $files));
            $filesCount = count($this->storageRequest->files);

            if (count($union) > $filesCount) {
                $validator->errors()->add('files', 'Some specified files do not belong to the storage request.');
            } elseif (count($files) === $filesCount) {
                $validator->errors()->add('files', 'You cannot delete all files of the storage request with this endpoint. Delete the whole request instead.');
            }
        });
    }
}
