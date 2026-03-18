<?php
/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\PluginBundle\LtiProvider\Entity\Platform as ProviderPlatform;
use Chamilo\PluginBundle\LtiProvider\Entity\PlatformKey;
use Packback\Lti1p3\Interfaces\IDatabase;
use Packback\Lti1p3\Interfaces\ILtiDeployment;
use Packback\Lti1p3\Interfaces\ILtiRegistration;
use Packback\Lti1p3\LtiDeployment;
use Packback\Lti1p3\LtiRegistration;

class Lti13Database implements IDatabase
{
    public function findRegistrationByIssuer(string $iss, ?string $clientId = null): ?ILtiRegistration
    {
        $platform = $this->findPlatform($iss, $clientId);

        if (!$platform) {
            return null;
        }

        $privateKey = $this->getPrivateKey();

        if ('' === $privateKey) {
            return null;
        }

        return LtiRegistration::new([
            'issuer' => $platform->getIssuer(),
            'clientId' => $platform->getClientId(),
            'keySetUrl' => $platform->getKeySetUrl(),
            'authTokenUrl' => $platform->getAuthTokenUrl(),
            'authLoginUrl' => $platform->getAuthLoginUrl(),
            'toolPrivateKey' => $privateKey,
            'kid' => $platform->getKid(),
        ]);
    }

    public function findDeployment(string $iss, string $deploymentId, ?string $clientId = null): ?ILtiDeployment
    {
        $platform = $this->findPlatform($iss, $clientId);

        error_log(sprintf(
            '[LTI Provider] findDeployment iss="%s" clientId="%s" incomingDeployment="%s"',
            $iss,
            (string) $clientId,
            $deploymentId
        ));

        if (!$platform) {
            error_log('[LTI Provider] findDeployment platform not found');

            return null;
        }

        error_log(sprintf(
            '[LTI Provider] findDeployment storedDeployment="%s"',
            $platform->getDeploymentId()
        ));

        if ($platform->getDeploymentId() !== $deploymentId) {
            error_log('[LTI Provider] findDeployment deployment mismatch');

            return null;
        }

        return LtiDeployment::new($deploymentId);
    }

    private function findPlatform(string $issuer, ?string $clientId = null): ?ProviderPlatform
    {
        $em = Container::getEntityManager();
        $repo = $em->getRepository(ProviderPlatform::class);

        if (null !== $clientId && '' !== $clientId) {
            return $repo->findOneBy([
                'issuer' => $issuer,
                'clientId' => $clientId,
            ]);
        }

        return $repo->findOneBy([
            'issuer' => $issuer,
        ]);
    }

    private function getPrivateKey(): string
    {
        $em = Container::getEntityManager();

        /** @var PlatformKey|null $platformKey */
        $platformKey = $em
            ->getRepository(PlatformKey::class)
            ->findOneBy([]);

        if (!$platformKey) {
            return '';
        }

        return (string) $platformKey->getPrivateKey();
    }
}
