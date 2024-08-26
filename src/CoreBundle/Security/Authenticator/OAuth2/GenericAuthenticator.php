<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authenticator\OAuth2;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\ServiceHelper\AuthenticationConfigHelper;
use ExtraField;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\GenericResourceOwner;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\ArrayAccessorTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use UnexpectedValueException;

class GenericAuthenticator extends AbstractAuthenticator
{
    use ArrayAccessorTrait;

    public const EXTRA_FIELD_OAUTH2_ID = 'oauth2_id';

    protected string $providerName = 'generic';

    public function __construct(
        ClientRegistry $clientRegistry,
        RouterInterface $router,
        UserRepository $userRepository,
        AuthenticationConfigHelper $authenticationConfigHelper,
        protected readonly ExtraFieldRepository $extraFieldRepository,
        protected readonly ExtraFieldValuesRepository $extraFieldValuesRepository,
    ) {
        parent::__construct(
            $clientRegistry,
            $router,
            $userRepository,
            $authenticationConfigHelper
        );
    }

    public function supports(Request $request): ?bool
    {
        return 'chamilo.oauth2_generic_check' === $request->attributes->get('_route');
    }

    protected function userLoader(AccessToken $accessToken): User
    {
        $providerParams = $this->authenticationConfigHelper->getParams('generic');

        /** @var GenericResourceOwner $resourceOwner */
        $resourceOwner = $this->client->fetchUserFromToken($accessToken);
        $resourceOwnerData = $resourceOwner->toArray();
        $resourceOwnerId = $resourceOwner->getId();

        if (empty($resourceOwnerId)) {
            throw new UnexpectedValueException('Value for the resource owner identifier not found at the configured key');
        }

        $fieldType = (int) ExtraField::getExtraFieldTypeFromString('user');
        $extraField = $this->extraFieldRepository->findByVariable($fieldType, self::EXTRA_FIELD_OAUTH2_ID);

        $existingUserExtraFieldValue = $this->extraFieldValuesRepository->findByVariableAndValue(
            $extraField,
            $resourceOwnerId
        );

        if (null === $existingUserExtraFieldValue) {
            $username = $this->getValueByKey(
                $resourceOwnerData,
                $providerParams['resource_owner_username_field'],
                "oauth2user_$resourceOwnerId"
            );

            /** @var User $user */
            $user = $this->userRepository->findOneBy(['username' => $username]);

            if (!$user || 'platform' !== $user->getAuthSource()) {
                if (!$providerParams['allow_create_new_users']) {
                    throw new AuthenticationException('This user doesn\'t have an account yet and auto-provisioning is not enabled. Please contact this portal administration team to request access.');
                }

                // set default values, real values are set in self::updateUserInfo method
                $user = (new User())
                    ->setFirstname('OAuth2 User default firstname')
                    ->setLastname('OAuth2 User default firstname')
                    ->setEmail('oauth2user_'.$resourceOwnerId.'@'.(gethostname() ?: 'localhost'))
                    ->setUsername($username)
                    ->setPlainPassword($username)
                    ->setStatus(STUDENT)
                    ->setCreatorId($this->userRepository->getRootUser()->getId())
                ;
            }

            $this->saveUserInfo($user, $resourceOwnerData, $providerParams);

            $this->extraFieldValuesRepository->updateItemData(
                $extraField,
                $user,
                $resourceOwnerId
            );
        } else {
            /** @var User $user */
            $user = $this->userRepository->find(
                $existingUserExtraFieldValue->getItemId()
            );

            if ($providerParams['allow_update_user_info']) {
                $this->saveUserInfo($user, $resourceOwnerData, $providerParams);
            }
        }

        return $user;
    }

    /**
     * Set user information from the resource owner's data or the user itself.
     */
    public function saveUserInfo(User $user, array $resourceOwnerData, array $providerParams): void
    {
        $status = $this->getUserStatus($resourceOwnerData, $user->getStatus(), $providerParams);

        $user
            ->setFirstname(
                $this->getValueByKey(
                    $resourceOwnerData,
                    $providerParams['resource_owner_firstname_field'],
                    $user->getFirstname()
                )
            )
            ->setLastname(
                $this->getValueByKey(
                    $resourceOwnerData,
                    $providerParams['resource_owner_lastname_field'],
                    $user->getLastname()
                )
            )
            ->setUsername(
                $this->getValueByKey(
                    $resourceOwnerData,
                    $providerParams['resource_owner_username_field'],
                    $user->getUsername()
                )
            )
            ->setEmail(
                $this->getValueByKey(
                    $resourceOwnerData,
                    $providerParams['resource_owner_email_field'],
                    $user->getEmail()
                )
            )
            ->setAuthSource('oauth2')
            ->setStatus($status)
            ->setRoleFromStatus($status)
        ;

        $this->userRepository->updateUser($user);
        // updateAccessUrls ?
    }

    private function getUserStatus(array $resourceOwnerData, int $defaultStatus, array $providerParams): int
    {
        $status = $this->getValueByKey(
            $resourceOwnerData,
            $providerParams['resource_owner_status_field'],
            $defaultStatus
        );

        $responseStatus = [];

        if ($teacherStatus = $providerParams['resource_owner_teacher_status_field']) {
            $responseStatus[COURSEMANAGER] = $teacherStatus;
        }

        if ($sessAdminStatus = $providerParams['resource_owner_sessadmin_status_field']) {
            $responseStatus[SESSIONADMIN] = $sessAdminStatus;
        }

        if ($drhStatus = $providerParams['resource_owner_hr_status_field']) {
            $responseStatus[DRH] = $drhStatus;
        }

        if ($studentStatus = $providerParams['resource_owner_status_status_field']) {
            $responseStatus[STUDENT] = $studentStatus;
        }

        if ($anonStatus = $providerParams['resource_owner_anon_status_field']) {
            $responseStatus[ANONYMOUS] = $anonStatus;
        }

        $map = array_flip($responseStatus);

        return $map[$status] ?? $status;
    }
}
