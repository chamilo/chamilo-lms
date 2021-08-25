<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XApi\Fixtures\Json;

/**
 * JSON encoded xAPI result fixtures.
 *
 * These fixtures are borrowed from the
 * {@link https://github.com/adlnet/xAPI_LRS_Test Experience API Learning Record Store Conformance Test} package.
 */
class ResultJsonFixtures extends JsonFixtures
{
    const DIRECTORY = 'Result';

    public static function getEmptyResult()
    {
        return self::load('empty');
    }

    public static function getTypicalResult()
    {
        return self::load('typical');
    }

    public static function getScoreResult()
    {
        return self::load('score');
    }

    public static function getEmptyScoreResult()
    {
        return self::load('empty_score');
    }

    public static function getSuccessResult()
    {
        return self::load('success');
    }

    public static function getCompletionResult()
    {
        return self::load('completion');
    }

    public static function getResponseResult()
    {
        return self::load('response');
    }

    public static function getDurationResult()
    {
        return self::load('duration');
    }

    public static function getExtensionsResult()
    {
        return self::load('extensions');
    }

    public static function getEmptyExtensionsResult()
    {
        return self::load('empty_extensions');
    }

    public static function getScoreAndSuccessResult()
    {
        return self::load('score_and_success');
    }

    public static function getScoreAndCompletionResult()
    {
        return self::load('score_and_completion');
    }

    public static function getScoreAndResponseResult()
    {
        return self::load('score_and_response');
    }

    public static function getScoreAndDurationResult()
    {
        return self::load('score_and_duration');
    }

    public static function getSuccessAndCompletionResult()
    {
        return self::load('success_and_completion');
    }

    public static function getSuccessAndResponseResult()
    {
        return self::load('success_and_response');
    }

    public static function getSuccessAndDurationResult()
    {
        return self::load('success_and_duration');
    }

    public static function getCompletionAndResponseResult()
    {
        return self::load('completion_and_response');
    }

    public static function getCompletionAndDurationResult()
    {
        return self::load('completion_and_duration');
    }

    public static function getResponseAndDurationResult()
    {
        return self::load('response_and_duration');
    }

    public static function getScoreSuccessAndCompletionResult()
    {
        return self::load('score_success_and_completion');
    }

    public static function getScoreSuccessAndResponseResult()
    {
        return self::load('score_success_and_response');
    }

    public static function getScoreSuccessAndDurationResult()
    {
        return self::load('score_success_and_duration');
    }

    public static function getScoreCompletionAndResponseResult()
    {
        return self::load('score_completion_and_response');
    }

    public static function getScoreCompletionAndDurationResult()
    {
        return self::load('score_completion_and_duration');
    }

    public static function getScoreResponseAndDurationResult()
    {
        return self::load('score_response_and_duration');
    }

    public static function getSuccessCompletionAndResponseResult()
    {
        return self::load('success_completion_and_response');
    }

    public static function getSuccessCompletionAndDurationResult()
    {
        return self::load('success_completion_and_duration');
    }

    public static function getSuccessResponseAndDurationResult()
    {
        return self::load('success_response_and_duration');
    }

    public static function getCompletionResponseAndDurationResult()
    {
        return self::load('completion_response_and_duration');
    }

    public static function getScoreSuccessCompletionAndResponseResult()
    {
        return self::load('score_success_completion_and_response');
    }

    public static function getScoreSuccessCompletionAndDurationResult()
    {
        return self::load('score_success_completion_and_duration');
    }

    public static function getSuccessCompletionResponseAndDurationResult()
    {
        return self::load('success_completion_response_and_duration');
    }

    public static function getAllPropertiesResult()
    {
        return self::load('all_properties');
    }
}
