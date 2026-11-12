<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

/**
 * Dependency rules of ADR 0001 and ADR 0012 and the folder rule for exceptions, checked by phpat during static analysis.
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

    public function testApplicationDependsOnlyOnDomainAndApplicationLayers(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('Common\Application'), Selector::inNamespace('Payroll\Application'))
            ->canOnlyDependOn()
            ->classes(
                Selector::inNamespace('Common\Domain'),
                Selector::inNamespace('Common\Application'),
                Selector::inNamespace('Payroll\Domain'),
                Selector::inNamespace('Payroll\Application'),
                Selector::isStandardClass(),
            )
            ->because('use cases reach storage and the outside world only through ports');
    }

    public function testEveryExceptionLivesInTheExceptionFolderOfItsLayer(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(
                Selector::AnyOf(Selector::inNamespace('Common'), Selector::inNamespace('Payroll')),
                Selector::isThrowable(),
            ))
            ->excluding(Selector::inNamespace('/^(Common|Payroll)\\\\(Domain|Application)\\\\Exception$/', true))
            ->shouldNotExist()
            ->because('every layer keeps its exceptions in one place');
    }

    public function testCoreDependsOnNoFrameworkOrLibrary(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('Common'), Selector::inNamespace('Payroll'))
            ->canOnlyDependOn()
            ->classes(Selector::inNamespace('Common'), Selector::inNamespace('Payroll'), Selector::isStandardClass())
            ->because('the core works through ports; frameworks and libraries are plugged in from outside');
    }

    public function testInMemoryAdaptersAndTheDemoDoNotDependOnLaravel(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('/^App\\\\Modules\\\\[A-Za-z]+\\\\(InMemory|Cli)$/', true))
            ->shouldNotDependOn()
            ->classes(Selector::inNamespace('Illuminate'))
            ->because('the in-memory adapters and the demo show that the core runs without a framework');
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
