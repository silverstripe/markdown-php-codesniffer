<?php

namespace SilverStripe\MD_PHP_CodeSniffer;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Node\Query;
use League\CommonMark\Parser\MarkdownParser;
use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Exceptions\DeepExitException;
use PHP_CodeSniffer\Files\DummyFile;
use PHP_CodeSniffer\Files\FileList;
use PHP_CodeSniffer\Reporter;
use PHP_CodeSniffer\Runner;
use PHP_CodeSniffer\Util\Common;

final class Sniffer
{
    private Query $query;

    private MarkdownParser $parser;

    public function __construct()
    {
        $this->query = new Query();
        $this->query->where(Query::type(FencedCode::class));
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());
        $this->parser = new MarkdownParser($environment);
    }

    public function run(string $lintLanguage, bool $usingExplicitStandard = false): int
    {
        // Prevents errors when unexpected args are passed in, and forces fixing OFF
        if (!defined('PHP_CODESNIFFER_CBF')) {
            define('PHP_CODESNIFFER_CBF', false);
        }

        $sniffer = new Runner();
        $sniffer->checkRequirements();
        $sniffer->config = $this->prepareConfig($usingExplicitStandard, $lintLanguage);
        $sniffer->init();
        $sniffer->reporter = new Reporter($sniffer->config);

        // Find all the relevant code blocks for linting
        // $files = $this->findMarkdownFiles($sniffer->config, $sniffer->ruleset);
        if (PHP_CODESNIFFER_VERBOSITY > 0) {
            echo 'Finding markdown files... ';
        }

        $files = new FileList($sniffer->config, $sniffer->ruleset);

        if (PHP_CODESNIFFER_VERBOSITY > 0) {
            $numFiles = count($files);
            echo "DONE ($numFiles files in queue)" . PHP_EOL;
        }

        $codeBlocks = $this->findFencedCodeblocks($files, $lintLanguage);

        // Add code blocks to the file list for linting
        $todo = [];
        foreach ($codeBlocks as $block) {
            $dummy = new DummyFile($block['content'], $sniffer->ruleset, $sniffer->config);
            $dummy->path = $block['path']; 
            $todo[$dummy->path] = $dummy;
        }

        // Do the actual linting
        $numErrors = $this->sniff($sniffer, $todo);
        $sniffer->reporter->printReports();

        if ($numErrors === 0) {
            // No errors found.
            return 0;
            /* we can't fix errors directly yet.
        } else if ($sniffer->reporter->totalFixable === 0) {
            // Errors found, but none of them can be fixed by PHPCBF.
            return 1;
        } else {
            // Errors found, and some can be fixed by PHPCBF.
            return 2;
        */
        }
        return 1;
    }

    private function prepareConfig(bool $usingExplicitStandard, string $lintLanguage): Config
    {
        // Creating the Config object populates it with all required settings based on the phpcs/phpcbf CLI arguments provided.
        $config = new Config();

        // We don't support STDIN for passing markdown in
        if ($config->stdin === true) {
            // 3 is the exit code phpcs uses for errors like this
            throw new DeepExitException('STDIN isn\'t supported', 3);
        }

        // Ensure we can find and lint markdown files
        $config->extensions = array_merge($config->extensions, ['md' => $lintLanguage]);
        // We're not passing the sniffer any real files, so caching could be unreliable
        $config->cache = false;
        // We must sniff all "files" sequentially - asyncronous sniffing isn't supported
        $config->parallel = 1;

        // If the user hasn't defined an explicit standard, and there's no default standards file to use,
        // use our customised PSR12 standard
        if (!$usingExplicitStandard && $config->standards === ['PEAR']) {
            $config->standards = [__DIR__ . '/../phpcs.default.xml'];
        }

        return $config;
    }

    /**
     * Finds all fenced codeblocks for the relevant language in all the markdown files
     */
    private function findFencedCodeblocks(FileList $paths, string $lintLanguage): array
    {
        if (PHP_CODESNIFFER_VERBOSITY > 0) {
            echo 'Finding fenced codeblocks... ';
        }

        $blocks = [];
        
        /** @var string $path */
        foreach ($paths as $path => $v) {
            $document = $this->parser->parse(file_get_contents($path));
            $codeBlocks = $this->query->findAll($document);

            $n = 0;
            /** @var FencedCode $block */
            foreach ($codeBlocks as $block) {
                if (strtoupper($block->getInfo()) !== $lintLanguage) {
                    continue;
                }
                // We only want to count relevant code blocks
                $n++;

                // $startAt is the line in the md file where the ```php line sits
                $startAt = $block->getStartLine();

                // Pad the content out so we have an accurate line count, and prepend a php code opening tag
                $content = str_repeat(PHP_EOL, $startAt - 1);
                $content .= '<?php' . PHP_EOL . $block->getLiteral();

                // Report each block separately (by making the path unique) so it's treated as its own file
                // This lets us lint for things like namespaces more easily
                $blocks[] = [
                    'content' => $content,
                    'path' => dirname($path) . '/' . basename($path, '.md') . "_{$n}" . '.md',
                ];
            }
        }

        if (PHP_CODESNIFFER_VERBOSITY > 0) {
            $numBlocks = count($blocks);
            echo "DONE ($numBlocks codeblocks in queue)" . PHP_EOL;
        }

        return $blocks;
    }

    /**
     * Run the codesniffing rules over the identified markdown codeblocks
     * 
     * This is very nearly a direct copy of Runner::run()
     */
    private function sniff(Runner $sniffer, array $todo): int
    {
        // Turn all sniff errors into exceptions.
        set_error_handler([$sniffer, 'handleErrors']);

        $lastDir  = '';
        $numFiles = count($todo);

        // Process each "file" sequentially - running sniff in parallel isn't supported
        // We're not actually running this across real files, but we should give the same output we'd get if we were.
        $numProcessed = 0;
        foreach ($todo as $path => $file) {
            if ($file->ignored === false) {
                $currDir = dirname($path);
                if ($lastDir !== $currDir) {
                    if (PHP_CODESNIFFER_VERBOSITY > 0) {
                        echo 'Changing into directory ' . Common::stripBasepath($currDir, $sniffer->config->basepath) . PHP_EOL;
                    }

                    $lastDir = $currDir;
                }
                
                $sniffer->processFile($file);
            } else if (PHP_CODESNIFFER_VERBOSITY > 0) {
                echo 'Skipping ' . basename($file->path) . PHP_EOL;
            }

            $numProcessed++;
            $sniffer->printProgress($file, $numFiles, $numProcessed);
        }

        restore_error_handler();

        if (PHP_CODESNIFFER_VERBOSITY === 0
            && $sniffer->config->interactive === false
            && $sniffer->config->showProgress === true
        ) {
            echo PHP_EOL . PHP_EOL;
        }

        $ignoreWarnings = Config::getConfigData('ignore_warnings_on_exit');
        $ignoreErrors = Config::getConfigData('ignore_errors_on_exit');

        $return = ($sniffer->reporter->totalErrors + $sniffer->reporter->totalWarnings);
        if ($ignoreErrors !== null) {
            $ignoreErrors = (bool) $ignoreErrors;
            if ($ignoreErrors === true) {
                $return -= $sniffer->reporter->totalErrors;
            }
        }

        if ($ignoreWarnings !== null) {
            $ignoreWarnings = (bool) $ignoreWarnings;
            if ($ignoreWarnings === true) {
                $return -= $sniffer->reporter->totalWarnings;
            }
        }

        return $return;
    }
}