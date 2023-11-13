<?php

// php_codesniffer autoloader
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

if (!defined('PHP_CODESNIFFER_VERBOSITY')) {
    define('PHP_CODESNIFFER_VERBOSITY', 0);
}

if (!defined('PHP_CODESNIFFER_IN_TESTS')) {
    define('PHP_CODESNIFFER_IN_TESTS', true);
}

$_SERVER['argv'][] = '--standard=' . str_replace('/tests', '/phpcs.default.xml', __DIR__);
