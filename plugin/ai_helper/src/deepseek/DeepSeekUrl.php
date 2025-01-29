<?php
/* For license terms, see /license.txt */

class DeepSeekUrl
{
    private const BASE_URL = 'https://api.deepseek.com/chat';

    /**
     * Get the endpoint URL for chat completions.
     *
     * @return string URL for the chat completions endpoint
     */
    public static function completionsUrl(): string
    {
        return self::BASE_URL.'/completions';
    }
}
