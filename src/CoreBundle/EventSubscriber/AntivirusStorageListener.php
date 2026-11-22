<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Security\Upload\AntivirusGuard;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\FileAbstraction\ReplacingFile;

// Must run before Vich's clean/upload listeners, not its pre_upload event.
#[AsDoctrineListener(event: Events::prePersist, priority: 1000, connection: 'default')]
#[AsDoctrineListener(event: Events::preUpdate, priority: 1000, connection: 'default')]
final readonly class AntivirusStorageListener
{
    public function __construct(
        private AntivirusGuard $guard
    ) {}

    public function prePersist(PrePersistEventArgs $event): void
    {
        $this->check($event->getObject());
    }

    public function preUpdate(PreUpdateEventArgs $event): void
    {
        $this->check($event->getObject());
    }

    private function check(object $object): void
    {
        if (!$object instanceof ResourceFile && !$object instanceof Asset) {
            return;
        }
        if ($object instanceof Asset && !$object->hasFile()) {
            return;
        }
        $file = $object->getFile();
        if (!$file instanceof UploadedFile && !$file instanceof ReplacingFile) {
            return;
        }
        $name = $file instanceof UploadedFile ? $file->getClientOriginalName() : $file->getFilename();
        $this->guard->assertPath($file->getPathname(), $name, $object instanceof Asset ? 'asset' : 'resource');
    }
}
