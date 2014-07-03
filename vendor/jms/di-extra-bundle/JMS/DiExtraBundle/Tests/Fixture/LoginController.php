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

namespace JMS\DiExtraBundle\Tests\Fixture;

use JMS\DiExtraBundle\Annotation\Inject;

/**
 * Login Controller.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class LoginController
{
    /**
     * @Inject("form.csrf_provider")
     */
    private $csrfProvider;

    /**
     * @Inject
     */
    private $rememberMeServices;

    /**
     * @Inject("security.context")
     */
    private $securityContext;

    /**
     * @Inject("security.authentication.trust_resolver")
     */
    private $trustResolver;

    public function loginAction()
    {
    }

    public function getCsrfProvider()
    {
        return $this->csrfProvider;
    }

    public function getRememberMeServices()
    {
        return $this->rememberMeServices;
    }

    public function getSecurityContext()
    {
        return $this->securityContext;
    }

    public function getTrustResolver()
    {
        return $this->trustResolver;
    }
}
