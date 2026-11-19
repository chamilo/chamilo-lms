<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use Doctrine\DBAL\Connection;
use FormValidator;
use ReflectionProperty;
use SessionManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Characterization tests (tier 3b) for SessionManager functions the earlier files did not reach:
 * getSessionsForCertificateReport(), the access URL check of canManageSessionId() and the coach
 * list of setForm(). Fixtures are raw prepared inserts, tagged "sm3b_".
 *
 * Not pinned here (their queries stay raw, see the TODO(db-neutral) comments in the library):
 * get_session_lp_progress, get_survey_overview, get_session_progress,
 * get_user_data_access_tracking_overview, getAllUsersFromCoursesFromAllSessionFromStatus,
 * getSessionsForAdmin (free-form jqGrid where/order/limit fragments, UNION), importCSV, importXML.
 */
final class SessionManagerTier3bCharacterizationTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $httpSession = new HttpSession(new MockArraySessionStorage());
        $httpSession->set('_user', ['id' => $this->getAdmin()->getId()]);
        $httpSession->set('access_url_id', $this->getAccessUrl()->getId());
        $request = new Request();
        $request->setSession($httpSession);
        Container::setSession($httpSession);
        self::getContainer()->get('request_stack')->push($request);
        Container::setContainer(self::getContainer());
        $this->loginAs($this->getAdmin()->getId());
        Database::setManager($this->getEntityManager());
        Database::setConnection($this->getEntityManager()->getConnection());
    }

    private function conn(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    private function loginAs(int $userId): void
    {
        $user = $this->getEntityManager()->getRepository(User::class)->find($userId);
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken($user, 'main', $user->getRoles())
        );
        Container::getSession()->set('_user', ['id' => $userId]);
        $this->getEntityManager()->clear();
    }

    private function rawUser(string $suffix, string $role = 'ROLE_STUDENT', int $status = 5, int $active = 1): int
    {
        $name = 'sm3b_'.$suffix.'_'.getmypid().'_'.(++$this->seq);
        $this->conn()->executeStatement(
            'INSERT INTO user (username, username_canonical, email, email_canonical, firstname, lastname, locale,
                password, timezone, locked, expired, credentials_expired, salt, roles, status, active, uuid,
                mfa_enabled, created_at, updated_at, official_code)
             VALUES (?, ?, ?, ?, ?, ?, \'en_US\', \'x\', \'UTC\', 0, 0, 0, \'x\', ?, ?, ?,
                UNHEX(REPLACE(UUID(), \'-\', \'\')), 0, NOW(), NOW(), \'\')',
            [$name, $name, $name.'@example.com', $name.'@example.com', 'First'.$suffix, 'Last'.$suffix, json_encode([$role]), $status, $active]
        );
        $id = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement('INSERT INTO access_url_rel_user (access_url_id, user_id) VALUES (1, ?)', [$id]);

        return $id;
    }

    private function rawSession(string $title, int $urlId = 1): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status, position, description)
             VALUES (?, 0, 0, 0, 1, 0, 0, \'\')',
            ['sm3b_'.$title]
        );
        $id = (int) $this->conn()->lastInsertId();
        if ($urlId > 0) {
            $this->conn()->executeStatement('INSERT INTO access_url_rel_session (session_id, access_url_id) VALUES (?, ?)', [$id, $urlId]);
        }

        return $id;
    }

    private function multiUrl(): int
    {
        $this->conn()->executeStatement('UPDATE access_url SET lft = 1, rgt = 4, tree_root = 1 WHERE id = 1');
        $this->conn()->executeStatement(
            'INSERT INTO access_url (parent_id, tree_root, lft, lvl, rgt, url, description, active, created_by)
             VALUES (1, 1, 2, 1, 3, \'http://sm3b-b.example.com/\', \'sm3b_b\', 1, 1)'
        );
        $this->getEntityManager()->clear();

        return (int) $this->conn()->lastInsertId();
    }

    /**
     * @return array<int, string> the report sessions created by this test only
     */
    private function ourReport(): array
    {
        return array_filter(
            SessionManager::getSessionsForCertificateReport(),
            static fn ($title) => str_starts_with((string) $title, 'sm3b_')
        );
    }

    public function testCertificateReportListsSessionsByTitleIndexedByIntegerId(): void
    {
        $z = $this->rawSession('cr_z');
        $a = $this->rawSession('cr_a');
        $noUrl = $this->rawSession('cr_nourl', 0);

        $report = $this->ourReport();

        // Ordered by title, keys are int session ids, values the titles. On a single-URL install a session
        // without an access_url_rel_session row is listed too.
        $this->assertSame([$a, $noUrl, $z], array_keys($report));
        $this->assertSame(['sm3b_cr_a', 'sm3b_cr_nourl', 'sm3b_cr_z'], array_values($report));
        foreach (array_keys($report) as $id) {
            $this->assertIsInt($id);
        }
    }

    public function testCertificateReportIsLimitedToTheCurrentAccessUrlOnMultiUrl(): void
    {
        $urlB = $this->multiUrl();
        $inA = $this->rawSession('cr_in_a');
        $this->rawSession('cr_in_b', $urlB);
        $this->rawSession('cr_none', 0);
        // Linked to both URLs: must be listed once (DISTINCT does not matter here, the join is per URL).
        $both = $this->rawSession('cr_both');
        $this->conn()->executeStatement('INSERT INTO access_url_rel_session (session_id, access_url_id) VALUES (?, ?)', [$both, $urlB]);

        $this->assertSame([$both, $inA], array_keys($this->ourReport()));
    }

    public function testCertificateReportOfAnHrManagerIsLimitedToTheSessionsAssignedToThem(): void
    {
        $hr = $this->rawUser('hr', 'ROLE_HR', DRH);
        $mine = $this->rawSession('cr_hr_mine');
        $this->rawSession('cr_hr_other');
        // Any session_rel_user row counts, and two rows must not duplicate the session.
        foreach ([Session::DRH, Session::STUDENT] as $relation) {
            $this->conn()->executeStatement(
                'INSERT INTO session_rel_user (session_id, user_id, relation_type, duration, registered_at) VALUES (?, ?, ?, 0, NOW())',
                [$mine, $hr, $relation]
            );
        }
        $this->loginAs($hr);

        $this->assertTrue(api_is_hrm());
        $this->assertSame([$mine], array_keys($this->ourReport()));
    }

    public function testCanManageSessionIdRequiresTheCurrentAccessUrlOnMultiUrl(): void
    {
        $urlB = $this->multiUrl();
        $inA = $this->rawSession('cm_a');
        $inB = $this->rawSession('cm_b', $urlB);
        $none = $this->rawSession('cm_none', 0);

        // The root admin may edit every session, but an id taken from a request must belong to the URL in use.
        $this->assertTrue(SessionManager::canManageSessionId($inA));
        $this->assertFalse(SessionManager::canManageSessionId($inB));
        $this->assertFalse(SessionManager::canManageSessionId($none));
    }

    public function testCanManageSessionIdIgnoresTheUrlOnSingleUrlInstall(): void
    {
        $none = $this->rawSession('cm_single', 0);
        $this->assertTrue(SessionManager::canManageSessionId($none));
    }

    public function testSetFormOffersTheSoftDeletedLevelOneUsersAsCoachesWhenNoActiveOneExists(): void
    {
        // The "no teacher" fallback counts users with status 1 and active <> -2, but lists those with
        // active <> -1: so only a soft-deleted status-1 user is listed, and an inactive one is not.
        $this->conn()->executeStatement('UPDATE user SET status = 5 WHERE status = 1');
        // Created in reverse name order: the list is sorted by name (first/last name order per platform setting).
        $laterName = $this->rawUser('coach_del_b', 'ROLE_TEACHER', 1, -2);
        $deleted = $this->rawUser('coach_del_a', 'ROLE_TEACHER', 1, -2);
        $this->getEntityManager()->clear();

        $form = new FormValidator('sm3b_form');
        SessionManager::setForm($form);
        $options = $this->selectOptions($form, 'coach_username');

        $this->assertCount(2, $options, 'Both soft-deleted users are listed.');
        $this->assertSame([(string) $deleted, (string) $laterName], [(string) $options[0]['attr']['value'], (string) $options[1]['attr']['value']]);
        $this->assertMatchesRegularExpression('/\(sm3b_coach_del_a_\d+_\d+\)$/', $options[0]['text']);
    }

    public function testSetFormUsesAnAjaxCoachSelectWhenAnActiveLevelOneUserExists(): void
    {
        $this->conn()->executeStatement('UPDATE user SET status = 5 WHERE status = 1');
        $this->rawUser('coach_inactive', 'ROLE_TEACHER', 1, -1);
        $this->getEntityManager()->clear();

        $form = new FormValidator('sm3b_form_ajax');
        SessionManager::setForm($form);

        // Active -1 still counts as a user (only -2 is excluded), so the ajax branch is taken and no option is preloaded.
        $this->assertSame([], $this->selectOptions($form, 'coach_username'));
    }

    private function selectOptions(FormValidator $form, string $name): array
    {
        $element = $form->getElement($name);
        $property = new ReflectionProperty($element, '_options');
        $property->setAccessible(true);

        return $property->getValue($element);
    }
}
