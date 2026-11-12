<?php

declare(strict_types=1);

namespace Tests\Payroll\Unit\Domain;

use Payroll\Domain\EarningLineId;
use Payroll\Domain\Exception\InvalidIdentifier;
use Payroll\Domain\SpecialistId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(EarningLineId::class)]
#[CoversClass(SpecialistId::class)]
#[CoversClass(InvalidIdentifier::class)]
final class IdentifierTest extends TestCase
{
    public function testLineIdIsACanonicalLowerCaseUuid(): void
    {
        $id = new EarningLineId('019A7B2C-3D4E-7F80-9A1B-2C3D4E5F6A7B');

        self::assertSame('019a7b2c-3d4e-7f80-9a1b-2c3d4e5f6a7b', $id->value);
        self::assertTrue($id->equals(new EarningLineId('019a7b2c-3d4e-7f80-9a1b-2c3d4e5f6a7b')));
        self::assertFalse($id->equals(new EarningLineId('019a7b2c-3d4e-7f80-9a1b-2c3d4e5f6a7c')));
    }

    /** @return iterable<string, array{string}> */
    public static function invalidLineIds(): iterable
    {
        yield 'empty' => [''];
        yield 'arbitrary string' => ['employee-42.base_salary'];
        yield 'without hyphens' => ['019a7b2c3d4e7f809a1b2c3d4e5f6a7b'];
        yield 'in braces' => ['{019a7b2c-3d4e-7f80-9a1b-2c3d4e5f6a7b}'];
        yield 'trailing newline' => ["019a7b2c-3d4e-7f80-9a1b-2c3d4e5f6a7b\n"];
        yield 'surrounding spaces' => [' 019A7B2C-3D4E-7F80-9A1B-2C3D4E5F6A7B '];
    }

    #[DataProvider('invalidLineIds')]
    public function testRejectsAnInvalidLineId(string $id): void
    {
        $this->expectException(InvalidIdentifier::class);

        new EarningLineId($id);
    }

    public function testSpecialistIdIsAnOpaqueTrimmedReference(): void
    {
        self::assertSame('anna@payroll', new SpecialistId("\u{00A0}anna@payroll\u{200B} ")->value);
        self::assertSame(str_repeat('é', SpecialistId::MAX_LENGTH), new SpecialistId(str_repeat('é', SpecialistId::MAX_LENGTH))->value);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidSpecialistIds(): iterable
    {
        yield 'empty' => [''];
        yield 'only spaces' => ['   '];
        yield 'too long' => [str_repeat('a', SpecialistId::MAX_LENGTH + 1)];
        yield 'invalid UTF-8' => ["\xC3\x28"];
        yield 'only invisible characters' => ["\u{00A0}\u{200B}"];
        yield 'line break inside' => ["anna\nbob"];
        yield 'control character inside' => ["an\x07na"];
    }

    #[DataProvider('invalidSpecialistIds')]
    public function testRejectsAnInvalidSpecialistId(string $id): void
    {
        $this->expectException(InvalidIdentifier::class);

        new SpecialistId($id);
    }
}
