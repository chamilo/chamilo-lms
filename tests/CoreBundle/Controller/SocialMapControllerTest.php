<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\AccessUrlRelPlugin;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Plugin;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Service\Social\SocialMapService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use const JSON_THROW_ON_ERROR;

final class SocialMapControllerTest extends WebTestCase
{
    use ChamiloTestTrait;

    public function testLocationFormats(): void
    {
        self::assertSame(['label' => 'QA city', 'address' => 'QA city', 'lat' => 0.0, 'lng' => 0.0], SocialMapService::parseLocation('QA city::0,0'));
        self::assertSame(['label' => 'QA city', 'address' => 'QA city', 'lat' => -3.5, 'lng' => -73.2], SocialMapService::parseLocation('{"label":"QA city","lat":-3.5,"lng":-73.2}'));
        self::assertSame(['label' => '0, 0', 'address' => '0, 0', 'lat' => 0.0, 'lng' => 0.0], SocialMapService::parseLocation('0,0'));
        self::assertSame(['label' => 'QA address', 'address' => 'QA address', 'lat' => null, 'lng' => null], SocialMapService::parseLocation('QA address'));
        self::assertNull(SocialMapService::parseLocation('91,181'));
        self::assertNull(SocialMapService::parseLocation('{"lat":[],"lng":0}'));
        self::assertNull(SocialMapService::parseLocation('{broken'));
        self::assertNull(SocialMapService::parseLocation(' '));
    }

