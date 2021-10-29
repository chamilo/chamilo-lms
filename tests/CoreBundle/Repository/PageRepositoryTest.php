<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Page;
use Chamilo\CoreBundle\Entity\PageCategory;
use Chamilo\CoreBundle\Repository\PageRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;

class PageRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): Page
    {
        $em = $this->getEntityManager();
        $pageRepo = self::getContainer()->get(PageRepository::class);
        $defaultCount = $pageRepo->count([]);

        $user = $this->getAdmin();
        $url = $this->getAccessUrl();

        $category = (new PageCategory())
            ->setCreator($user)
            ->setTitle('category1')
            ->setType('simple')
            ->setCreatedAt(new DateTime())
            ->setUpdatedAt(new DateTime())
        ;
        $this->assertHasNoEntityViolations($category);
        $em->persist($category);

        $page = (new Page())
            ->setTitle('page1')
            ->setContent('page1 content')
            ->setCreator($user)
            ->setUrl($url)
            ->setPosition(0)
            ->setCategory($category)
            ->setSlug('english')
            ->setLocale('en')
            ->setEnabled(true)
            ->setCreatedAt(new DateTime())
            ->setUpdatedAt(new DateTime())
        ;
        $this->assertHasNoEntityViolations($page);
        $em->persist($page);

        $collection = new ArrayCollection();
        $collection->add($page);
        $category->setPages($collection);
        $em->persist($category);
        $em->flush();

        $this->assertSame(0, $page->getPosition());
        // 2 pages are already created during installation.
        $this->assertSame($defaultCount + 1, $pageRepo->count([]));

        $category2 = (new PageCategory())
            ->setCreator($user)
            ->setTitle('category2')
            ->setType('simple')
            ->setCreatedAt(new DateTime())
            ->setUpdatedAt(new DateTime())
        ;

        $this->assertHasNoEntityViolations($category2);
        $em->persist($category2);

        $pageFrench = (new Page())
            ->setTitle("l'êtê")
            ->setContent('french content')
            ->setCreator($user)
            ->setUrl($url)
            ->setCategory($category2)
            ->setLocale('fr')
            ->setEnabled(true)
            ->setCreatedAt(new DateTime())
            ->setUpdatedAt(new DateTime())
        ;
        $this->assertHasNoEntityViolations($pageFrench);
        $em->persist($pageFrench);
        $em->flush();

        $this->assertSame(0, $pageFrench->getPosition());
        $this->assertSame('fr', $pageFrench->getLocale());
        $this->assertSame('lete', $pageFrench->getSlug());
        $this->assertSame($defaultCount + 2, $pageRepo->count([]));

        return $page;
    }

    public function testAddAnotherPage(): void
    {
        $page = $this->testCreate();
        $em = $this->getEntityManager();
        $pageRepo = self::getContainer()->get(PageRepository::class);

        $defaultCount = $pageRepo->count([]);

        /** @var Page $page */
        $page = $pageRepo->find($page->getId());

        $url = $this->getAccessUrl();
        $user = $this->getAdmin();

        $anotherPage = (new Page())
            ->setTitle('page2')
            ->setContent('page2 content')
            ->setUrl($url)
            ->setCreator($user)
            ->setLocale('en')
            ->setEnabled(true)
            ->setCategory($page->getCategory())
        ;
        $this->assertHasNoEntityViolations($anotherPage);
        $em->persist($anotherPage);
        $em->flush();

        $this->assertSame($defaultCount + 1, $pageRepo->count([]));
        $this->assertSame(1, $anotherPage->getPosition());
        $this->assertNotNull($anotherPage->getCategory());
    }

    public function testUpdate(): void
    {
        $page = $this->testCreate();
        $pageRepo = self::getContainer()->get(PageRepository::class);
        $defaultCount = $pageRepo->count([]);

        $page->setLocale('fr');
        $pageRepo->update($page);

        $this->assertSame('fr', $page->getLocale());
        $this->assertSame($defaultCount, $pageRepo->count([]));
    }

    public function testDelete(): void
    {
        $page = $this->testCreate();
        $pageRepo = self::getContainer()->get(PageRepository::class);
        $defaultCount = $pageRepo->count([]);
        $pageRepo->delete($page);
        $this->assertSame($defaultCount - 1, $pageRepo->count([]));
    }

    public function testGetPages(): void
    {
        $this->testAddAnotherPage();

        $token = $this->getUserToken([]);
        $this->createClientWithCredentials($token)->request('GET', '/api/pages');
        $this->assertResponseIsSuccessful();

        $response = $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/pages',
            [
                'query' => [
                    'locale' => 'en',
                ],
            ]
        );
        $this->assertResponseIsSuccessful();

        // Asserts that the returned content type is JSON-LD (the default)
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        // Asserts that the returned JSON is a superset of this one
        $this->assertJsonContains([
            '@context' => '/api/contexts/Page',
            '@id' => '/api/pages',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 2,
        ]);

        $this->assertCount(2, $response->toArray()['hydra:member']);
        $this->assertMatchesResourceCollectionJsonSchema(Page::class);

        $response = $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/pages',
            [
                'query' => [
                    'locale' => 'en',
                    'category.title' => 'category1',
                ],
            ]
        );
        $this->assertCount(2, $response->toArray()['hydra:member']);
        $this->assertJsonContains([
            '@context' => '/api/contexts/Page',
            '@id' => '/api/pages',
            '@type' => 'hydra:Collection',
            'hydra:member' => [
                [
                    '@type' => 'Page',
                    'title' => 'page1',
                ],
                [
                    '@type' => 'Page',
                    'title' => 'page2',
                ],
            ],
        ]);

        $response = $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/pages',
            [
                'query' => [
                    'locale' => 'fr',
                ],
            ]
        );
        $this->assertCount(1, $response->toArray()['hydra:member']);
    }

    public function testAddPage(): void
    {
        $user = $this->getAdmin();
        $url = $this->getAccessUrl();

        $url = $this->findIriBy(AccessUrl::class, ['id' => $url->getId()]);
        $token = $this->getUserToken([]);
        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/pages',
            [
                'json' => [
                    'creator' => $user->getIri(),
                    'url' => $url,
                    'locale' => 'en',
                    'title' => 'my post',
                    'content' => 'hello',
                ],
            ]
        );
        $this->assertResponseStatusCodeSame(201);
    }

    public function testGetPage(): void
    {
        $page = $this->testCreate();
        $iri = $this->findIriBy(Page::class, ['id' => $page->getId()]);

        $token = $this->getUserToken([]);
        $this->createClientWithCredentials($token)->request('GET', $iri);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => $iri,
            '@type' => 'Page',
            'title' => 'page1',
            'content' => 'page1 content',
            '@context' => '/api/contexts/Page',
        ]);
    }

    public function testDeletePage(): void
    {
        $page = $this->testCreate();
        $iri = $this->findIriBy(Page::class, ['id' => $page->getId()]);

        $token = $this->getUserToken([]);
        $this->createClientWithCredentials($token)->request(
            'DELETE',
            $iri
        );

        $this->assertResponseStatusCodeSame(204);

        $iri = $this->findIriBy(Page::class, ['id' => $page->getId()]);
        $this->assertNull($iri);
    }
}
