<?php

namespace SilverStripe\MarkdownPhpCodeSniffer\Test;

use PHP_CodeSniffer\Files\FileList;
use PHP_CodeSniffer\Ruleset;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use SilverStripe\MarkdownPhpCodeSniffer\Sniffer;

/**
 * Because of PHP CodeSniffer's reliance on constants, this has to be done separately from the other tests.
 */
class SnifferFixTest extends TestCase
{
    public function testFix()
    {
        if (!defined('PHP_CODESNIFFER_CBF')) {
            define('PHP_CODESNIFFER_CBF', true);
        }

        $sniffer = new Sniffer();

        // backup original content
        $orig = [];
        $paths = SnifferFixTest::getFilesList($sniffer);
        /** @var string $path */
        foreach ($paths as $path => $v) {
            $orig[$path] = file_get_contents($path);
        }

        try {
            ob_start();
            $exitCode = $sniffer->run('PHP', true, true);
            $output = ob_get_clean();

            // Validate that the files which should change did, and which shouldn't change didn't
            foreach ($orig as $path => $content) {
                $this->assertFileExists($path);

                if (str_contains($path, 'lint-with-problems')) {
                    $this->assertFileEquals(str_replace('/fixtures/', '/expected-after-fixing/', $path), $path);
                } else {
                    $this->assertSame($content, file_get_contents($path));
                }
            }

            // There are no remaining auto-fixable problems
            $this->assertSame(1, $exitCode);
        } finally {
            // Put the original content back
            foreach ($orig as $path => $content) {
                file_put_contents($path, $content);
            }
        }
    }

    private static function getFilesList(Sniffer $sniffer): FileList
    {
        $prepareConfig = new ReflectionMethod($sniffer, 'prepareConfig');
        $prepareConfig->setAccessible(true);
        $config = $prepareConfig->invoke($sniffer, false, 'PHP', true);

        return new FileList($config, new Ruleset($config));
    }
}
