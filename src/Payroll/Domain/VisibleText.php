<?php

declare(strict_types=1);

namespace Payroll\Domain;

/** Trimming that also removes characters a person cannot see, so "looks empty" means "is empty". */
final class VisibleText
{
    /**
     * Whitespace and separators, control and format characters (such as zero-width spaces),
     * and the Hangul and Braille fillers that render as blanks.
     */
    private const string INVISIBLE = '[\s\p{Z}\p{Cc}\p{Cf}\x{115F}\x{1160}\x{2800}\x{3164}\x{FFA0}]';

    /** @param string $text valid UTF-8 */
    public static function trim(string $text): string
    {
        return (string) preg_replace('/\A' . self::INVISIBLE . '+|' . self::INVISIBLE . '+\z/u', '', $text);
    }
}
