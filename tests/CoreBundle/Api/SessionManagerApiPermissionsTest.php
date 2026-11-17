<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use ApiPlatform\Symfony\Bundle\Test\Client;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Helpers\SessionManagerApiScope;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

use const JSON_THROW_ON_ERROR;

/**
 * Session managers must see their own session and course metadata, never
 * unrelated records or user extra fields, and must not gain write privileges.
 */
final class SessionManagerApiPermissionsTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testScopedReadCollectionsAndItems(): void
    {
        $settings = self::getContainer()->get(SettingsManager::class);
        $settings->updateSetting('session.allow_session_admins_to_manage_all_sessions', 'false');

        $suffix = uniqid('sm_api_', true);
        $manager = $this->createUser('sm_api_manager_'.str_replace('.', '_', $suffix), '', '', 'ROLE_SESSION_MANAGER');
        $otherManager = $this->createUser('sm_api_other_'.str_replace('.', '_', $suffix), '', '', 'ROLE_SESSION_MANAGER');
        $owned = $this->createSession('Session manager owned '.$suffix);
        $foreign = $this->createSession('Session manager foreign '.$suffix);
        // CourseRepository derives visual_code from the title (40-character DB limit).
        $courseSuffix = substr(str_replace('.', '', $suffix), -16);
        $ownedCourse = $this->createCourse('SM owned course '.$courseSuffix);
        $foreignCourse = $this->createCourse('SM foreign course '.$courseSuffix);

        // Even hidden courses may be returned to their session manager through
        // the scoped API collection, not to arbitrary other session managers.
        $ownedCourse->setVisibility(Course::HIDDEN);
        $foreignCourse->setVisibility(Course::HIDDEN);
        $owned->addSessionAdmin($manager)->addCourse($ownedCourse);
        $foreign->addSessionAdmin($otherManager)->addCourse($foreignCourse);

        $field = (new ExtraField())
            ->setItemType(ExtraField::COURSE_FIELD_TYPE)
            ->setValueType(ExtraField::FIELD_TYPE_TEXT)
            ->setVariable('sm_api_price_'.str_replace('.', '_', $suffix))
            ->setDisplayText('Price')
        ;
        $privateField = (new ExtraField())
            ->setItemType(ExtraField::USER_FIELD_TYPE)
            ->setValueType(ExtraField::FIELD_TYPE_TEXT)
            ->setVariable('sm_api_private_'.str_replace('.', '_', $suffix))
            ->setDisplayText('Private')
        ;
        $ownedValue = (new ExtraFieldValues())->setField($field)->setItemId($ownedCourse->getId())->setFieldValue('100');
        $foreignValue = (new ExtraFieldValues())->setField($field)->setItemId($foreignCourse->getId())->setFieldValue('200');
        $privateValue = (new ExtraFieldValues())->setField($privateField)->setItemId($manager->getId())->setFieldValue('private');

        $em = $this->getEntityManager();
        foreach ([$owned, $foreign, $ownedCourse, $foreignCourse, $field, $privateField, $ownedValue, $foreignValue, $privateValue] as $entity) {
            $em->persist($entity);
        }
        $em->flush();

        $scope = self::getContainer()->get(SessionManagerApiScope::class);
        self::assertContains($owned->getId(), $scope->getSessionIds($manager));
        self::assertNotContains($foreign->getId(), $scope->getSessionIds($manager));
        self::assertContains($ownedCourse->getId(), $scope->getCourseIds($manager));
        self::assertNotContains($foreignCourse->getId(), $scope->getCourseIds($manager));

        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($manager));

        // A 200 response is insufficient: collections must not contain another manager's rows.
        $collectionChecks = [
            '/api/sessions' => [$owned->getId(), $foreign->getId()],
            '/api/courses' => [$ownedCourse->getId(), $foreignCourse->getId()],
            '/api/extra_fields' => [$field->getId(), $privateField->getId()],
            '/api/extra_field_values' => [$ownedValue->getId(), $foreignValue->getId()],
        ];
        foreach ($collectionChecks as $route => [$allowedId, $deniedId]) {
            $ids = $this->getCollectionIds($client, $route);
            self::assertContains($allowedId, $ids, $route.' must include the authorized row.');
            self::assertNotContains($deniedId, $ids, $route.' must exclude the foreign row.');
        }
        self::assertNotContains($privateValue->getId(), $this->getCollectionIds($client, '/api/extra_field_values'));
        self::assertSame([], array_values(array_diff(
            $this->getCollectionIds($client, '/api/sessions'),
            $scope->getSessionIds($manager)
        )), 'No out-of-scope session may be present in the collection.');
        self::assertSame([], array_values(array_diff(
            $this->getCollectionIds($client, '/api/courses'),
            $scope->getCourseIds($manager)
        )), 'No out-of-scope course may be present in the collection.');

        foreach (['/api/sessions/'.$owned->getId(), '/api/courses/'.$ownedCourse->getId(),
            '/api/extra_fields/'.$field->getId(), '/api/extra_field_values/'.$ownedValue->getId()] as $route) {
            $client->request('GET', $route);
            $this->assertResponseIsSuccessful();
        }

        foreach (['/api/sessions/'.$foreign->getId(), '/api/courses/'.$foreignCourse->getId(),
            '/api/extra_fields/'.$privateField->getId(), '/api/extra_field_values/'.$foreignValue->getId(),
            '/api/extra_field_values/'.$privateValue->getId()] as $route) {
            $client->request('GET', $route);
            $this->assertResponseStatusCodeSame(403);
        }

        // The collection permission must not accidentally grant writes.
        $client->request('PATCH', '/api/extra_field_values/'.$ownedValue->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['fieldValue' => '999'],
        ]);
        $this->assertResponseStatusCodeSame(403);

        // An independent session manager must see the opposite scope.
        $otherClient = $this->createClientWithCredentials($this->getUserTokenFromUser($otherManager));
        self::assertContains($foreign->getId(), $this->getCollectionIds($otherClient, '/api/sessions'));
        self::assertNotContains($owned->getId(), $this->getCollectionIds($otherClient, '/api/sessions'));
        self::assertContains($foreignCourse->getId(), $this->getCollectionIds($otherClient, '/api/courses'));
        self::assertNotContains($ownedCourse->getId(), $this->getCollectionIds($otherClient, '/api/courses'));

        // Course description reads pass through CidReqListener before reaching
        // CourseDescriptionHelper. A hidden course must remain readable through
        // this one scoped API operation, not through general course navigation.
        foreach ([
            [$client, $ownedCourse, $owned, $foreignCourse, $foreign],
            [$otherClient, $foreignCourse, $foreign, $ownedCourse, $owned],
        ] as [$apiClient, $allowedCourse, $allowedSession, $deniedCourse, $deniedSession]) {
            foreach ([
                '/api/course-description/list?cid='.$allowedCourse->getId(),
                '/api/course-description/list?cid='.$allowedCourse->getId().'&sid='.$allowedSession->getId(),
            ] as $route) {
                $response = $apiClient->request('GET', $route);
                self::assertSame(200, $response->getStatusCode(), $route);
                $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
                self::assertSame($allowedCourse->getId(), $payload['courseId'] ?? null);
            }
            foreach ([
                '/api/course-description/list?cid='.$deniedCourse->getId(),
                '/api/course-description/list?cid='.$allowedCourse->getId().'&sid='.$deniedSession->getId(),
            ] as $route) {
                $response = $apiClient->request('GET', $route);
                self::assertSame(403, $response->getStatusCode(), $route);
            }
        }
    }

    public function testManageAllSettingDoesNotCrossAccessUrlBoundary(): void
    {
        $settings = self::getContainer()->get(SettingsManager::class);
        $settings->updateSetting('session.allow_session_admins_to_manage_all_sessions', 'false');

        $suffix = uniqid('sm_portal_', true);
        $manager = $this->createUser('sm_portal_manager_'.str_replace('.', '_', $suffix), '', '', 'ROLE_SESSION_MANAGER');
        $firstUrlSession = $this->createSession('First portal session '.$suffix);
        $url = (new AccessUrl())
            ->setUrl('https://sm-portal-'.str_replace('.', '-', $suffix).'.example.org/')
            ->setActive(1)
            ->setCreator($this->getAdmin())
            ->setSuperior($this->getAccessUrl())
        ;
        self::getContainer()->get(AccessUrlRepository::class)->create($url);

        $foreignUrlSession = (new Session())
            ->setTitle('Foreign portal session '.$suffix)
            ->addGeneralCoach($this->getAdmin())
            ->addAccessUrl($url)
        ;
        self::getContainer()->get(SessionRepository::class)->update($foreignUrlSession);

        $scope = self::getContainer()->get(SessionManagerApiScope::class);
        self::assertNotContains($firstUrlSession->getId(), $scope->getSessionIds($manager));

        try {
            $settings->updateSetting('session.allow_session_admins_to_manage_all_sessions', 'true');
            self::assertContains($firstUrlSession->getId(), $scope->getSessionIds($manager));
            self::assertNotContains($foreignUrlSession->getId(), $scope->getSessionIds($manager));
        } finally {
            $settings->updateSetting('session.allow_session_admins_to_manage_all_sessions', 'false');
        }
    }

    public function testNotificationCannotTargetAnUnmanagedSession(): void
    {
        self::getContainer()->get(SettingsManager::class)
            ->updateSetting('session.allow_session_admins_to_manage_all_sessions', 'false')
        ;

        $suffix = uniqid('sm_notify_', true);
        $manager = $this->createUser('sm_notify_manager_'.str_replace('.', '_', $suffix), '', '', 'ROLE_SESSION_MANAGER');
        $foreign = $this->createSession('Notification foreign '.$suffix);
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($manager));

        $client->request('POST', '/api/enrollment-notifications', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'user' => '/api/users/'.$manager->getId(),
                'itemType' => 'session',
                'itemId' => $foreign->getId(),
            ],
        ]);

        // The enrollment is not enough: authorization must prevent sending.
        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @return list<int>
     */
    private function getCollectionIds(Client $client, string $route): array
    {
        $next = $route.'?itemsPerPage=1000';
        $ids = [];
        $visited = [];
        $pageCount = 0;
        while (null !== $next) {
            self::assertArrayNotHasKey($next, $visited, 'Pagination must not loop.');
            $visited[$next] = true;
            self::assertLessThan(101, ++$pageCount, 'Collection exceeded 100 pages.');
            $response = $client->request('GET', $next, [
                'headers' => ['Accept' => 'application/ld+json'],
            ]);
            self::assertSame(200, $response->getStatusCode(), $next);

            $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $members = $payload['hydra:member'] ?? $payload['member'] ?? null;
            self::assertIsArray($members, $route.' must return an API Platform collection.');

            foreach ($members as $member) {
                $iri = (string) ($member['@id'] ?? '');
                self::assertMatchesRegularExpression('#/\d+$#', $iri);
                $ids[] = (int) substr($iri, (int) strrpos($iri, '/') + 1);
                if ('/api/extra_fields' === $route) {
                    self::assertNotSame(ExtraField::USER_FIELD_TYPE, (int) ($member['itemType'] ?? -1));
                }
            }

            $view = $payload['hydra:view'] ?? $payload['view'] ?? [];
            $next = $view['hydra:next'] ?? $view['next'] ?? null;
        }

        return $ids;
    }
}
