<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function reset(User $user, array $input): void
    {
        $customerOnly = $user->vendor_id === null
            && $user->roles()->where('slug', 'customer')->exists()
            && ! $user->roles()->where('slug', '!=', 'customer')->exists()
            && ! $user->directPermissions()->exists();

        if ($customerOnly) {
            throw ValidationException::withMessages([
                'email' => 'Customer passwords must be reset through the customer account recovery page.',
            ]);
        }

        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $user->forceFill([
            'password' => Hash::make($input['password']),
        ])->save();
    }
}
