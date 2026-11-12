<?php

declare(strict_types=1);

// Coding style: PER Coding Style 2.0 (the successor of PSR-12) plus mandatory strict types.
$finder = new PhpCsFixer\Finder()
    ->in([__DIR__ . '/src', __DIR__ . '/app', __DIR__ . '/tests'])
    // Files that Laravel writes at run time.
    ->exclude(['bootstrap/cache', 'storage'])
    ->append([__FILE__, __DIR__ . '/bin/earning-line', __DIR__ . '/app/artisan']);

return new PhpCsFixer\Config()
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS2.0' => true,
        '@PER-CS2.0:risky' => true,
        'declare_strict_types' => true,
        'no_unused_imports' => true,
        'ordered_imports' => ['imports_order' => ['class', 'function', 'const']],
        'strict_comparison' => true,
    ])
    ->setFinder($finder);
