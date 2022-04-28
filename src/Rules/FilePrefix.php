<?php

namespace Biigle\Modules\UserStorage\Rules;

use Illuminate\Contracts\Validation\Rule;

class FilePrefix implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // Scnchronize this with resources/assets/js/createContainer.vue.

        if (preg_match('/[^\p{L}\p{N}\- \/\.\(\)\[\]]/u', $value) === 1) {
            return false;
        }

        // Leading characters are more restricted.
        if (preg_match('/^[^\p{L}\p{N}]/u', $value) === 1) {
            return false;
        }

        // Trailing characters are also more restricted.
        if (preg_match('/[^\p{L}\p{N}\)\]]$/u', $value) === 1) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The file :attribute contains invalid characters.';
    }
}
