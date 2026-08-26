<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\AccessUrlRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserAuthSource;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * AccessUrlController's CSV import/remove actions previously required only plain ROLE_ADMIN, with
 * no check that the caller manages the target access_url at all -- a plain admin of one portal
 * could move users into any other portal in the install. Now: ROLE_GLOBAL_ADMIN is required, and
 * every target URL is checked against AccessUrlScopeHelper (unrestricted admins unaffected,
 * subtree admins confined to their own subtree).
 *
 * The auth-sources/* actions are deliberately NOT part of that tightening -- an auth source
 * identifies an authentication mechanism, not a URL/portal identity, so they remain plain
 * ROLE_ADMIN with no URL-ownership check. See testPlainAdminCanListAuthSourcesForAnyUrl().
 */
class AccessUrlControllerTest extends WebTestCase
{
    use ChamiloTestTrait;

    /**
     * @var string[]
     */
    private array $uploadedCsvPaths = [];

    protected function tearDown(): void
    {
        foreach ($this->uploadedCsvPaths as $path) {
            @unlink($path);
        }
        $this->uploadedCsvPaths = [];

        parent::tearDown();
    }

    private function createUserOnUrl(string $username, AccessUrl $url, string $role = ''): User
    {
        /** @var UserRepository $repo */
        $repo = self::getContainer()->get(UserRepository::class);
        $admin = $this->getAdmin();

        $user = $repo->createUser()
            ->setLastname($username)
            ->setFirstname($username)
            ->setUsername($username)
            ->setStatus(1)
            ->setPlainPassword($username)
            ->setEmail($username.'@example.com')
            ->setCreator($admin)
            ->setCurrentUrl($url)
            ->addAuthSourceByAuthentication(UserAuthSource::PLATFORM, $url)
        ;

        if ('' !== $role) {
            $user->addRole($role);
        }

        $repo->updateUser($user);

        return $user;
    }

    private function createChildUrl(): AccessUrl
    {
        /** @var AccessUrlRepository $urlRepo */
        $urlRepo = self::getContainer()->get(AccessUrlRepository::class);
        $admin = $this->getAdmin();
        $root = $this->getAccessUrl();

        $child = (new AccessUrl())
            ->setUrl('https://access-url-controller-'.uniqid().'.example.org/')
            ->setActive(1)
            ->setCreator($admin)
            ->setSuperior($root)
        ;
        $urlRepo->create($child);

        return $child;
    }

    private function csvUploadFile(string $username, string $url): UploadedFile
    {
        // The temp path itself needs no .csv suffix -- UploadedFile's 2nd ctor argument is what
        // the app sees as the original filename, independent of where the tmp data actually sits.
        $path = tempnam(sys_get_temp_dir(), 'access_url_import_test_');
        self::assertIsString($path);
        file_put_contents($path, "username,url\n{$username},{$url}\n");
        $this->uploadedCsvPaths[] = $path;

        return new UploadedFile($path, 'import.csv', 'text/csv', null, true);
    }

    private function isUserOnUrl(User $user, AccessUrl $url): bool
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $count = (int) $em->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(AccessUrlRelUser::class, 'r')
            ->where('r.user = :user')
            ->andWhere('r.url = :url')
            ->setParameter('user', $user->getId())
            ->setParameter('url', $url->getId())
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return $count > 0;
    }

    public function testPlainAdminIsRefusedEntirely(): void
    {
        $client = static::createClient();
        $plainAdmin = $this->createUserOnUrl('auc_plain_admin', $this->getAccessUrl(), 'ROLE_ADMIN');

        $client->loginUser($plainAdmin);
        $client->request('GET', '/access-url/users/import');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testScopedAdminCannotImportAUserIntoAUrlOutsideTheirSubtree(): void
    {
        $client = static::createClient();
        $child = $this->createChildUrl();
        $scopedAdmin = $this->createUserOnUrl('auc_scoped_import_admin', $child, 'ROLE_GLOBAL_ADMIN');
        $targetUser = $this->createUserOnUrl('auc_import_target', $child);
        $root = $this->getAccessUrl();

        $client->loginUser($scopedAdmin);
        $client->request(
            'POST',
            '/access-url/users/import',
            [],
            ['csv_file' => $this->csvUploadFile($targetUser->getUsername(), $root->getUrl())],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin']
        );

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('not found', (string) $client->getResponse()->getContent());
        $this->assertFalse($this->isUserOnUrl($targetUser, $root));
    }

    public function testUnrestrictedAdminCanImportAUserIntoAnyUrl(): void
    {
        $client = static::createClient();
        $child = $this->createChildUrl();
        $rootAdmin = $this->createUserOnUrl('auc_root_import_admin', $this->getAccessUrl(), 'ROLE_GLOBAL_ADMIN');
        $targetUser = $this->createUserOnUrl('auc_import_target_2', $this->getAccessUrl());

        $client->loginUser($rootAdmin);
        $client->request(
            'POST',
            '/access-url/users/import',
            [],
            ['csv_file' => $this->csvUploadFile($targetUser->getUsername(), $child->getUrl())],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin']
        );

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('successfully assigned', (string) $client->getResponse()->getContent());
        $this->assertTrue($this->isUserOnUrl($targetUser, $child));
    }

    public function testPlainAdminCanListAuthSourcesForAnyUrl(): void
    {
        $client = static::createClient();
        $child = $this->createChildUrl();
        // A plain admin registered only in the root URL -- not ROLE_GLOBAL_ADMIN, not registered
        // in $child at all -- must still be able to read auth sources for $child: auth source is
        // about authentication mechanism, not URL/portal ownership, so it is intentionally not
        // gated by AccessUrlScopeHelper.
        $plainAdmin = $this->createUserOnUrl('auc_plain_auth_admin', $this->getAccessUrl(), 'ROLE_ADMIN');

        $client->loginUser($plainAdmin);
        $client->request('GET', '/access-url/auth-sources/list', ['access_url' => '/api/access_urls/'.$child->getId()]);

        $this->assertResponseIsSuccessful();
    }
}
