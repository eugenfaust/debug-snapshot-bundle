<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;

try {
    $finder = (new Finder())
        ->in(__DIR__)
        ->exclude(['var', 'vendor', 'snapshot-test-app'])
    ;

    return (new Config())
        ->setRules([
            '@Symfony' => true,
            '@PSR12' => true,
            '@PHP83Migration' => true,
            '@PhpCsFixer' => true,
            'yoda_style' => [
                'equal' => false,
                'identical' => false,
                'less_and_greater' => false,
            ],
            'single_line_comment_style' => [
                'comment_types' => ['hash'],
            ],
            'multiline_comment_opening_closing' => false,
            'phpdoc_align' => ['align' => 'left'],
            'phpdoc_line_span' => [
                'const' => 'multi',
                'property' => 'multi',
                'method' => 'multi',
            ],
            'phpdoc_to_comment' => false,
            'global_namespace_import' => [
                'import_constants' => false,
                'import_functions' => false,
                'import_classes' => true,
            ],
        ])
        ->setFinder($finder)
        ->setCacheFile(__DIR__.'/.build/php-cs-fixer/.php-cs-fixer.cache')
    ;
} catch (DirectoryNotFoundException $e) {
}
