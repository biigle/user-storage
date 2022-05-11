<?php

namespace Biigle\Modules\UserStorage\Http\Requests;

use Biigle\Modules\UserStorage\StorageRequestFile;
use Illuminate\Foundation\Http\FormRequest;

class DestroyStorageRequestFile extends FormRequest
{
    /**
     * Storage request file that should be deleted.
     *
     * @var StorageRequestFile
     */
    public $file;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $this->file = StorageRequestFile::with('request')->findOrFail($this->route('id'));

        return $this->user()->can('destroy', $this->file->request);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
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
            if ($this->file->request->files()->count() === 1) {
                $validator->errors()->add('files', 'You cannot delete all files of the storage request this way. Delete the whole request instead.');
            }
        });
    }
}
