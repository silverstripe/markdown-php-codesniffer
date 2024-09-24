<?php

namespace SilverStripe\MarkdownPhpCodeSniffer\Test;

use PHP_CodeSniffer\Files\FileList;
use PHP_CodeSniffer\Ruleset;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use SilverStripe\MarkdownPhpCodeSniffer\Sniffer;
use PHPUnit\Framework\Attributes\DataProvider;

class SnifferTest extends TestCase
{
    /**
     * Validates that fenced code blocks are correctly identified and have the expected data
     */
    #[DataProvider('provideFindFencedBlocks')]
    public function testFindFencedCodeBlocks(
        string $path,
        bool $exists,
        string $realPath = '',
        int $num = 0,
        string $content = ''
    ) {
        if (!defined('PHP_CODESNIFFER_CBF')) {
            define('PHP_CODESNIFFER_CBF', false);
        }

        $sniffer = new Sniffer();
        $files = SnifferTest::getFilesList($sniffer);

        $findFencedCodeblocks = new ReflectionMethod($sniffer, 'findFencedCodeblocks');
        $findFencedCodeblocks->setAccessible(true);
        $blocks = $findFencedCodeblocks->invoke($sniffer, $files, 'PHP');

        $blockKey = __DIR__ . $path;

        if ($exists) {
            $this->assertArrayHasKey($blockKey, $blocks, 'block must be found');

            $block = $blocks[$blockKey];
            $this->assertSame($blockKey, $block['path'], 'block path must be correct');
            $this->assertSame(__DIR__ . $realPath, $block['realpath'], 'block realpath must be correct');
            $this->assertSame($num, $block['num'], 'block must be numbered correctly');
            $this->assertSame($content, ltrim($block['content']), 'block content must be correct');
        } else {
            $this->assertArrayNotHasKey($blockKey, $blocks, 'block must not be found');
        }
    }

    public static function provideFindFencedBlocks()
    {
        return [
            'nothing to lint 1' => [
                'path' => '/fixtures/nothing-to-lint.md',
                'exists' => false,
            ],
            'nothing to lint 2' => [
                'path' => '/fixtures/nothing-to-lint_1.md',
                'exists' => false,
            ],
            'file paths all include block numbers' => [
                'path' => '/fixtures/lint-but-no-problems.md',
                'exists' => false,
            ],
            [
                'path' => '/fixtures/lint-but-no-problems_1.md',
                'exists' => true,
                'realpath' => '/fixtures/lint-but-no-problems.md',
                'num' => 1,
                'content' => <<<'MD'
                <?php
                namespace App;

                class MyClass
                {
                    private string $myProperty = 'this is the value';
                }

                MD
            ],
            'no hallucinated block' => [
                'path' => '/fixtures/lint-but-no-problems_2.md',
                'exists' => false,
            ],
            // No need to check lint-with-problems_1 and lint-with-problems_2 - they're functionality
            // identical to lint-but-no-problems_1 for the purposes of this test.
            'language identifier not case sensitive' => [
                'path' => '/fixtures/lint-with-problems_3.md',
                'exists' => true,
                'realpath' => '/fixtures/lint-with-problems.md',
                'num' => 3,
                'content' => <<<'MD'
                <?php
                class AnotherClass {
                    private string $anotherProperty='this is the value';
                }

                MD
            ],
            'indentation not in content' => [
                'path' => '/fixtures/lint-with-problems_4.md',
                'exists' => true,
                'realpath' => '/fixtures/lint-with-problems.md',
                'num' => 4,
                'content' => <<<'MD'
                <?php
                class FinalClass {
                    private string $lastProperty='this is the value';
                }

                MD
            ],
        ];
    }

    public function testSniff()
    {
        $sniffer = new Sniffer();
        ob_start();
        $exitCode = $sniffer->run('PHP', false, true);
        $output = ob_get_clean();

        // There are auto-fixable problems
        $this->assertSame(2, $exitCode);

        // Check we didn't find problems where there aren't any
        $this->assertStringNotContainsString('nothing-to-lint', $output, 'nothing to lint, so nothing found');
        $this->assertStringNotContainsString('lint-but-no-problems', $output, 'linted but no problems found');
        $this->assertStringNotContainsString(
            'lint-with-problems_2',
            $output,
            'that code block has no linting problems'
        );

        // Check we did find problems where there are plenty
        $this->assertStringContainsString('lint-with-problems_1', $output);
        $this->assertStringContainsString('lint-with-problems_3', $output);
        $this->assertStringContainsString('lint-with-problems_4', $output);
    }

    private static function getFilesList(Sniffer $sniffer): FileList
    {
        $prepareConfig = new ReflectionMethod($sniffer, 'prepareConfig');
        $prepareConfig->setAccessible(true);
        $config = $prepareConfig->invoke($sniffer, false, 'PHP', true);

        return new FileList($config, new Ruleset($config));
    }
}
