<?php

declare(strict_types=1);

namespace App\Actions\PlayStation\Cod;

final readonly class ValidationResult
{
    /** @param list<string> $errors */
    public function __construct(
        public bool $passed,
        public array $errors,
    ) {}

    public static function pass(): self
    {
        return new self(passed: true, errors: []);
    }

    public static function fail(string ...$errors): self
    {
        return new self(passed: false, errors: array_values($errors));
    }
}
