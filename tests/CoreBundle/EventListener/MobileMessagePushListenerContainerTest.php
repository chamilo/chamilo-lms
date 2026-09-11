<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\EventListener;

use Chamilo\CoreBundle\EventListener\MobileMessagePushListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MobileMessagePushListenerContainerTest extends KernelTestCase
{
    public function testListenerIsRegisteredForDoctrineEvents(): void
    {
        self::bootKernel();

        $container = self::getContainer();

        self::assertTrue($container->has(MobileMessagePushListener::class));
        self::assertInstanceOf(
            MobileMessagePushListener::class,
            $container->get(MobileMessagePushListener::class)
        );

        $entityManager = $container->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        foreach ([Events::postPersist, Events::postFlush] as $eventName) {
            self::assertTrue(
                $this->containsMobileMessagePushListener(
                    $entityManager->getEventManager()->getListeners($eventName)
                ),
                \sprintf('MobileMessagePushListener is not registered for Doctrine event "%s".', $eventName)
            );
        }
    }

    /**
     * @param array<int, object> $listeners
     */
    private function containsMobileMessagePushListener(array $listeners): bool
    {
        foreach ($listeners as $listener) {
            if ($listener instanceof MobileMessagePushListener) {
                return true;
            }
        }

        return false;
    }
}
