<?php

/* For licensing terms, see /license.txt */

/**
 * Canonical report entry point.
 *
 * This router gives reports a cohesive URL while preserving the original
 * legacy report implementations. It also applies the documented role matrix
 * for links opened through the reports catalog.
 */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';
require_once __DIR__.'/../inc/lib/reports.lib.php';

$reportId = isset($_GET['id']) ? (string) $_GET['id'] : '';
if ('' === $reportId) {
    header('Location: '.api_get_path(WEB_CODE_PATH).'admin/reports_catalog.php');
    exit;
}

$report = ReportRegistry::assertCurrentUserCanAccessReport($reportId);

$query = $_GET;
unset($query['id']);

$target = ReportRegistry::getLegacyUrl($report);
if (!empty($query)) {
    $target .= (str_contains($target, '?') ? '&' : '?').http_build_query($query);
}

header('Location: '.$target);
exit;
