<?php

/* For licensing terms, see /license.txt */

require __DIR__.'/../../main/inc/global.inc.php';

$plugin = OAuth2::create();

if ('true' !== $plugin->get(OAuth2::SETTING_ENABLE)
    || !ChamiloSession::has('oauth2state')
    || !ChamiloSession::has('aouth2_authorization_url')
) {
    api_not_allowed(true);
}

$oauth2authorizationUrl = ChamiloSession::read('aouth2_authorization_url');

$htmlHeadXtra[] = '<meta http-equiv="refresh" content="15; url='.$oauth2authorizationUrl.'">';

ChamiloSession::erase('aouth2_authorization_url');

$content = '<div class="row">
        <div class="col-md-6 col-md-offset-3">
            <div class="alert alert-info text-center lead">
                <span class="fa fa-info-circle fa-2x fa-fw" aria-hidden="true"></span>
                '.$plugin->get_lang('MessageInfoAboutRedirectToProvider').'
                <hr>
                '.$plugin->get_lang('PleaseWaitThisCouldTakeAWhile').'
                <span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"></span>
            </div>
        </div>
    </div>
';

$template = new Template();
$template->assign('content', $content);
$template->display_one_col_template();
