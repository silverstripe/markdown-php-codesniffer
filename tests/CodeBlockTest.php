<?php

namespace SilverStripe\MarkdownPhpCodeSniffer\Test;

use ReflectionProperty;
use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Ruleset;
use PHPUnit\Framework\TestCase;
use SilverStripe\MarkdownPhpCodeSniffer\CodeBlock;

class CodeBlockTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (!defined('PHP_CODESNIFFER_CBF')) {
            define('PHP_CODESNIFFER_CBF', false);
        }
    }

    public function testGetContent()
    {
        $config = new Config();
        $block = new CodeBlock(new Ruleset($config), $config, 'This is the content', '', '', 0);

        $this->assertSame('This is the content', $block->getContent());

        $block->setContent('New content now');

        $this->assertSame('New content now', $block->getContent());
    }

    public function testCleanup()
    {
        $config = new Config();
        $block = new CodeBlock(new Ruleset($config), $config, 'This is the content', '', '', 0);
        $block->cleanUp();

        $this->assertSame('This is the content', $block->getContent());

        $reflectionContent = new ReflectionProperty($block, 'content');
        $reflectionContent->setAccessible(true);
        $reflectionFinalContent = new ReflectionProperty($block, 'finalContent');
        $reflectionFinalContent->setAccessible(true);

        $this->assertNull($reflectionContent->getValue($block));
        $this->assertSame('This is the content', $reflectionFinalContent->getValue($block));
    }
}
