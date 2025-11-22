<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/validator.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Import\Base\Validator;

use const LIBXML_ERR_ERROR;
use const LIBXML_ERR_FATAL;
use const LIBXML_ERR_WARNING;

final class LibxmlErrorsMgr
{
    /**
     * @var bool Previous state for libxml_use_internal_errors.
     */
    private bool $previous = false;

    /**
     * Begins capturing libxml errors. Optionally resets the global ErrorMessages sink.
     *
     * @param bool $reset when true, clears previous ErrorMessages
     */
    public function __construct(bool $reset = false)
    {
        if ($reset) {
            ErrorMessages::instance()->reset();
        }
        // Enable internal error capture; keep previous state to restore later if needed.
        $this->previous = libxml_use_internal_errors(true);
        libxml_clear_errors();
    }

    public function __destruct()
    {
        $this->collectErrors();
        // Restore previous mode only if it was disabled before (so we don't disable if it was already enabled).
        if (false === $this->previous) {
            libxml_use_internal_errors($this->previous);
        }
    }

    /**
     * Manually collect current libxml errors.
     */
    public function collect(): void
    {
        $this->collectErrors();
    }

    /**
     * Reads libxml collected errors and pushes nicely formatted messages into ErrorMessages.
     *
     * @param string $filename optional filename to be included in messages
     *
     * @return array<int, string> collected error strings (not used by callers but handy for testing)
     */
    private function collectErrors(string $filename = ''): array
    {
        $errors = libxml_get_errors();
        static $error_types = [
            LIBXML_ERR_ERROR => 'Error',
            LIBXML_ERR_FATAL => 'Fatal Error',
            LIBXML_ERR_WARNING => 'Warning',
        ];
        $result = [];

        foreach ($errors as $error) {
            $add = '';
            if ('' !== $filename) {
                $add = " in {$filename}";
            } elseif (!empty($error->file)) {
                $add = " in {$error->file}";
            }
            $line = '';
            if (!empty($error->line)) {
                $line = " at line {$error->line}";
            }
            $level = $error_types[$error->level] ?? 'Notice';
            $err = "{$level}{$add}: {$error->message}{$line}";
            $result[] = $err;
            ErrorMessages::instance()->add($err);
        }

        libxml_clear_errors();

        return $result;
    }
}
