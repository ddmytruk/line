<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

/**
 * Dependency rules of ADR 0001, checked by phpat during static analysis.
 * The class name does not end with "Test" because it is not a PHPUnit test.
 */
final class LayerRules
{
    public function testDomainDependsOnNothingOutsideTheDomain(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('Common\Domain'), Selector::inNamespace('Payroll\Domain'))
            ->canOnlyDependOn()
            ->classes(Selector::inNamespace('Common\Domain'), Selector::inNamespace('Payroll\Domain'), Selector::isStandardClass())
            ->because('the domain holds business rules only');
    }

    public function testCoreDependsOnNoFrameworkOrLibrary(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('Common'), Selector::inNamespace('Payroll'))
            ->canOnlyDependOn()
            ->classes(Selector::inNamespace('Common'), Selector::inNamespace('Payroll'), Selector::isStandardClass())
            ->because('the core works through ports; frameworks and libraries are plugged in from outside');
    }

    public function testSharedCoreDoesNotKnowBusinessModules(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('Common'))
            ->shouldNotDependOn()
            ->classes(Selector::inNamespace('Payroll'))
            ->because('shared building blocks must be reusable by any module');
    }
}
