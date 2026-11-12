<?php

declare(strict_types=1);

namespace App\Modules\Payroll\Http;

use Illuminate\Http\Request;

/**
 * The fields of a JSON request body, with their types checked. Amounts must be strings, so they reach the domain
 * exactly as they were written; a JSON number would already have been turned into a float.
 */
final readonly class JsonBody
{
    /** @param array<array-key, mixed> $fields */
    private function __construct(private array $fields) {}

    public static function of(Request $request): self
    {
        return new self($request->isJson() ? $request->json()->all() : []);
    }

    /** @throws InvalidRequest */
    public function string(string $field): string
    {
        $value = $this->fields[$field] ?? null;

        return \is_string($value) ? $value : throw InvalidRequest::field($field, 'a string');
    }

    /** @throws InvalidRequest */
    public function nonEmptyString(string $field): string
    {
        $value = $this->string($field);

        return $value !== '' ? $value : throw InvalidRequest::field($field, 'a non-empty string');
    }

    /** @throws InvalidRequest */
    public function nonNegativeInt(string $field): int
    {
        $value = $this->fields[$field] ?? null;

        return \is_int($value) && $value >= 0 ? $value : throw InvalidRequest::field($field, 'an integer of 0 or more');
    }
}
