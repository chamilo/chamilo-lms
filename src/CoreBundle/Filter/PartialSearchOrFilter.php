<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\AccessUrlScopeHelper;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class PartialSearchOrFilter extends AbstractFilter
{
    /**
     * Prevent applying the portal scope multiple times on the same QueryBuilder instance.
     * (No unused parameters, no side-effects on the DQL parameter list.).
     *
     * @var array<int, bool>
     */
    private static array $scopeApplied = [];

    public function __construct(
        ManagerRegistry $managerRegistry,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly AccessUrlScopeHelper $accessUrlScope,
        private readonly Security $security,
        ?LoggerInterface $logger = null,
        ?array $properties = null
    ) {
        // Ensure "search" is an enabled property and normalize list/associative arrays.
        $properties = $this->normalizeProperties($properties);

        parent::__construct($managerRegistry, $logger, $properties);
    }

    public function apply(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        // Always scope users by current portal in a multi-URL install.
        $this->applyAccessUrlScopeOnce($queryBuilder, $queryNameGenerator, $resourceClass);

        // Keep default behavior: only apply filterProperty() when query params exist.
        parent::apply($queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);
    }

    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $this->applyAccessUrlScopeOnce($queryBuilder, $queryNameGenerator, $resourceClass);

        if ('search' !== $property) {
            return;
        }

        if (!\is_string($value) || '' === trim($value)) {
            throw new InvalidArgumentException('The "search" filter must not be empty.');
        }

        $alias = $queryBuilder->getRootAliases()[0];

        $paramName = $queryNameGenerator->generateParameterName('search');
        $queryBuilder->setParameter($paramName, '%'.trim($value).'%');

        $ors = [];
        foreach ($this->getSearchableFields() as $field) {
            if (str_contains($field, '.')) {
                [$relation, $subField] = explode('.', $field, 2);
                $joinAlias = $relation.'_alias';

                if (!\in_array($joinAlias, $queryBuilder->getAllAliases(), true)) {
                    $queryBuilder->leftJoin("$alias.$relation", $joinAlias);
                }

                $ors[] = $queryBuilder->expr()->like("$joinAlias.$subField", ':'.$paramName);
            } else {
                $ors[] = $queryBuilder->expr()->like("$alias.$field", ':'.$paramName);
            }
        }

        if (!empty($ors)) {
            $queryBuilder->andWhere($queryBuilder->expr()->orX(...$ors));
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'search' => [
                'type' => 'string',
                'required' => false,
                'description' => 'Search OR/LIKE across configured fields and scopes results to the current portal when multi-URL is enabled.',
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function normalizeProperties(?array $properties): ?array
    {
        if (null === $properties) {
            return null;
        }

        // If a numeric list was provided (['username','firstname',...]),
        // convert it into an associative map: ['username' => null, ...]
        if (\function_exists('array_is_list') && array_is_list($properties)) {
            $normalized = [];
            foreach ($properties as $field) {
                if (\is_string($field) && '' !== trim($field)) {
                    $normalized[trim($field)] = null;
                }
            }
            $properties = $normalized;
        }

        // Ensure "search" is enabled, otherwise ApiPlatform may ignore ?search=...
        if (!\array_key_exists('search', $properties)) {
            $properties['search'] = null;
        }

        return $properties;
    }

    /**
     * @return string[]
     */
    private function getSearchableFields(): array
    {
        // Default fallback if no properties were configured.
        if (null === $this->properties) {
            return ['username', 'firstname', 'lastname'];
        }

        $fields = array_keys($this->properties);

        // "search" is not a field, it is the filter parameter.
        $fields = array_values(array_filter(
            $fields,
            static fn (string $f): bool => 'search' !== $f
        ));

        return !empty($fields) ? $fields : ['username', 'firstname', 'lastname'];
    }

    private function applyAccessUrlScopeOnce(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass
    ): void {
        if (User::class !== $resourceClass) {
            return;
        }

        if (!$this->accessUrlHelper->isMultiple()) {
            return;
        }

        // ROLE_SUPER_ADMIN no longer exists in the role hierarchy (config/packages/security.yaml);
        // kept as a defensive check in case something still grants it directly.
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return;
        }

        $currentUser = $this->security->getUser();

        $urlIds = null;
        if ($this->security->isGranted('ROLE_GLOBAL_ADMIN') && $currentUser instanceof User) {
            // A global admin registered in the topmost URL of a tree ("unrestricted") manages
            // every URL and must see every user regardless of portal, unchanged. One registered
            // only in a non-root URL is scoped to that URL's subtree specifically -- not to
            // whichever URL the current request happens to be on, since (unlike a plain user,
            // whose own portal IS the URL they're browsing) an admin's authority is a property of
            // their own registration, not of the request. See AccessUrlScopeHelper.
            $managedUrlIds = $this->accessUrlScope->getManagedUrlIds($currentUser);
            if (null === $managedUrlIds) {
                return;
            }

            $urlIds = $managedUrlIds;
        }

        $qbId = spl_object_id($queryBuilder);
        if (isset(self::$scopeApplied[$qbId])) {
            return;
        }
        self::$scopeApplied[$qbId] = true;

        if (null === $urlIds) {
            $currentUrl = $this->accessUrlHelper->getCurrent();
            if (null === $currentUrl || null === $currentUrl->getId()) {
                return;
            }

            $urlIds = [$currentUrl->getId()];
        }

        $alias = $queryBuilder->getRootAliases()[0];

        // Join User -> portals (AccessUrlRelUser) -> url (AccessUrl)
        $portalsAlias = $queryNameGenerator->generateJoinAlias('portals');
        $urlAlias = $queryNameGenerator->generateJoinAlias('accessUrl');

        $queryBuilder->innerJoin("$alias.portals", $portalsAlias);
        $queryBuilder->innerJoin("$portalsAlias.url", $urlAlias);

        // Compare by ID to avoid edge cases with entity object comparison.
        $paramName = $queryNameGenerator->generateParameterName('currentAccessUrlIds');

        $queryBuilder
            ->andWhere("$urlAlias.id IN (:$paramName)")
            ->setParameter($paramName, $urlIds)
            ->distinct()
        ;
    }
}
