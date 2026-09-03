<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

final class CreateUser extends Command
{
    protected $signature = 'user:create';

    protected $description = 'Interactively create a new user account';

    public function handle(): int
    {
        $this->info('Creating a new user account.');
        $this->newLine();

        $name = $this->ask('Name');

        $email = $this->askWithValidation('Email', fn ($value) => Validator::make(
            ['email' => $value],
            ['email' => ['required', 'email', 'unique:users,email']],
        ));

        $password = $this->secret('Password (min. 8 characters)');

        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');
            return self::FAILURE;
        }

        $confirm = $this->secret('Confirm password');

        if ($password !== $confirm) {
            $this->error('Passwords do not match.');
            return self::FAILURE;
        }

        User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($password),
        ]);

        $this->newLine();
        $this->info("User \"{$name}\" ({$email}) created successfully.");

        return self::SUCCESS;
    }

    private function askWithValidation(string $question, callable $validator): string
    {
        while (true) {
            $value = $this->ask($question);

            $result = $validator($value);

            if ($result->passes()) {
                return $value;
            }

            foreach ($result->errors()->all() as $error) {
                $this->error($error);
            }
        }
    }
}
