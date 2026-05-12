<?php

/* For licensing terms, see /license.txt */

/**
 * Activities audit report.
 *
 * This page exposes the former Statistics > Important activities report as an
 * audit page linked from the Administration > Security block.
 */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';
require_once __DIR__.'/../inc/lib/statistics.lib.php';

api_protect_admin_script();

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];

$tool_name = get_lang('Activities audit');

$htmlHeadXtra[] = <<<JS
<script>
(function () {
  "use strict";
  $(document).on("click", "a.js-user-details-link", function (e) {
    e.stopImmediatePropagation();
  });
})();
</script>
JS;

Display::display_header($tool_name);
echo Display::page_header($tool_name);
echo Statistics::printActivitiesStats();
Display::display_footer();
