<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use DocumentManager;
use learnpath;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Pins learnpath::get_mediaplayer() (HTML of the audio player, audio resolved through the document resources)
 * and the working path of learnpath::create_document() with a logged user.
 */
final class LearnpathFixCharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private const COURSE_ID = 1;
    private const USER_ID = 1;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/lp/lp_controller.php');
        $request->setSession($session);
        Container::setRequest($request);
        $session->set('_real_cid', self::COURSE_ID);
        $session->set('_cid', 'TEMP');
        $session->set('_course', api_get_course_info_by_id(self::COURSE_ID));
        $session->set('_user', ['id' => self::USER_ID]);
        $user = $this->getEntityManager()->find(User::class, self::USER_ID);
        $container->get('security.token_storage')->setToken(
            new UsernamePasswordToken($user, 'main', $user->getRoles())
        );
    }

    private function newLp(string $title): learnpath
    {
        $conn = Database::getManager()->getConnection();
        $conn->executeStatement(
            "INSERT INTO c_lp (lp_type, title, path, force_commit, content_maker, content_license, js_lib, debug, theme,
                author, prerequisite, hide_toc_frame, seriousgame_mode, autolaunch, max_attempts, subscribe_users,
                created_on, modified_on)
             VALUES (1, ?, '', 0, '', '', '', 0, '', '', 0, 0, 0, 0, 0, 0, '2030-01-01 00:00:00', '2030-01-01 00:00:00')",
            [$title]
        );
        $lpId = (int) $conn->lastInsertId();
        $em = $this->getEntityManager();
        $em->persist(
            (new CLpItem())
                ->setTitle('root')
                ->setPath('root')
                ->setLp($em->find(CLp::class, $lpId))
                ->setItemType('root')
        );
        $em->flush();
        $em->clear();

        return new learnpath($em->find(CLp::class, $lpId), api_get_course_info_by_id(self::COURSE_ID), self::USER_ID);
    }

    public function testCreateDocumentReturnsTheIdOfTheNewHtmlDocument(): void
    {
        $lp = $this->newLp('fj_create_document');

        $id = $lp->create_document(
            api_get_course_info_by_id(self::COURSE_ID),
            '<html><body><p>Hello</p></body></html>',
            'fj page '.uniqid(),
            'html'
        );

        $this->assertGreaterThan(0, $id, 'create_document must return the new document id');
        $document = $this->getEntityManager()->find(CDocument::class, $id);
        $this->assertInstanceOf(CDocument::class, $document);
        $this->assertSame('html', $document->getFiletype());
        $this->assertStringStartsWith('fj-page', $document->getTitle());
    }

    public function testGetMediaplayerRendersTheAudioPlayerOfTheItemAudioDocument(): void
    {
        $courseInfo = api_get_course_info_by_id(self::COURSE_ID);
        $em = $this->getEntityManager();
        $conn = Database::getManager()->getConnection();

        $name = 'fj'.uniqid().'.mp3';
        $tmp = tempnam(sys_get_temp_dir(), 'fjmp3');
        file_put_contents($tmp, 'ID3 not really an mp3');
        $folder = DocumentManager::addDocument($courseInfo, '/audio', 'folder', 0, 'audio', null, 0, true, 0, 0, self::USER_ID, false);
        $this->assertNotFalse($folder);
        $audioDoc = DocumentManager::addDocument(
            $courseInfo,
            '/audio/'.$name,
            'file',
            filesize($tmp),
            $name,
            null,
            0,
            true,
            0,
            0,
            self::USER_ID,
            false,
            '',
            $folder->getIid(),
            $tmp
        );
        $this->assertNotFalse($audioDoc);
        @unlink($tmp);

        $lp = $this->newLp('fj_mediaplayer');
        $lpId = $lp->get_id();
        $root = $conn->fetchOne("SELECT iid FROM c_lp_item WHERE lp_id = ? AND path = 'root'", [$lpId]);
        $itemId = (int) $lp->add_item($em->find(CLpItem::class, $root), 0, 'document', 0, 'fj item');
        $conn->executeStatement('UPDATE c_lp_item SET audio = ? WHERE iid = ?', ['/audio/'.$name, $itemId]);
        $em->clear();
        $lp = new learnpath($em->find(CLp::class, $lpId), $courseInfo, self::USER_ID);
        $_SESSION['oLP'] = $lp;

        $this->assertNotEmpty(api_get_course_info(), 'course context');
        $this->assertArrayHasKey($itemId, $lp->items, 'item loaded');
        $this->assertSame('/audio/'.$name, $lp->items[$itemId]->audio, 'audio loaded');

        $html = $lp->get_mediaplayer($itemId, 'true');

        $expectedUrl = Container::getDocumentRepository()->getResourceFileUrl(
            Container::getDocumentRepository()->find($audioDoc->getIid())
        );
        $this->assertStringStartsWith('<div id="container"><audio id="lp_audio_media_player"', $html);
        $this->assertStringContainsString(' controls autoplay ', $html);
        $this->assertStringContainsString('width="100%"', $html);
        $this->assertStringContainsString('src="'.$expectedUrl.'"', $html);
        $this->assertStringEndsWith('</audio></div>', $html);

        // Old structure: only the file name is stored, the file lives in the "audio" folder.
        $conn->executeStatement('UPDATE c_lp_item SET audio = ? WHERE iid = ?', [$name, $itemId]);
        $em->clear();
        $lp = new learnpath($em->find(CLp::class, $lpId), $courseInfo, self::USER_ID);
        $_SESSION['oLP'] = $lp;
        $this->assertStringContainsString('src="'.$expectedUrl.'"', $lp->get_mediaplayer($itemId, 'true'));

        // An audio whose document does not exist renders nothing instead of a broken player.
        $conn->executeStatement('UPDATE c_lp_item SET audio = ? WHERE iid = ?', ['/audio/missing-'.$name, $itemId]);
        $em->clear();
        $lp = new learnpath($em->find(CLp::class, $lpId), $courseInfo, self::USER_ID);
        $_SESSION['oLP'] = $lp;
        $this->assertSame('', $lp->get_mediaplayer($itemId, 'true'));
        unset($_SESSION['oLP']);
    }

    public function testGetMediaplayerIsEmptyWithoutAudio(): void
    {
        $courseInfo = api_get_course_info_by_id(self::COURSE_ID);
        $em = $this->getEntityManager();
        $lp = $this->newLp('fj_mediaplayer_none');
        $lpId = $lp->get_id();
        $root = Database::getManager()->getConnection()->fetchOne("SELECT iid FROM c_lp_item WHERE lp_id = ? AND path = 'root'", [$lpId]);
        $itemId = (int) $lp->add_item($em->find(CLpItem::class, $root), 0, 'document', 0, 'fj item');
        $em->clear();
        $lp = new learnpath($em->find(CLp::class, $lpId), $courseInfo, self::USER_ID);

        $this->assertSame('', $lp->get_mediaplayer($itemId, 'true'));
    }
}
