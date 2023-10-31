<?php

namespace SilverStripe\MD_PHP_CodeSniffer;

use PHP_CodeSniffer\Exceptions\DeepExitException;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Reports\Cbf;

class FixerReport extends Cbf
{
    /**
     * Generate a partial report for a single processed code block and store the result in the dummy files.
     *
     * Should return TRUE if it printed or stored data about the code block
     * and FALSE if it ignored the code block. Returning TRUE indicates that the code block and
     * its data should be counted in the grand totals.
     *
     * @param array $report Prepared report data.
     * @param \PHP_CodeSniffer\File $phpcsFile The file being reported on.
     * @param bool $showSources NOT USED
     * @param int $width NOT USED
     */
    public function generateFileReport($report, File $phpcsFile, $showSources = false, $width = 80): bool
    {
        $errors = $phpcsFile->getFixableCount();
        if ($errors !== 0) {
            if (PHP_CODESNIFFER_VERBOSITY > 0) {
                ob_end_clean();
                $startTime = microtime(true);
                echo "\t=> Fixing file: $errors/$errors violations remaining";
                if (PHP_CODESNIFFER_VERBOSITY > 1) {
                    echo PHP_EOL;
                }
            }

            $fixed = $phpcsFile->fixer->fixFile();
        }

        if ($phpcsFile->config->stdin === true) {
            // Replacing STDIN, so output current file to STDOUT
            // even if nothing was fixed. Exit here because we
            // can't process any more than 1 file in this setup.
            $fixedContent = $phpcsFile->fixer->getContents();
            throw new DeepExitException($fixedContent, 1);
        }

        if ($errors === 0) {
            return false;
        }

        if (PHP_CODESNIFFER_VERBOSITY > 0) {
            if ($fixed === false) {
                echo 'ERROR';
            } else {
                echo 'DONE';
            }

            $timeTaken = ((microtime(true) - $startTime) * 1000);
            if ($timeTaken < 1000) {
                $timeTaken = round($timeTaken);
                echo " in {$timeTaken}ms".PHP_EOL;
            } else {
                $timeTaken = round(($timeTaken / 1000), 2);
                echo " in $timeTaken secs".PHP_EOL;
            }
        }

        // NOTE: This is the only change from the parent method!
        // We've ripped out all of the code here which would have written changes to the file.
        // Instead, we need to find the old content for a given block and override that with
        // the new content. This is done back in the Sniffer class.

        if (PHP_CODESNIFFER_VERBOSITY > 0) {
            if ($fixed === true) {
                echo "\t=> Fixed content stored in memory".PHP_EOL;
            }
            ob_start();
        }

        $errorCount = $phpcsFile->getErrorCount();
        $warningCount = $phpcsFile->getWarningCount();
        $fixableCount = $phpcsFile->getFixableCount();
        $fixedCount = ($errors - $fixableCount);
        echo $report['filename'] . ">>$errorCount>>$warningCount>>$fixableCount>>$fixedCount" . PHP_EOL;

        return $fixed;

    }
}
