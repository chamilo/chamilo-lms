<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Career;
use Chamilo\CoreBundle\Entity\Promotion;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * The 1.11.x update_session webservice let integrations (EFC) attach a session to a promotion.
 * In 3.0 this is PATCH /api/sessions/{id} with the promotion's IRI, which only works because
 * Promotion is exposed as a read-only, admin-only API resource.
 */
class SessionPromotionApiTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testAdminAttachesAndDetachesAPromotion(): void
    {
        $session = $this->createSession('Session with promotion');
        $promotion = $this->createPromotion('Promotion 2026');
        $iri = '/api/promotions/'.$promotion->getId();

        $client = $this->createClientWithCredentials($this->getUserToken());
        $client->request('PATCH', '/api/sessions/'.$session->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['promotion' => $iri],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['promotion' => $iri]);

        // Reading the session back is how the integration checks which promotion it belongs to.
        $client->request('GET', '/api/sessions/'.$session->getId());
        $this->assertJsonContains(['promotion' => $iri]);

        $client->request('PATCH', '/api/sessions/'.$session->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['promotion' => null],
        ]);

        $this->assertResponseIsSuccessful();
        $reloaded = self::getContainer()->get(SessionRepository::class)->find($session->getId());
        $this->getEntityManager()->refresh($reloaded);
        $this->assertNull($reloaded->getPromotion(), 'null detaches the promotion.');
    }

    public function testUnknownPromotionIsRejected(): void
    {
        $session = $this->createSession('Session with unknown promotion');

        $client = $this->createClientWithCredentials($this->getUserToken());
        $client->request('PATCH', '/api/sessions/'.$session->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['promotion' => '/api/promotions/999999'],
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testPromotionsAreNotExposedToNonAdmins(): void
    {
        $promotion = $this->createPromotion('Hidden promotion');
        $student = $this->createUser('promotion_student');

        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($student));
        $client->request('GET', '/api/promotions/'.$promotion->getId());

        $this->assertResponseStatusCodeSame(403);
    }

    public function testPromotionsCannotBeWrittenThroughTheApi(): void
    {
        $client = $this->createClientWithCredentials($this->getUserToken());
        $client->request('POST', '/api/promotions', ['json' => ['title' => 'Created over API']]);

        // Promotions stay managed from the administration: no write operation is exposed.
        $this->assertResponseStatusCodeSame(405);
    }

    private function createPromotion(string $title): Promotion
    {
        $em = $this->getEntityManager();

        $career = (new Career())->setTitle('Career of '.$title)->setDescription('')->setStatus(1);
        $promotion = (new Promotion())->setTitle($title)->setDescription('')->setCareer($career);

        $em->persist($career);
        $em->persist($promotion);
        $em->flush();

        return $promotion;
    }
}
