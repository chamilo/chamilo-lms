<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Serializer\Tests;

use PHPUnit\Framework\TestCase;

abstract class SerializerTest extends TestCase
{
    protected function buildSerializeTestCases($objectType)
    {
        $tests = array();

        $phpFixturesClass = 'Xabbuh\XApi\DataFixtures\\'.$objectType.'Fixtures';
        $jsonFixturesClass = 'XApi\Fixtures\Json\\'.$objectType.'JsonFixtures';
        $jsonFixturesMethods = get_class_methods($jsonFixturesClass);

        foreach (get_class_methods($phpFixturesClass) as $method) {
            if (false !== strpos($method, 'ForQuery')) {
                continue;
            }

            // serialized data will always contain type information
            if (in_array($method.'WithType', $jsonFixturesMethods)) {
                $jsonMethod = $method.'WithType';
            } else {
                $jsonMethod = $method;
            }

            $tests[$method] = array(
                call_user_func(array($phpFixturesClass, $method)),
                call_user_func(array($jsonFixturesClass, $jsonMethod)),
            );
        }

        return $tests;
    }

    protected function buildDeserializeTestCases($objectType)
    {
        $tests = array();

        $jsonFixturesClass = 'XApi\Fixtures\Json\\'.$objectType.'JsonFixtures';
        $phpFixturesClass = 'Xabbuh\XApi\DataFixtures\\'.$objectType.'Fixtures';

        foreach (get_class_methods($jsonFixturesClass) as $method) {
            // PHP objects do not contain the type information as a dedicated property
            if ('WithType' === substr($method, -8)) {
                continue;
            }

            $tests[$method] = array(
                call_user_func(array($jsonFixturesClass, $method)),
                call_user_func(array($phpFixturesClass, $method)),
            );
        }

        return $tests;
    }
}
