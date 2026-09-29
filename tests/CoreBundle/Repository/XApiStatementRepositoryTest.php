<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Repository\XApiStatementRepository;
use Chamilo\PluginBundle\XApi\Lrs\StatementMapper;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * The LRS must behave as a store, not as a per-request cache: a statement
 * written by one client has to be readable by any other one — the reporting
 * pages fetch statements over HTTP, without the writer's PHP session.
 */
class XApiStatementRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private const REGISTRATION = '11111111-1111-1111-1111-111111111111';

    public function testStatementSurvivesTheRequestThatWroteIt(): void
    {
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(XApiStatementRepository::class);
        $mapper = new StatementMapper();

        $em->persist($mapper->toEntity($this->statementData(), 'aaaaaaaa-0000-0000-0000-000000000001'));
        $em->flush();

        // A different consumer only has the identifier, not the writer's state.
        $em->clear();

        $found = $repo->findActiveById('aaaaaaaa-0000-0000-0000-000000000001');

        $this->assertNotNull($found, 'A persisted statement must be readable outside the writing request.');

        $data = $mapper->toArray($found);

        $this->assertSame('mailto:test@example.com', $data['actor']['mbox']);
        $this->assertSame('http://adlnet.gov/expapi/verbs/completed', $data['verb']['id']);
        $this->assertSame('http://example.com/activity/1', $data['object']['id']);
        $this->assertSame(self::REGISTRATION, $data['context']['registration']);
        $this->assertSame(0.9, $data['result']['score']['scaled']);
    }

    public function testRegistrationFilterOnlyReturnsItsOwnStatements(): void
    {
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(XApiStatementRepository::class);
        $mapper = new StatementMapper();

        $em->persist($mapper->toEntity($this->statementData(), 'aaaaaaaa-0000-0000-0000-000000000002'));

        $other = $this->statementData();
        $other['context']['registration'] = '99999999-9999-9999-9999-999999999999';
        $em->persist($mapper->toEntity($other, 'aaaaaaaa-0000-0000-0000-000000000003'));
        $em->flush();

        $statements = $repo->findByFilters(['registration' => self::REGISTRATION]);

        $this->assertCount(1, $statements);
        $this->assertSame('aaaaaaaa-0000-0000-0000-000000000002', $statements[0]->getId());
    }

    public function testVoidedStatementLeavesTheRegularResults(): void
    {
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(XApiStatementRepository::class);
        $mapper = new StatementMapper();

        $target = 'aaaaaaaa-0000-0000-0000-000000000004';
        $em->persist($mapper->toEntity($this->statementData(), $target));
        $em->flush();

        $this->assertCount(1, $repo->findByFilters(['registration' => self::REGISTRATION]));

        $em->persist(
            $mapper->toEntity(
                [
                    'actor' => ['mbox' => 'mailto:admin@example.com'],
                    'verb' => ['id' => XApiStatementRepository::VOIDED_VERB_ID],
                    'object' => ['objectType' => 'StatementRef', 'id' => $target],
                ],
                'aaaaaaaa-0000-0000-0000-000000000005'
            )
        );
        $em->flush();

        $this->assertCount(
            0,
            $repo->findByFilters(['registration' => self::REGISTRATION]),
            'A voided statement must no longer show up in the regular query.'
        );
        $this->assertNull($repo->findActiveById($target));
        $this->assertNotNull(
            $repo->findVoidedById($target),
            'A voided statement is still retrievable through voidedStatementId.'
        );
    }

    public function testLimitAndCursorPageThroughTheResults(): void
    {
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(XApiStatementRepository::class);
        $mapper = new StatementMapper();

        for ($i = 1; $i <= 5; $i++) {
            $em->persist(
                $mapper->toEntity($this->statementData(), \sprintf('bbbbbbbb-0000-0000-0000-00000000000%d', $i))
            );
        }
        $em->flush();

        $filters = ['registration' => self::REGISTRATION, 'limit' => 2];

        $this->assertCount(2, $repo->findByFilters($filters));
        $this->assertCount(2, $repo->findByFilters($filters + ['cursor' => 2]));
        $this->assertCount(1, $repo->findByFilters($filters + ['cursor' => 4]));
        $this->assertSame(5, $repo->countByFilters($filters));
    }

    public function testAgentFilterWithoutIdentifierMatchesNothing(): void
    {
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(XApiStatementRepository::class);
        $mapper = new StatementMapper();

        $em->persist($mapper->toEntity($this->statementData(), 'cccccccc-0000-0000-0000-000000000001'));
        $em->flush();

        // "name" is not an inverse functional identifier, so it cannot be used
        // to claim an identity: the filter must not fall back to matching all.
        $this->assertCount(0, $repo->findByFilters(['agent' => ['name' => 'Tester']]));
        $this->assertCount(1, $repo->findByFilters(['agent' => ['mbox' => 'mailto:test@example.com']]));
    }

    /**
     * @return array<string, mixed>
     */
    private function statementData(): array
    {
        return [
            'actor' => [
                'objectType' => 'Agent',
                'name' => 'Tester',
                'mbox' => 'mailto:test@example.com',
            ],
            'verb' => [
                'id' => 'http://adlnet.gov/expapi/verbs/completed',
                'display' => ['en-US' => 'completed'],
            ],
            'object' => [
                'objectType' => 'Activity',
                'id' => 'http://example.com/activity/1',
                'definition' => [
                    'name' => ['en-US' => 'Demo activity'],
                    'type' => 'http://adlnet.gov/expapi/activities/lesson',
                ],
            ],
            'result' => [
                'score' => ['scaled' => 0.9, 'raw' => 9, 'min' => 0, 'max' => 10],
                'success' => true,
                'completion' => true,
                'duration' => 'PT5M',
            ],
            'context' => [
                'registration' => self::REGISTRATION,
                'platform' => 'Chamilo',
            ],
        ];
    }
}
