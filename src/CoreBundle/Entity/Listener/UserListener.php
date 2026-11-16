<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Listener;

use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\DateTimeHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Service\Security\AtRestCrypto;
use Chamilo\CoreBundle\Service\Security\UserAtRestEncryptionPolicy;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserListener
{
    public function __construct(
        private UserRepository $userRepository,
        private Security $security,
        private readonly TranslatorInterface $translator,
        private AccessUrlHelper $accessUrlHelper,
        private readonly DateTimeHelper $dateTimeHelper,
        private readonly AtRestCrypto $atRestCrypto,
        private readonly UserAtRestEncryptionPolicy $encryptionPolicy,
    ) {}

    /**
     * This code is executed when a new user is created.
     */
    public function prePersist(User $user, PrePersistEventArgs $args): void
    {
        $this->userRepository->updateCanonicalFields($user);
        $this->userRepository->updatePassword($user);
        $this->prepareBiographyForInsert($user);
        $this->preparePrivateFieldForInsert($user, 'phone');
        $this->preparePrivateFieldForInsert($user, 'address');

        if ($user->getPortals()->isEmpty()) {
            $currentUrl = $this->accessUrlHelper->getCurrent();
            if (null !== $currentUrl) {
                $user->setCurrentUrl($currentUrl);
            }
        }

        if ($user->isSkipResourceNode()) {
            return;
        }

        if (!$user->hasResourceNode()) {
            // Check if creator is set with $resource->setCreator()
            $creator = $user->getResourceNodeCreator();
            if (null === $creator) {
                /** @var User|null $defaultCreator */
                $defaultCreator = $this->security->getUser();
                if (null !== $defaultCreator) {
                    $creator = $defaultCreator;
                } elseif (!empty($user->getCreatorId())) {
                    $creator = $this->userRepository->find($user->getCreatorId());
                }
            }

            if (null === $creator) {
                throw new UserNotFoundException('User creator not found, use $resource->setCreator();');
            }

            $em = $args->getObjectManager();
            $resourceNode = (new ResourceNode())
                ->setTitle($user->getUsername())
                ->setCreator($creator)
                ->setResourceType($this->userRepository->getResourceType())
            ;
            $em->persist($resourceNode);
            $user->setResourceNode($resourceNode);
        }
    }

    /**
     * Restore the transient plaintext value without marking Doctrine fields dirty.
     */
    public function postLoad(User $user, PostLoadEventArgs $args): void
    {
        $stored = $user->biographyStorageValue();
        $user->hydrateBiography(
            null !== $stored && $this->atRestCrypto->isEncrypted($stored)
                ? $this->atRestCrypto->decrypt($stored, $this->biographyContext($user))
                : $stored
        );
        $this->hydratePrivateField($user, 'phone');
        $this->hydratePrivateField($user, 'address');
    }

    /**
     * Encrypt changed biography content before Doctrine writes its change set.
     */
    public function preUpdate(User $user, PreUpdateEventArgs $args): void
    {
        $this->userRepository->updatePassword($user);
        $this->userRepository->updateCanonicalFields($user);
        $this->preparePrivateFieldForUpdate($user, $args, 'phone');
        $this->preparePrivateFieldForUpdate($user, $args, 'address');

        if (!$args->hasChangedField('biography')) {
            return;
        }

        $oldValue = $args->getOldValue('biography');
        $plaintext = $user->biographyStorageValue();
        if (null === $plaintext || '' === $plaintext) {
            return;
        }

        // Once a value was encrypted, edits must never downgrade it to plaintext,
        // even if the administrator later disables the field.
        if (!$this->encryptionPolicy->isEnabled('biography') && !(\is_string($oldValue) && $this->atRestCrypto->isEncrypted($oldValue))) {
            return;
        }

        $ciphertext = $this->atRestCrypto->encrypt($plaintext, $this->biographyContext($user));
        $user->setBiographyStorageValue($ciphertext);
        $args->setNewValue('biography', $ciphertext);
    }

    private function prepareBiographyForInsert(User $user): void
    {
        $plaintext = $user->biographyStorageValue();
        if (null === $plaintext || '' === $plaintext || !$this->encryptionPolicy->isEnabled('biography')) {
            return;
        }

        $user->setBiographyStorageValue(
            $this->atRestCrypto->encrypt($plaintext, $this->biographyContext($user))
        );
    }

    /**
     * Keep the mapped Doctrine property names untouched for hydration and DQL.
     * The non-persistent plaintext fields are populated in postLoad.
     */
    private function hydratePrivateField(User $user, string $field): void
    {
        $stored = 'phone' === $field ? $user->phoneStorageValue() : $user->addressStorageValue();
        $plaintext = null !== $stored && $this->atRestCrypto->isEncrypted($stored)
            ? $this->atRestCrypto->decrypt($stored, $this->privateFieldContext($user, $field))
            : $stored;

        if ('phone' === $field) {
            $user->hydratePhone($plaintext);
        } else {
            $user->hydrateAddress($plaintext);
        }
    }

    private function preparePrivateFieldForInsert(User $user, string $field): void
    {
        $plaintext = 'phone' === $field ? $user->phoneStorageValue() : $user->addressStorageValue();
        if (null === $plaintext || '' === $plaintext || !$this->encryptionPolicy->isEnabled($field)) {
            return;
        }
        $this->setPrivateFieldStorage(
            $user,
            $field,
            $this->atRestCrypto->encrypt($plaintext, $this->privateFieldContext($user, $field))
        );
    }

    private function preparePrivateFieldForUpdate(User $user, PreUpdateEventArgs $args, string $field): void
    {
        if (!$args->hasChangedField($field)) {
            return;
        }

        $oldValue = $args->getOldValue($field);
        $plaintext = 'phone' === $field ? $user->phoneStorageValue() : $user->addressStorageValue();
        if (null === $plaintext || '' === $plaintext) {
            return;
        }
        // An existing encrypted value must never downgrade when the setting is disabled.
        if (!$this->encryptionPolicy->isEnabled($field) && !(\is_string($oldValue) && $this->atRestCrypto->isEncrypted($oldValue))) {
            return;
        }

        $ciphertext = $this->atRestCrypto->encrypt($plaintext, $this->privateFieldContext($user, $field));
        $this->setPrivateFieldStorage($user, $field, $ciphertext);
        $args->setNewValue($field, $ciphertext);
    }

    private function setPrivateFieldStorage(User $user, string $field, string $value): void
    {
        if ('phone' === $field) {
            $user->setPhoneStorageValue($value);
        } else {
            $user->setAddressStorageValue($value);
        }
    }

    private function privateFieldContext(User $user, string $field): string
    {
        return 'user.'.$field.':'.$user->getUuid();
    }

    private function biographyContext(User $user): string
    {
        return 'user.biography:'.$user->getUuid();
    }

    public function preRemove(User $user, PreRemoveEventArgs $eventArgs): void
    {
        $this->deleteContentAndAttachmentsFromMessages($user, $eventArgs);
    }

    private function deleteContentAndAttachmentsFromMessages(User $user, PreRemoveEventArgs $eventArgs): void
    {
        $ob = $eventArgs->getObjectManager();

        $nowText = $this->dateTimeHelper->localTimeYmdHis(null, null, 'UTC');
        $messages = $user->getSentMessages();
        $newContent = \sprintf(
            $this->translator->trans('This message was deleted when the user was removed from the platform on %s'),
            $nowText
        );

        foreach ($messages as $message) {
            $message->setContent($newContent);

            $attachments = $message->getAttachments();

            foreach ($attachments as $attachment) {
                $ob->remove($attachment);
            }

            $message->setSender(null);
        }
    }
}
