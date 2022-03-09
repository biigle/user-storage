<?php

namespace Biigle\Modules\UserStorage\Http\Requests;

use Biigle\Modules\UserStorage\StorageRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreStorageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('create', StorageRequest::class);
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
            $pendingCount = StorageRequest::where('user_id', $this->user()->id)
                ->whereNull('expires_at')
                ->count();

            $maxPending = config('user_storage.max_pending_requests');

            if ($pendingCount >= $maxPending) {
                $validator->errors()->add('id', "There are already {$pendingCount} pending storage requests. Please wait for at least one request to be confirmed.");
            }
        });
    }
}
