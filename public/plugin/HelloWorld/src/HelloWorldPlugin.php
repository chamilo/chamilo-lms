<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

/**
 * Minimal Chamilo 2 example plugin.
 *
 * This plugin is intentionally simple: it can be assigned to a plugin region
 * and renders a small translated message using a configurable greeting.
 */
class HelloWorldPlugin extends Plugin
{
    public const SHOW_HELLO_WORLD = 'hello_world';
    public const SHOW_HELLO = 'hello';
    public const SHOW_HI = 'hi';

    protected function __construct()
    {
        parent::__construct(
            '2.0.0',
            'Chamilo',
            [
                'show_type' => [
                    'type' => 'select',
                    'options' => [
                        self::SHOW_HELLO_WORLD => 'option_hello_world',
                        self::SHOW_HELLO => 'option_hello',
                        self::SHOW_HI => 'option_hi',
                    ],
                    'translate_options' => true,
                ],
            ]
        );
    }

    public static function create(): self
    {
        static $instance = null;

        return $instance ??= new self();
    }

    public function get_info()
    {
        $info = parent::get_info();
        $info['supports_regions'] = true;

        return $info;
    }

    public function install(): void
    {
        // No database changes are required.
    }

    public function uninstall(): void
    {
        // No data is created by this plugin.
    }

    public function getConfiguredGreeting(): string
    {
        $showType = (string) ($this->get('show_type') ?: self::SHOW_HELLO_WORLD);

        if (!in_array($showType, [self::SHOW_HELLO_WORLD, self::SHOW_HELLO, self::SHOW_HI], true)) {
            $showType = self::SHOW_HELLO_WORLD;
        }

        return $this->getGreetingByType($showType);
    }

    private function getGreetingByType(string $showType): string
    {
        return match ($showType) {
            self::SHOW_HELLO => $this->get_lang('message_hello'),
            self::SHOW_HI => $this->get_lang('message_hi'),
            default => $this->get_lang('message_hello_world'),
        };
    }
}
