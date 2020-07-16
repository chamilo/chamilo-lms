<?php
/* For license terms, see /license.txt */

$course_plugin = 'zoom'; // needed in order to load the plugin lang variables

require_once __DIR__.'/config.php';

if (!api_user_is_login()) {
    api_not_allowed(true);
}

$plugin = ZoomPlugin::create();

Display::display_header($plugin->get_title());

try {
    printf(
        '<div class="embed-responsive embed-responsive-16by9">
 <a class="embed-responsive-item" href="%s" target="_blank">%s</a>
</div>',
        $plugin->getGlobalMeetingURL(),
        get_lang('JoinGlobalVideoConference')
    );
} catch (Exception $exception) {
    Display::addFlash(
        Display::return_message($exception->getMessage(), 'error')
    );
}

Display::display_footer();
