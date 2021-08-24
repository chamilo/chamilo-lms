<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/validator.php under GNU/GPL license */

final class LibxmlErrorsMgr
{
    /**
     * @var bool
     */
    private $previous = false;

    /**
     * @param bool $reset
     */
    public function __construct($reset = false)
    {
        if ($reset) {
            ErrorMessages::instance()->reset();
        }
        $this->previous = libxml_use_internal_errors(true);
        libxml_clear_errors();
    }

    public function __destruct()
    {
        $this->collectErrors();
        if (!$this->previous) {
            libxml_use_internal_errors($this->previous);
        }
    }

    public function collect()
    {
        $this->collectErrors();
    }

    private function collectErrors($filename = '')
    {
        $errors = libxml_get_errors();
        static $error_types = [
            LIBXML_ERR_ERROR => 'Error', LIBXML_ERR_FATAL => 'Fatal Error', LIBXML_ERR_WARNING => 'Warning',
        ];
        $result = [];
        foreach ($errors as $error) {
            $add = '';
            if (!empty($filename)) {
                $add = " in {$filename}";
            } elseif (!empty($error->file)) {
                $add = " in {$error->file}";
            }
            $line = '';
            if (!empty($error->line)) {
                $line = " at line {$error->line}";
            }
            $err = "{$error_types[$error->level]}{$add}: {$error->message}{$line}";
            ErrorMessages::instance()->add($err);
        }
        libxml_clear_errors();

        return $result;
    }
}