    public function testAnonymousRequestCannotReadMarkers(): void
    {
        $client = self::createClient();
        $client->request('GET', '/social-network/map/markers');
        if (200 === $client->getResponse()->getStatusCode()) {
            // A platform guest may have a Symfony user token; it still must not see locations.
            self::assertSame(['markers' => [], 'missingFields' => []], json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR));
        } else {
            self::assertContains($client->getResponse()->getStatusCode(), [302, 303, 401, 403]);
        }
    }

    public function testMapHonoursProviderSettingsAndPortalAndFieldVisibility(): void
    {
        $client = self::createClient();
        $client->disableReboot();
        $em = $this->getEntityManager();
        self::assertSame('test', self::getContainer()->getParameter('kernel.environment'));
        $settings = self::getContainer()->get(SettingsManager::class);
        $portal = $this->getAccessUrl();
        self::assertNotNull($portal);
        $previous = [];
        foreach (['profile.allow_social_map_fields', 'profile.profile_fields_visibility', 'social.allow_social_tool'] as $name) {
            $previous[$name] = $settings->getSetting($name, true);
        }
        $plugin = $em->getRepository(Plugin::class)->createQueryBuilder('p')
            ->andWhere('LOWER(p.title) = :title')->setParameter('title', 'googlemaps')->getQuery()->getOneOrNullResult()
        ;
        $pluginCreated = null === $plugin;
        $plugin ??= (new Plugin())->setTitle('GoogleMaps')->setInstalledVersion('1.2');
        $wasInstalled = $plugin->isInstalled();
        $relation = $plugin->getConfigurationsByAccessUrl($portal);
        $relationCreated = null === $relation;
        $relation ??= $plugin->getOrCreatePluginConfiguration($portal);
        $wasActive = $relation->isActive();
        $oldConfiguration = $relation->getConfiguration();
        $fieldIds = [];
        $suffix = bin2hex(random_bytes(4));

        try {
            $plugin->setInstalled(true);
            $relation->setActive(true)->setConfiguration(['map_provider' => 'openstreetmap', 'api_key' => 'QA-never-return-this', 'default_latitude' => '0', 'default_longitude' => '0']);
            $em->persist($plugin);
            $em->persist($relation);
            $em->flush();
            $viewer = $this->createUser('map_viewer_'.$suffix, '', '', 'ROLE_STUDENT');
            $visibleUser = $this->createUser('map_visible_'.$suffix, '', '', 'ROLE_STUDENT');
            $inactiveUser = $this->createUser('map_inactive_'.$suffix, '', '', 'ROLE_STUDENT');
            $foreignUser = $this->createUser('map_foreign_'.$suffix, '', '', 'ROLE_STUDENT');
            $inactiveUser->setActive(User::INACTIVE);
            $child = (new AccessUrl())->setUrl('https://map-qa-'.$suffix.'.example.invalid/')->setActive(1)->setCreator($this->getAdmin())->setSuperior($portal);
            self::getContainer()->get(AccessUrlRepository::class)->create($child);
            $foreignUser->getPortals()->clear();
            $foreignUser->setCurrentUrl($child);
            $em->flush();
            $visibleId = $visibleUser->getId();
            $visible = $this->createMapField('map_public_'.$suffix, true);
            $private = $this->createMapField('map_private_'.$suffix, false);
            $hiddenByProfile = $this->createMapField('map_hidden_'.$suffix, true);
            $notListed = $this->createMapField('map_unlisted_'.$suffix, true);
            $fieldIds = [$visible->getId(), $private->getId(), $hiddenByProfile->getId(), $notListed->getId()];
            foreach ([[$visible, $visibleUser, 'QA public::0,0'], [$visible, $inactiveUser, 'QA inactive'], [$visible, $foreignUser, 'QA foreign'], [$private, $visibleUser, 'QA private'], [$hiddenByProfile, $visibleUser, 'QA hidden'], [$notListed, $visibleUser, 'QA unlisted']] as [$field, $user, $raw]) {
                $em->persist((new ExtraFieldValues())->setField($field)->setItemId((int) $user->getId())->setFieldValue($raw));
            }
            $em->flush();
            $settings->updateSetting('social.allow_social_tool', 'true');
            $settings->updateSetting('profile.profile_fields_visibility', json_encode(['options' => [$hiddenByProfile->getVariable() => false]], JSON_THROW_ON_ERROR));
            $settings->updateSetting('profile.allow_social_map_fields', json_encode(['fields' => [$visible->getVariable(), $private->getVariable(), $hiddenByProfile->getVariable(), 'map_missing_'.$suffix]], JSON_THROW_ON_ERROR));
            $client->loginUser($viewer);
            $client->request('GET', '/social-network/map/config');
            self::assertResponseIsSuccessful();
            $config = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
            self::assertTrue($config['enabled']);
            self::assertSame('openstreetmap', $config['provider']);
            self::assertNull($config['apiKey']);
            self::assertEquals(['lat' => 0, 'lng' => 0], $config['defaultCenter']);
            $client->request('GET', '/social-network/map/markers');
            self::assertResponseIsSuccessful();
            $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
            self::assertCount(1, $data['markers']);
            self::assertSame($visibleId, $data['markers'][0]['userId']);
            self::assertSame('QA public', $data['markers'][0]['label']);
            self::assertSame('/social?id='.$visibleId, $data['markers'][0]['profileUrl']);
            self::assertEquals(0, $data['markers'][0]['lat']);
            self::assertSame(['map_missing_'.$suffix], $data['missingFields']);
            self::assertStringContainsString('no-store', $client->getResponse()->headers->get('Cache-Control'));

            // Use the current manager after browser requests (the kernel may reset services).
            $em = $this->getEntityManager();
            $relation = $em->find(AccessUrlRelPlugin::class, $relation->getId());
            $relation->setConfiguration(['map_provider' => 'google_maps', 'enable_api' => 'true', 'api_key' => 'QA-browser-key', 'private_setting' => 'not-public']);
            $em->flush();
            $client->request('GET', '/social-network/map/config');
            self::assertResponseIsSuccessful();
            $config = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
            self::assertTrue($config['enabled']);
            self::assertSame('google_maps', $config['provider']);
            self::assertSame('QA-browser-key', $config['apiKey']);
            self::assertArrayNotHasKey('private_setting', $config);

            $em = $this->getEntityManager();
            $relation = $em->find(AccessUrlRelPlugin::class, $relation->getId());
            $relation->setConfiguration(['map_provider' => 'google_maps', 'enable_api' => 'true', 'api_key' => '']);
            $em->flush();
            $client->request('GET', '/social-network/map/config');
            self::assertResponseIsSuccessful();
            self::assertFalse(json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['enabled']);

            $em = $this->getEntityManager();
            $relation = $em->find(AccessUrlRelPlugin::class, $relation->getId());
            $relation->setConfiguration(['map_provider' => 'openstreetmap']);
            $em->flush();
            self::getContainer()->get(SettingsManager::class)->updateSetting('profile.allow_social_map_fields', '');
            $client->request('GET', '/social-network/map/markers');
            self::assertResponseIsSuccessful();
            self::assertSame(['markers' => [], 'missingFields' => []], json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR));
            self::getContainer()->get(SettingsManager::class)->updateSetting('profile.allow_social_map_fields', json_encode(['fields' => ['map_public_'.$suffix]], JSON_THROW_ON_ERROR));
            $em = $this->getEntityManager();
            $relation = $em->find(AccessUrlRelPlugin::class, $relation->getId());
            $relation->setActive(false);
            $em->flush();
            $client->request('GET', '/social-network/map/markers');
            self::assertResponseIsSuccessful();
            self::assertSame(['markers' => [], 'missingFields' => []], json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR));
            $client->request('GET', '/social-network/map/config');
            self::assertResponseIsSuccessful();
            $config = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
            self::assertFalse($config['enabled']);
            self::assertNull($config['apiKey']);
        } finally {
            $em = $this->getEntityManager();
            $settings = self::getContainer()->get(SettingsManager::class);
            foreach ($previous as $name => $value) {
                $settings->updateSetting($name, $value);
            }
            $plugin = null === $plugin->getId() ? null : $em->find(Plugin::class, $plugin->getId());
            if (null !== $plugin) {
                $relation = $plugin->getConfigurationsByAccessUrl($em->find(AccessUrl::class, $portal->getId()));
                if ($pluginCreated) {
                    $em->remove($plugin);
                } elseif ($relationCreated && null !== $relation) {
                    $plugin->removeConfigurationsInUrl($relation);
                } else {
                    $relation?->setActive($wasActive)->setConfiguration($oldConfiguration);
                }
                if (!$pluginCreated) {
                    $plugin->setInstalled($wasInstalled);
                }
            }
            foreach ($fieldIds as $id) {
                $field = $em->find(ExtraField::class, $id);
                if (null !== $field) {
                    foreach ($em->getRepository(ExtraFieldValues::class)->findBy(['field' => $field]) as $value) {
                        $em->remove($value);
                    }
                    $em->remove($field);
                }
            }
            $em->flush();
        }
    }

    private function createMapField(string $variable, bool $visible): ExtraField
    {
        $field = (new ExtraField())->setVariable($variable)->setDisplayText($variable)->setItemType(ExtraField::USER_FIELD_TYPE)->setValueType(ExtraField::FIELD_TYPE_TEXT)->setVisibleToOthers($visible);
        $em = $this->getEntityManager();
        $em->persist($field);
        $em->flush();

        return $field;
    }
}
