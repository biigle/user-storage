<?php

namespace Biigle\Modules\UserStorage\Http\Requests;

use Biigle\Modules\UserStorage\StorageRequest;
use Illuminate\Foundation\Http\FormRequest;

class RejectStorageRequest extends FormRequest
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
        $this->storageRequest = StorageRequest::whereNull('expires_at')
            ->findOrFail($this->route('id'));

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
            'reason' => 'required',
        ];
    }
}
