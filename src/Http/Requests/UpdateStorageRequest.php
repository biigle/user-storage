<?php

namespace Biigle\Modules\UserStorage\Http\Requests;

use Biigle\Modules\UserStorage\StorageRequest;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStorageRequest extends FormRequest
{
    /**
     * Storage request that should be approved.
     *
     * @var StorageRequest
     */
    public $storageRequest;

    /**
     * Chunked files of a stroage request.
     *
     * @var \Illumenate\Support\Collection
     */
    public $chunkedFiles;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $this->storageRequest = StorageRequest::whereNull('submitted_at')
            ->findOrFail($this->route('id'));

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
            if (!$this->storageRequest->files()->exists()) {
                $validator->errors()->add('id', "The storage request has no files.");
            }

            $this->chunkedFiles = $this->storageRequest->files()
                ->whereNotNull('total_chunks')
                ->get();

            $unfinished = $this->chunkedFiles->reduce(function ($carry, $file) {
                $received = $file->received_chunks;
                sort($received);

                return $carry || $received !== range(0, $file->total_chunks - 1);
            }, false);

            if ($unfinished) {
                $validator->errors()->add('id', 'Some file chunks were not uploaded yet.');
            }
        });
    }
}
