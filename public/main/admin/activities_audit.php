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
require_once __DIR__.'/../inc/lib/reports.lib.php';

api_protect_admin_script();
ReportRegistry::assertCurrentUserCanAccessReport('security_activities_audit');

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
echo ReportRegistry::renderReportActionBar(
    'security_activities_audit',
    api_get_path(WEB_CODE_PATH).'admin/index.php'
);
echo Statistics::printActivitiesStats();
Display::display_footer();
