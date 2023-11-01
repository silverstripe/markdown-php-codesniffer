<?php

namespace SilverStripe\MarkdownPhpCodeSniffer;

use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Files\DummyFile;
use PHP_CodeSniffer\Ruleset;

class CodeBlock extends DummyFile
{
    public int $num;

    public string $realPath;

    private string $finalContent = '';

    public function __construct(
        Ruleset $ruleset,
        Config $config,
        string $content,
        string $path,
        string $realPath,
        int $num
    ) {
        parent::__construct($content, $ruleset, $config);

        $this->path = $path;
        $this->realPath = $realPath;
        $this->num = $num;
    }

    public function cleanUp()
    {
        $this->finalContent = $this->content ?? '';
        parent::cleanUp();
    }

    public function getContent(): ?string
    {
        if ($this->content) {
            return $this->content;
        }

        return $this->finalContent;
    }
}
