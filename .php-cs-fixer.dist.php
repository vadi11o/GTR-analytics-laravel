<?php

$config = new PhpCsFixer\Config();
return $config
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => [
            'default' => 'align_single_space_minimal',
        ],
        'trim_array_spaces' => true,
        'no_trailing_whitespace' => true,
        'single_quote' => true,
        'no_extra_blank_lines' => true,
        'no_empty_comment' => true,
        'no_whitespace_in_blank_line' => true,
        'single_blank_line_at_eof' => true,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()->in([
            __DIR__.'/app',
        ])
    );
