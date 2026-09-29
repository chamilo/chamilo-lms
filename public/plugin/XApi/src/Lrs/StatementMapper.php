<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Lrs;

use Chamilo\CoreBundle\Entity\XApiContext;
use Chamilo\CoreBundle\Entity\XApiExtensions;
use Chamilo\CoreBundle\Entity\XApiObject;
use Chamilo\CoreBundle\Entity\XApiResult;
use Chamilo\CoreBundle\Entity\XApiStatement;
use Chamilo\CoreBundle\Entity\XApiVerb;
use DateTimeImmutable;
use DateTimeInterface;
use RuntimeException;

/**
 * Converts xAPI 1.0.3 statements between their JSON representation and the
 * XApi* Doctrine entities.
 *
 * The entity model is polymorphic: XApiObject represents Agents, Groups,
 * Activities, StatementRefs and SubStatements, discriminated by its `type`.
 */
final class StatementMapper
{
    public const VOIDED_VERB_ID = 'http://adlnet.gov/expapi/verbs/voided';

    /**
     * Builds the entity graph for a decoded statement.
     *
     * @param array<string, mixed> $data decoded statement JSON
     */
    public function toEntity(array $data, string $id): XApiStatement
    {
        if (empty($data['actor']) || !\is_array($data['actor'])) {
            throw new RuntimeException('A statement requires an "actor" property.');
        }

        if (empty($data['verb']) || !\is_array($data['verb'])) {
            throw new RuntimeException('A statement requires a "verb" property.');
        }

        if (empty($data['object']) || !\is_array($data['object'])) {
            throw new RuntimeException('A statement requires an "object" property.');
        }

        $statement = new XApiStatement();
        $statement
            ->setId($id)
            ->setCreated($this->toTimestamp($data['timestamp'] ?? null))
            ->setStored(time())
            ->setHasAttachments(!empty($data['attachments']))
            ->setActor($this->objectToEntity($data['actor']))
            ->setVerb($this->verbToEntity($data['verb']))
            ->setObject($this->objectToEntity($data['object']))
        ;

        if (!empty($data['result']) && \is_array($data['result'])) {
            $statement->setResult($this->resultToEntity($data['result']));
        }

        if (!empty($data['context']) && \is_array($data['context'])) {
            $statement->setContext($this->contextToEntity($data['context']));
        }

        if (!empty($data['authority']) && \is_array($data['authority'])) {
            $statement->setAuthority($this->objectToEntity($data['authority']));
        }

        return $statement;
    }

