<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
    ->exclude('vendor')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        '@PhpCsFixer' => true,
        'declare_strict_types' => true,
        'void_return' => true,
        'native_function_invocation' => ['include' => ['@compiler_optimized'], 'scope' => 'namespaced'],
        'global_namespace_import' => ['import_classes' => true, 'import_functions' => false, 'import_constants' => false],
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
;
