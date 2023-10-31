<?php

namespace SilverStripe\MD_PHP_CodeSniffer;

use PHP_CodeSniffer\Files\DummyFile;

class CodeBlock extends DummyFile
{
    public int $num;

    public string $realPath;

    private string $finalContent = '';

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
