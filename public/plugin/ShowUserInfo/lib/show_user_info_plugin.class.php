<?php

/* For licensing terms, see /license.txt */

/**
 * Plugin that displays the current user's name in configured plugin regions.
 */
class ShowUserInfoPlugin extends Plugin
{
    protected function __construct()
    {
        parent::__construct(
            '1.2',
            'Julio Montoya',
            []
        );
    }

    public static function create(): self
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    public function get_info(): array
    {
        $info = parent::get_info();
        $info['title'] = $this->get_lang('plugin_title');
        $info['comment'] = $this->get_lang('plugin_comment');
        $info['supports_regions'] = true;

        return $info;
    }

    public function renderRegion($region): string
    {
        return $this->renderUserBlock();
    }

    public function renderUserBlock(): string
    {
        if (api_is_anonymous()) {
            return '';
        }

        $userId = api_get_user_id();

        if (empty($userId)) {
            return '';
        }

        $userInfo = api_get_user_info($userId);

        if (empty($userInfo) || empty($userInfo['complete_name'])) {
            return '';
        }

        $completeName = Security::remove_XSS((string) $userInfo['complete_name']);
        $title = Security::remove_XSS($this->get_lang('UserInformation'));
        $label = Security::remove_XSS($this->get_lang('SignedInAs'));
        $message = Security::remove_XSS(sprintf($this->get_lang('WelcomeUserX'), $completeName));

        return '<section class="show-user-info-plugin rounded-2xl border border-gray-25 bg-white p-4 shadow-sm">'
            .'<div class="flex items-center gap-4">'
            .'<div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary/10">'
            .'<span class="mdi mdi-account-circle ch-tool-icon text-2xl" aria-hidden="true"></span>'
            .'</div>'
            .'<div class="min-w-0">'
            .'<p class="m-0 text-xs font-semibold uppercase tracking-wide text-gray-50">'.$title.'</p>'
            .'<p class="m-0 text-base font-semibold text-gray-90">'.$message.'</p>'
            .'<p class="m-0 text-sm text-gray-50">'.$label.'</p>'
            .'</div>'
            .'</div>'
            .'</section>';
    }
}
