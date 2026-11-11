<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Regression test for the user-directory enumeration through
 * /api/gradebook/certificate-search.
 *
 * The endpoint is anonymous on purpose: an employer verifies a certificate
 * without an account. Its name search must therefore answer only with users
 * who hold a published certificate, and never with an unbounded list.
 */
class GradebookCertificateSearchApiSecurityTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private const string URL = '/api/gradebook/certificate-search';
    private const int MAX_USERS = 50;

    public function testSearchIsForbiddenWhenTheSettingIsDisabled(): void
    {
        $this->setSearchEnabled(false);

        static::createClient()->request('GET', self::URL, ['query' => ['lastname' => 'certsearch']]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAnonymousSeesOnlyHoldersOfAPublishedCertificate(): void
    {
        $this->setSearchEnabled(true);

        $category = $this->createCategory();
        $holder = $this->createUser('certsearch_holder');
        $unpublished = $this->createUser('certsearch_unpublished');
        $this->createUser('certsearch_none');
        $this->createCertificate($category, $holder, true);
        $this->createCertificate($category, $unpublished, false);

        $response = static::createClient()->request('GET', self::URL, ['query' => ['lastname' => 'certsearch_']]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSame(
            ['certsearch_holder'],
            array_column($response->toArray()['users'], 'lastname'),
        );
    }

    public function testCertificateOfASubCategoryDoesNotListTheUser(): void
    {
        $this->setSearchEnabled(true);

        $subCategory = $this->createCategory($this->createCategory());
        $this->createCertificate($subCategory, $this->createUser('certsearch_sub'), true);

        $response = static::createClient()->request('GET', self::URL, ['query' => ['lastname' => 'certsearch_sub']]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSame([], $response->toArray()['users']);
    }

    public function testUserIdDoesNotRevealAUserWithoutPublishedCertificate(): void
    {
        $this->setSearchEnabled(true);

        $category = $this->createCategory();
        $holder = $this->createUser('certsearch_id_holder');
        $unpublished = $this->createUser('certsearch_id_unpublished');
        $none = $this->createUser('certsearch_id_none');
        $this->createCertificate($category, $holder, true);
        $this->createCertificate($category, $unpublished, false);

        $client = static::createClient();

        $data = $client->request('GET', self::URL, ['query' => ['userId' => $holder->getId()]])->toArray();
        $this->assertSame('certsearch_id_holder', $data['selectedUser']['lastname']);

        // Iterating the numeric id must not disclose the names of the other users.
        foreach ([$unpublished, $none] as $user) {
            $data = $client->request('GET', self::URL, ['query' => ['userId' => $user->getId()]])->toArray();
            $this->assertArrayNotHasKey('selectedUser', $data);
            $this->assertSame('No user', $data['message']);
        }
    }

    public function testBroadSearchIsCapped(): void
    {
        $this->setSearchEnabled(true);

        $category = $this->createCategory();
        for ($i = 0; $i <= self::MAX_USERS; $i++) {
            $this->createCertificate($category, $this->createUser('certsearch_many_'.$i), true);
        }

        $response = static::createClient()->request('GET', self::URL, ['query' => ['lastname' => 'certsearch_many']]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $data = $response->toArray();
        $this->assertCount(self::MAX_USERS, $data['users']);
        $this->assertSame('Too many results. Please refine your search.', $data['message']);
    }

    private function setSearchEnabled(bool $enabled): void
    {
        self::getContainer()->get(SettingsManager::class)
            ->updateSetting('certificate.allow_certificates_search', $enabled ? 'true' : 'false')
        ;
    }

    private function createCategory(?GradebookCategory $parent = null): GradebookCategory
    {
        $category = (new GradebookCategory())
            ->setTitle('Certificate search')
            ->setCourse($this->createCourse('Certificate search '.uniqid()))
            ->setWeight(100)
            ->setVisible(true)
            ->setLocked(0)
            ->setGenerateCertificates(true)
            ->setParent($parent)
        ;
        $this->getEntityManager()->persist($category);
        $this->getEntityManager()->flush();

        return $category;
    }

    private function createCertificate(GradebookCategory $category, User $user, bool $publish): void
    {
        $certificate = (new GradebookCertificate())
            ->setCategory($category)
            ->setUser($user)
            ->setScoreCertificate(100)
            ->setCreator($user)
            ->setPublish($publish)
        ;
        $this->getEntityManager()->persist($certificate);
        $this->getEntityManager()->flush();
    }
}
