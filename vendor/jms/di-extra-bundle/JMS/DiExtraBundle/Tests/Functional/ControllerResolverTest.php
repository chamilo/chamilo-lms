<?php

/*
 * Copyright 2011 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\DiExtraBundle\Tests\Functional;

class ControllerResolverTest extends BaseTestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testLookupMethodIsCorrectlyImplemented()
    {
        $client = $this->createClient();
        $client->request('GET', '/register');

        $this->assertEquals('foo@bar.de', $client->getResponse()->getContent());
    }

    /**
     * @runInSeparateProcess
     */
    public function testLookupMethodAndAopProxy()
    {
        $client = $this->createClient();
        $client->request('GET', '/lookup-method-and-aop');

        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'), substr((string) $client->getResponse(), 0, 512));

        $client->insulate();
        $client->request('GET', '/lookup-method-and-aop');
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'), substr((string) $client->getResponse(), 0, 512));
    }

    /**
     * @runInSeparateProcess
     */
    public function testAopProxyWhenNoDiMetadata()
    {
        $client = $this->createClient();
        $client->request('GET', '/secure-action');

        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testAnnotationControllerServiceExtendingClassicService()
    {
        $client = $this->createClient();
        $client->request('GET', '/hello');

        $this->assertEquals('hello', $client->getResponse()->getContent());
    }
}
