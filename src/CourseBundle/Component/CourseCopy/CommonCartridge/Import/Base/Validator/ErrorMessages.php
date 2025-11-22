<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/validator.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Import\Base\Validator;

use const PHP_EOL;

final class ErrorMessages
{
    /**
     * @var ErrorMessages|null
     */
    private static $instance;

    /**
     * @var array<string>
     */
    private array $items = [];

    private function __construct() {}
    private function __clone() {}

    /**
     * Casting to string: plain list (no HTML) by default.
     */
    public function __toString(): string
    {
        return $this->toString(false);
    }

    public static function instance(): self
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function add(string $msg): void
    {
        if ('' !== $msg) {
            $this->items[] = $msg;
        }
    }

    /**
     * @return array<string>
     */
    public function errors(): array
    {
        return $this->items;
    }

    /**
     * Empties the error collection.
     */
    public function reset(): void
    {
        $this->items = [];
    }

    /**
     * @param bool $web If true, wraps items in <ol><li> ... </li></ol>
     */
    public function toString(bool $web = false): string
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
