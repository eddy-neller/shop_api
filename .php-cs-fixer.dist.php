<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
    ->exclude('save')
    ->exclude('config')
    ->exclude('vendor')
    ->exclude('public')
    ->notPath('tests/bootstrap.php')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'concat_space' => ['spacing' => 'one'],
        'global_namespace_import' => false,
    ])
    ->setFinder($finder)
;
