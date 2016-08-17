<?php
/**
 * Copyright 2014 Anthon Pang. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @package WebDriver
 *
 * @author Anthon Pang <apang@softwaredevelopment.ca>
 */

namespace Test\WebDriver;

use WebDriver\WebDriver;

/**
 * Test WebDriver\WebDriver class
 *
 * @package WebDriver
 */
class WebDriverTest extends \PHPUnit_Framework_TestCase
{
    private $driver;
    private $session;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->driver  = new WebDriver();
        $this->session = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        if ($this->session) {
            $this->session->close();
        }
    }

    /**
     * @group Functional
     */
    public function testSessions()
    {
        try {
	    $this->assertCount(0, $this->driver->sessions());

            $this->session = $this->driver->session();
        } catch (\Exception $e) {
            if (strpos($e->getMessage(),'Failed connect to localhost:4444; Connection refused') !== false
                || strpos($e->getMessage(), 'couldn\'t connect to host') !== false
            ) {
                $this->markTestSkipped('selenium server not running');
            } else {
                throw $e;
            }
        }

	$this->assertCount(1, $this->driver->sessions());
        $this->assertEquals('http://localhost:4444/wd/hub', $this->driver->getUrl());
    }

    /**
     * @group Functional
     */
    public function testStatus()
    {
        try {
            $status = $this->driver->status();
        } catch (\Exception $e) {
            if (strpos($e->getMessage(),'Failed connect to localhost:4444; Connection refused') !== false
                || strpos($e->getMessage(), 'couldn\'t connect to host') !== false
            ) {
                $this->markTestSkipped('selenium server not running');
            } else {
                throw $e;
            }
        }

        $this->assertCount(3, $status);
        $this->assertTrue(isset($status['java']));
        $this->assertTrue(isset($status['os']));
        $this->assertTrue(isset($status['build']));
    }
}
