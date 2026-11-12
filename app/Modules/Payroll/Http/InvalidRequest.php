<?php

declare(strict_types=1);

namespace App\Modules\Payroll\Http;

/** A request body without a field a command needs, or with a field of the wrong type. */
final class InvalidRequest extends \InvalidArgumentException
{
    public static function field(string $field, string $type): self
    {
        return new self(\sprintf('The field "%s" must be %s.', $field, $type));
    }
}