    /**
     * Rebuilds the JSON representation of a stored statement.
     *
     * @return array<string, mixed>
     */
    public function toArray(XApiStatement $statement): array
    {
        $data = [
            'id' => $statement->getId(),
            'actor' => $statement->getActor() ? $this->objectToArray($statement->getActor()) : null,
            'verb' => $statement->getVerb() ? $this->verbToArray($statement->getVerb()) : null,
            'object' => $statement->getObject() ? $this->objectToArray($statement->getObject()) : null,
        ];

        if ($statement->getResult()) {
            $data['result'] = $this->resultToArray($statement->getResult());
        }

        if ($statement->getContext()) {
            $data['context'] = $this->contextToArray($statement->getContext());
        }

        if ($statement->getAuthority()) {
            $data['authority'] = $this->objectToArray($statement->getAuthority());
        }

        if (null !== $statement->getCreated()) {
            $data['timestamp'] = $this->toIsoDate($statement->getCreated());
        }

        if (null !== $statement->getStored()) {
            $data['stored'] = $this->toIsoDate($statement->getStored());
        }

        $data['version'] = '1.0.3';

        return array_filter($data, static fn ($value): bool => null !== $value);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function objectToEntity(array $data): XApiObject
    {
        $object = new XApiObject();
        $type = $this->resolveObjectType($data);

        $object->setType($type);

        switch ($type) {
            case 'Group':
                $this->fillAgent($object, $data);

                foreach ($data['member'] ?? [] as $member) {
                    if (\is_array($member)) {
                        $memberObject = $this->objectToEntity($member + ['objectType' => 'Agent']);
                        $object->addMember($memberObject);
                    }
                }

                break;
            case 'Agent':
                $this->fillAgent($object, $data);

                break;
            case 'StatementRef':
                $object->setReferencedStatementId(isset($data['id']) ? (string) $data['id'] : null);

                break;
            case 'SubStatement':
                if (!empty($data['actor']) && \is_array($data['actor'])) {
                    $object->setActor($this->objectToEntity($data['actor']));
                }

                if (!empty($data['verb']) && \is_array($data['verb'])) {
                    $object->setVerb($this->verbToEntity($data['verb']));
                }

                if (!empty($data['object']) && \is_array($data['object'])) {
                    $object->setObject($this->objectToEntity($data['object']));
                }

                break;
            default:
                $this->fillActivity($object, $data);

                break;
        }

        return $object;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function fillAgent(XApiObject $object, array $data): void
    {
        $object
            ->setName(isset($data['name']) ? (string) $data['name'] : null)
            ->setMbox(isset($data['mbox']) ? (string) $data['mbox'] : null)
            ->setMboxSha1Sum(isset($data['mbox_sha1sum']) ? (string) $data['mbox_sha1sum'] : null)
            ->setOpenId(isset($data['openid']) ? (string) $data['openid'] : null)
        ;

        if (!empty($data['account']) && \is_array($data['account'])) {
            $object
                ->setAccountName(isset($data['account']['name']) ? (string) $data['account']['name'] : null)
                ->setAccountHomePage(isset($data['account']['homePage']) ? (string) $data['account']['homePage'] : null)
            ;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function fillActivity(XApiObject $object, array $data): void
    {
        $object->setActivityId(isset($data['id']) ? (string) $data['id'] : null);

        $definition = $data['definition'] ?? null;

        if (!\is_array($definition)) {
            $object->setHasActivityDefinition(false);

            return;
        }

        $object
            ->setHasActivityDefinition(true)
            ->setHasActivityName(isset($definition['name']))
            ->setActivityName(isset($definition['name']) && \is_array($definition['name']) ? $definition['name'] : null)
            ->setHasActivityDescription(isset($definition['description']))
            ->setActivityDescription(
                isset($definition['description']) && \is_array($definition['description'])
                    ? $definition['description']
                    : null
            )
            ->setActivityType(isset($definition['type']) ? (string) $definition['type'] : null)
            ->setActivityMoreInfo(isset($definition['moreInfo']) ? (string) $definition['moreInfo'] : null)
        ;

        if (!empty($definition['extensions']) && \is_array($definition['extensions'])) {
            $object->setActivityExtensions($this->extensionsToEntity($definition['extensions']));
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function verbToEntity(array $data): XApiVerb
    {
        $verb = new XApiVerb();
        $verb->setId(isset($data['id']) ? (string) $data['id'] : '');

        if (isset($data['display']) && \is_array($data['display'])) {
            $verb->setDisplay($data['display']);
        }

        return $verb;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resultToEntity(array $data): XApiResult
    {
        $result = new XApiResult();
        $score = $data['score'] ?? null;

        $result->setHasScore(\is_array($score));

        if (\is_array($score)) {
            $result
                ->setScaled(isset($score['scaled']) ? (float) $score['scaled'] : null)
                ->setRaw(isset($score['raw']) ? (float) $score['raw'] : null)
                ->setMin(isset($score['min']) ? (float) $score['min'] : null)
                ->setMax(isset($score['max']) ? (float) $score['max'] : null)
            ;
        }

        $result
            ->setSuccess(isset($data['success']) ? (bool) $data['success'] : null)
            ->setCompletion(isset($data['completion']) ? (bool) $data['completion'] : null)
            ->setResponse(isset($data['response']) ? (string) $data['response'] : null)
            ->setDuration(isset($data['duration']) ? (string) $data['duration'] : null)
        ;

        if (!empty($data['extensions']) && \is_array($data['extensions'])) {
            $result->setExtensions($this->extensionsToEntity($data['extensions']));
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function contextToEntity(array $data): XApiContext
    {
        $context = new XApiContext();
        $context
            ->setRegistration(isset($data['registration']) ? (string) $data['registration'] : null)
            ->setRevision(isset($data['revision']) ? (string) $data['revision'] : null)
            ->setPlatform(isset($data['platform']) ? (string) $data['platform'] : null)
            ->setLanguage(isset($data['language']) ? (string) $data['language'] : null)
        ;

        if (!empty($data['instructor']) && \is_array($data['instructor'])) {
            $context->setInstructor($this->objectToEntity($data['instructor']));
        }

        if (!empty($data['team']) && \is_array($data['team'])) {
            $context->setTeam($this->objectToEntity($data['team'] + ['objectType' => 'Group']));
        }

        if (!empty($data['statement']['id'])) {
            $context->setStatement((string) $data['statement']['id']);
        }

        if (!empty($data['extensions']) && \is_array($data['extensions'])) {
            $context->setExtensions($this->extensionsToEntity($data['extensions']));
        }

        $contextActivities = $data['contextActivities'] ?? null;
        $context->setHasContextActivities(\is_array($contextActivities) && !empty($contextActivities));

        if (\is_array($contextActivities)) {
            $adders = [
                'parent' => 'addParentActivity',
                'grouping' => 'addGroupingActivity',
                'category' => 'addCategoryActivity',
                'other' => 'addOtherActivity',
            ];

            foreach ($adders as $key => $adder) {
                foreach ($this->normalizeActivityList($contextActivities[$key] ?? null) as $activity) {
                    $context->{$adder}($this->objectToEntity($activity + ['objectType' => 'Activity']));
                }
            }
        }

        return $context;
    }

    /**
     * The specification allows both a single activity object and a list of them.
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeActivityList(mixed $value): array
    {
        if (!\is_array($value) || empty($value)) {
            return [];
        }

        if (isset($value['id']) || isset($value['definition'])) {
            return [$value];
        }

        return array_values(array_filter($value, '\is_array'));
    }

    /**
     * @param array<string, mixed> $extensions
     */
    private function extensionsToEntity(array $extensions): XApiExtensions
    {
        $entity = new XApiExtensions();
        $entity->setExtensions($extensions);

        return $entity;
    }

    /**
     * @return array<string, mixed>
     */
    private function objectToArray(XApiObject $object): array
    {
        $type = $object->getType();

        if ('SubStatement' === $type) {
            $data = ['objectType' => 'SubStatement'];

            if ($object->getActor()) {
                $data['actor'] = $this->objectToArray($object->getActor());
            }

            if ($object->getVerb()) {
                $data['verb'] = $this->verbToArray($object->getVerb());
            }

            if ($object->getObject()) {
                $data['object'] = $this->objectToArray($object->getObject());
            }

            return $data;
        }

        if ('StatementRef' === $type) {
            return [
                'objectType' => 'StatementRef',
                'id' => $object->getReferencedStatementId(),
            ];
        }

        if (\in_array($type, ['Agent', 'Group'], true)) {
            return $this->agentToArray($object);
        }

        return $this->activityToArray($object);
    }

    /**
     * @return array<string, mixed>
     */
    private function agentToArray(XApiObject $object): array
    {
        $data = [
            'objectType' => $object->getType(),
            'name' => $object->getName(),
            'mbox' => $object->getMbox(),
            'mbox_sha1sum' => $object->getMboxSha1Sum(),
            'openid' => $object->getOpenId(),
        ];

        if (null !== $object->getAccountName() || null !== $object->getAccountHomePage()) {
            $data['account'] = array_filter(
                [
                    'name' => $object->getAccountName(),
                    'homePage' => $object->getAccountHomePage(),
                ],
                static fn ($value): bool => null !== $value
            );
        }

        if ('Group' === $object->getType() && !$object->getMembers()->isEmpty()) {
            $data['member'] = array_map(
                fn (XApiObject $member): array => $this->agentToArray($member),
                $object->getMembers()->toArray()
            );
        }

        return array_filter($data, static fn ($value): bool => null !== $value);
    }

    /**
     * @return array<string, mixed>
     */
    private function activityToArray(XApiObject $object): array
    {
        $data = [
            'objectType' => 'Activity',
            'id' => $object->getActivityId(),
        ];

        if (!$object->hasActivityDefinition()) {
            return array_filter($data, static fn ($value): bool => null !== $value);
        }

        $definition = [
            'name' => $object->getActivityName(),
            'description' => $object->getActivityDescription(),
            'type' => $object->getActivityType(),
            'moreInfo' => $object->getActivityMoreInfo(),
        ];

        if ($object->getActivityExtensions()) {
            $definition['extensions'] = $object->getActivityExtensions()->getExtensions();
        }

        $definition = array_filter($definition, static fn ($value): bool => null !== $value);

        if (!empty($definition)) {
            $data['definition'] = $definition;
        }

        return array_filter($data, static fn ($value): bool => null !== $value);
    }

    /**
     * @return array<string, mixed>
     */
    private function verbToArray(XApiVerb $verb): array
    {
        $data = [
            'id' => $verb->getId(),
            'display' => $verb->getDisplay(),
        ];

        return array_filter($data, static fn ($value): bool => null !== $value);
    }

    /**
     * @return array<string, mixed>
     */
    private function resultToArray(XApiResult $result): array
    {
        $data = [
            'success' => $result->isSuccess(),
            'completion' => $result->isCompletion(),
            'response' => $result->getResponse(),
            'duration' => $result->getDuration(),
        ];

        if ($result->hasScore()) {
            $score = array_filter(
                [
                    'scaled' => $result->getScaled(),
                    'raw' => $result->getRaw(),
                    'min' => $result->getMin(),
                    'max' => $result->getMax(),
                ],
                static fn ($value): bool => null !== $value
            );

            if (!empty($score)) {
                $data['score'] = $score;
            }
        }

        if ($result->getExtensions()) {
            $data['extensions'] = $result->getExtensions()->getExtensions();
        }

        return array_filter($data, static fn ($value): bool => null !== $value);
    }

    /**
     * @return array<string, mixed>
     */
    private function contextToArray(XApiContext $context): array
    {
        $data = [
            'registration' => $context->getRegistration(),
            'revision' => $context->getRevision(),
            'platform' => $context->getPlatform(),
            'language' => $context->getLanguage(),
        ];

        if ($context->getInstructor()) {
            $data['instructor'] = $this->objectToArray($context->getInstructor());
        }

        if ($context->getTeam()) {
            $data['team'] = $this->objectToArray($context->getTeam());
        }

        if (null !== $context->getStatement()) {
            $data['statement'] = [
                'objectType' => 'StatementRef',
                'id' => $context->getStatement(),
            ];
        }

        if ($context->getExtensions()) {
            $data['extensions'] = $context->getExtensions()->getExtensions();
        }

        $contextActivities = array_filter(
            [
                'parent' => $this->activityListToArray($context->getParentActivities()->toArray()),
                'grouping' => $this->activityListToArray($context->getGroupingActivities()->toArray()),
                'category' => $this->activityListToArray($context->getCategoryActivities()->toArray()),
                'other' => $this->activityListToArray($context->getOtherActivities()->toArray()),
            ],
            static fn (array $value): bool => !empty($value)
        );

        if (!empty($contextActivities)) {
            $data['contextActivities'] = $contextActivities;
        }

        return array_filter($data, static fn ($value): bool => null !== $value);
    }

    /**
     * @param array<int, XApiObject> $activities
     *
     * @return array<int, array<string, mixed>>
     */
    private function activityListToArray(array $activities): array
    {
        return array_map(
            fn (XApiObject $activity): array => $this->activityToArray($activity),
            array_values($activities)
        );
    }

    /**
     * An Agent may be identified by any of the four inverse functional
     * identifiers, or be an Activity, so the type cannot always be inferred
     * from the presence of a single property.
     *
     * @param array<string, mixed> $data
     */
    private function resolveObjectType(array $data): string
    {
        $declared = $data['objectType'] ?? null;

        if (\is_string($declared) && '' !== $declared) {
            return $declared;
        }

        if (isset($data['member'])) {
            return 'Group';
        }

        if (isset($data['mbox'], $data['mbox_sha1sum'], $data['openid'], $data['account'])
            || isset($data['mbox'])
            || isset($data['mbox_sha1sum'])
            || isset($data['openid'])
            || isset($data['account'])
        ) {
            return 'Agent';
        }

        return 'Activity';
    }

    private function toTimestamp(mixed $value): ?int
    {
        if (!\is_string($value) || '' === trim($value)) {
            return null;
        }

        $date = date_create_immutable($value);

        return false === $date ? null : $date->getTimestamp();
    }

    private function toIsoDate(int $timestamp): string
    {
        return (new DateTimeImmutable('@'.$timestamp))->format(DateTimeInterface::ATOM);
    }
}
