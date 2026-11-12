<?php

declare(strict_types=1);

namespace App\Modules\Payroll\Console;

use Illuminate\Support\Str;

/** Reads the arguments and options of the artisan commands of Payroll. */
final class CommandOptions
{
    /** The id the person gave, or a new one, so a request is repeated safely only when the person asks for it. */
    public static function commandId(mixed $option): string
    {
        return \is_string($option) && $option !== '' ? $option : Str::uuid()->toString();
    }

    public static function text(mixed $value): string
    {
        return \is_string($value) ? $value : '';
    }
}
