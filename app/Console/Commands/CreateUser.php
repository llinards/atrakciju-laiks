<?php

namespace App\Console\Commands;

use App\Actions\Fortify\CreateNewUser;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('user:create {name? : The user\'s name} {email? : The user\'s email address}')]
#[Description('Create a new user account')]
class CreateUser extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(CreateNewUser $creator): int
    {
        $name = $this->argument('name') ?? text(label: 'Name', required: true);
        $email = $this->argument('email') ?? text(label: 'Email address', required: true);
        $password = password(label: 'Password', required: true);
        $passwordConfirmation = password(label: 'Confirm password', required: true);

        try {
            $user = $creator->create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $passwordConfirmation,
            ]);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->components->error($message);
                }
            }

            return self::FAILURE;
        }

        $this->components->info("User [{$user->email}] created successfully.");

        return self::SUCCESS;
    }
}
