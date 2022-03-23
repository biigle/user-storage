<?php

namespace Biigle\Modules\UserStorage\Http\Requests;

use Biigle\Modules\UserStorage\StorageRequest;
use Illuminate\Foundation\Http\FormRequest;

class ExtendStorageRequest extends FormRequest
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

        return $this->user()->can('update', $this->storageRequest);
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
            if (is_null($this->storageRequest->expires_at)) {
                $validator->errors()->add('id', "The storage request was not approved yet.");
            }

            $warnPeriod = config('user_storage.about_to_expire_weeks');

            if ($this->storageRequest->expires_at >= now()->addWeeks($warnPeriod)) {
                $validator->errors()->add('id', "The storage request is not about to expire.");
            }
        });
    }
}
