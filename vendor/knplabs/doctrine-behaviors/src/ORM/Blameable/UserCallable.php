<?php

/**
 * This file is part of the KnpDoctrineBehaviors package.
 *
 * (c) KnpLabs <http://knplabs.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Knp\DoctrineBehaviors\ORM\Blameable;

use Symfony\Component\DependencyInjection\Container;

/**
 * UserCallable can be invoked to return a blameable user
 */
class UserCallable
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @param callable
     * @param string $userEntity
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function __invoke()
    {
        $token = $this->container->get('security.token_storage')->getToken();
        if (null !== $token) {
            return $token->getUser();
        }
    }
}
