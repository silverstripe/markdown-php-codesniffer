<?php

// php_codesniffer autoloader

use PHP_CodeSniffer\Util\Tokens;

$autoloadCandidates = [
    // running from package itself as root
    __DIR__ . '/../vendor/squizlabs/php_codesniffer/autoload.php',
    // running from package in vendor/silverstripe/markdown-php-codesniffer
    __DIR__ . '/../../../squizlabs/php_codesniffer/autoload.php',
];

$autoloaded = false;
foreach ($autoloadCandidates as $candidate) {
    if (is_file($candidate)) {
        require_once $candidate;
        $autoloaded = true;
        break;
    }
}

if (!$autoloaded) {
    throw new RuntimeException("Couldn't find autoloader");
}

// Required to correctly run constant definitions in CI, though not needed locally for some reason.
// Referencing a static property on the Tokens class forces the file containing it to be autoloaded,
// which results in the constants (defined in the same file) being defined ahead of time.
Tokens::$operators;

if (!defined('PHP_CODESNIFFER_VERBOSITY')) {
    define('PHP_CODESNIFFER_VERBOSITY', 0);
}

if (!defined('PHP_CODESNIFFER_IN_TESTS')) {
    define('PHP_CODESNIFFER_IN_TESTS', true);
}

$_SERVER['argv'][] = '--standard=' . str_replace('/tests', '/phpcs.default.xml', __DIR__);
