<?php declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$date = date('Y');
$headerComment = <<<COMMENT
This file is part of the ForciCatchableBundle package.

Copyright (c) Forci Web Consulting Ltd.

Author Martin Kirilov <martin@forci.com>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
COMMENT;

$finder = Finder::create()->in([
    __DIR__ . '/src',
    __DIR__ . '/tests',
]);

return (new Config())
    ->setRules([
        '@Symfony' => true,
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
        ],
        'array_syntax' => [
            'syntax' => 'short',
        ],
        'trailing_comma_in_multiline' => true,
        'concat_space' => [
            'spacing' => 'one',
        ],
        'cast_spaces' => [
            'space' => 'none',
        ],
        'function_declaration' => [
            // closure_function_spacing ('none', 'one'): spacing to use before open parenthesis for closures;
            // defaults to 'one'
            'closure_function_spacing' => 'one',
        ],
        'phpdoc_align' => [
            'align' => 'left',
        ],
        'single_line_throw' => false,
        'header_comment' => [
            'comment_type' => 'comment',
            'header' => $headerComment,
            // separate ('both', 'bottom', 'none', 'top'):
            // whether the header should be separated from the file content with a new line; defaults to 'both'
            'separate' => 'bottom',
        ],
        // Could use that and separate header comment bottom only
        // But any blank lines added by hand aren't removed and are only reduced to 1
        // Ensure there is no code on the same line as the PHP open tag.
        'linebreak_after_opening_tag' => false,
        // Ensure there is no code on the same line as the PHP open tag and it is followed by a blank line.
        'blank_line_after_opening_tag' => false,
        'strict_types' => 1,
        'declare_strict_types' => true,
    ])
    ->setUsingCache(true)
    ->setFinder($finder);
