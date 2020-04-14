<?php
return PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'no_php4_constructor' => true,
        'no_short_echo_tag' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'no_unreachable_default_argument_value' => true,
        'simplified_null_return' => true,
        /*'no_superfluous_phpdoc_tags' => false,*/
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__.'/src')
            ->in(__DIR__.'/bin')
            ->in(__DIR__.'/tests')
    );
