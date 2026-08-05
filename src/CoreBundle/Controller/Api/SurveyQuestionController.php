<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

/**
 * Deprecated and intentionally left empty: every survey-question route is now
 * served directly by the SurveyQuestion API Platform resource
 * (src/CoreBundle/ApiResource/Survey/SurveyQuestion.php) through
 * SurveyQuestionProvider and SurveyQuestionProcessor. This controller only
 * duplicated those routes (its GET/POST/DELETE/move/copy routes were shadowed by
 * the API Platform ones, and its PUT route was unused). Kept as a placeholder to
 * be removed in a follow-up.
 */
final class SurveyQuestionController {}
