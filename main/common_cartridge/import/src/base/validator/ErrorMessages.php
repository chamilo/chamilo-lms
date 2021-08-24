<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/validator.php under GNU/GPL license */

final class ErrorMessages
{
    /**
     * @static ErrorMessages
     */
    private static $instance = null;

    /**
     * @var array
     */
    private $items = [];

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     * Casting to string method.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString(false);
    }

    /**
     * @return ErrorMessages
     */
    public static function instance()
    {
        if (empty(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c();
        }

        return self::$instance;
    }

    /**
     * @param string $msg
     */
    public function add($msg)
    {
        if (!empty($msg)) {
            $this->items[] = $msg;
        }
    }

    /**
     * @return array
     */
    public function errors()
    {
        $this->items;
    }

    /**
     * Empties the error content.
     */
    public function reset()
    {
        $this->items = [];
    }

    /**
     * @param bool $web
     *
     * @return string
     */
    public function toString($web = false)
    {
        $result = '';
        if ($web) {
            $result .= '<ol>'.PHP_EOL;
        }
        foreach ($this->items as $error) {
            if ($web) {
                $result .= '<li>';
            }

            $result .= $error.PHP_EOL;

            if ($web) {
                $result .= '</li>'.PHP_EOL;
            }
        }
        if ($web) {
            $result .= '</ol>'.PHP_EOL;
        }

        return $result;
    }
}
