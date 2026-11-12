<?php

declare(strict_types=1);

namespace Tests\Payroll\Unit\Domain;

use Payroll\Domain\AdjustmentComment;
use Payroll\Domain\Exception\InvalidAdjustmentComment;
use Payroll\Domain\VisibleText;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(AdjustmentComment::class)]
#[CoversClass(InvalidAdjustmentComment::class)]
#[CoversClass(VisibleText::class)]
final class AdjustmentCommentTest extends TestCase
{
    public function testTrimsSurroundingWhitespace(): void
    {
        self::assertSame('Overtime bonus', new AdjustmentComment("  Overtime bonus \n")->value);
    }

    /** @return iterable<string, array{string}> */
    public static function blankComments(): iterable
    {
        yield 'empty' => [''];
        yield 'only whitespace' => ["   \t\n"];
        yield 'only non-breaking and zero-width spaces' => ["\u{00A0}\u{200B}"];
        yield 'only control characters' => ["\x00\x07"];
        yield 'only invisible fillers' => ["\u{3164}\u{2800}"];
    }

    #[DataProvider('blankComments')]
    public function testRejectsABlankComment(string $comment): void
    {
        $this->expectException(InvalidAdjustmentComment::class);

        new AdjustmentComment($comment);
    }

    /** @return iterable<string, array{string}> */
    public static function unreadableComments(): iterable
    {
        yield 'invalid UTF-8' => ["\xC3\x28"];
        yield 'control character inside' => ["Bonus\x07 paid"];
    }

    #[DataProvider('unreadableComments')]
    public function testRejectsUnreadableText(string $comment): void
    {
        $this->expectException(InvalidAdjustmentComment::class);

        new AdjustmentComment($comment);
    }

    public function testKeepsLineBreaksAndTabsInside(): void
    {
        self::assertSame("Bonus:\n\tapproved", new AdjustmentComment("Bonus:\n\tapproved")->value);
    }

    public function testAcceptsACommentOfMaximumLengthCountedInCharacters(): void
    {
        $comment = str_repeat('é', AdjustmentComment::MAX_LENGTH);

        self::assertSame($comment, new AdjustmentComment($comment)->value);
    }

    public function testRejectsATooLongComment(): void
    {
        $this->expectException(InvalidAdjustmentComment::class);

        new AdjustmentComment(str_repeat('é', AdjustmentComment::MAX_LENGTH + 1));
    }
}
