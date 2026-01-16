<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\SettingsCurrent;
use Chamilo\CoreBundle\Helpers\SettingsManagerHelper;
use Chamilo\CoreBundle\Search\SearchEngineFieldSynchronizer;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use InvalidArgumentException;
use ReflectionMethod;
use ReflectionNamedType;
use Sylius\Bundle\SettingsBundle\Manager\SettingsManagerInterface;
use Sylius\Bundle\SettingsBundle\Model\Settings;
use Sylius\Bundle\SettingsBundle\Model\SettingsInterface;
use Sylius\Bundle\SettingsBundle\Registry\ServiceRegistryInterface;
use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Exception\ValidatorException;

use const ARRAY_FILTER_USE_KEY;
use const PHP_URL_HOST;

/**
 * Handles the platform settings.
 */
class SettingsManager implements SettingsManagerInterface
{
    protected ?AccessUrl $url = null;

    protected ServiceRegistryInterface $schemaRegistry;

    protected EntityManager $manager;

    protected EntityRepository $repository;

    protected EventDispatcherInterface $eventDispatcher;

    /**
     * Runtime cache for resolved parameters.
     *
     * @var Settings[]
     */
    protected array $resolvedSettings = [];

    /**
     * @var null|array<string, Settings>|mixed[]
     */
    protected ?array $schemaList;

    protected RequestStack $request;

    private ?AccessUrl $mainUrlCache = null;

    /**
     * Platform -> Course settings propagation map.
     * Add more categories/variables here to scale to other features.
     *
     * IMPORTANT:
     * - Values are stored as strings in legacy course settings ("true"/"false" is common).
     */
    private const COURSE_SETTINGS_PROPAGATION = [
        'ai_helpers' => [
            // Only propagate "feature flags" (not JSON providers, etc.)
            'learning_path_generator',
            'exercise_generator',
            'open_answers_grader',
            'tutor_chatbot',
            'task_grader',
            'content_analyser',
            'image_generator',
            'glossary_terms_generator',
            'video_generator',
            'course_analyser',
        ],
    ];

    public function __construct(
        ServiceRegistryInterface $schemaRegistry,
        EntityManager $manager,
        EntityRepository $repository,
        EventDispatcherInterface $eventDispatcher,
        RequestStack $request,
        protected readonly SettingsManagerHelper $settingsManagerHelper,
        private readonly SearchEngineFieldSynchronizer $searchEngineFieldSynchronizer,
    ) {
        $this->schemaRegistry = $schemaRegistry;
        $this->manager = $manager;
        $this->repository = $repository;
        $this->eventDispatcher = $eventDispatcher;
        $this->request = $request;
        $this->schemaList = [];
    }

    public function getUrl(): ?AccessUrl
    {
        return $this->url;
    }

    public function setUrl(AccessUrl $url): void
    {
        $this->url = $url;
    }

    public function updateSchemas(AccessUrl $url): void
    {
        $this->url = $url;
        $schemas = array_keys($this->getSchemas());
        foreach ($schemas as $schema) {
            $settings = $this->load($this->convertServiceToNameSpace($schema));
            $this->update($settings);
        }
    }

    public function installSchemas(AccessUrl $url): void
    {
        $this->url = $url;
        $schemas = array_keys($this->getSchemas());
        foreach ($schemas as $schema) {
            $settings = $this->load($this->convertServiceToNameSpace($schema));
            $this->save($settings);
        }
    }

    /**
     * @return array|AbstractSettingsSchema[]
     */
    public function getSchemas(): array
    {
        return $this->schemaRegistry->all();
    }

    public function convertNameSpaceToService(string $category): string
    {
        return 'chamilo_core.settings.'.$category;
    }

    public function convertServiceToNameSpace(string $category): string
    {
        return str_replace('chamilo_core.settings.', '', $category);
    }

    public function updateSetting(string $name, $value): void
    {
        $name = $this->validateSetting($name);

        [$category, $name] = explode('.', $name);
        $settings = $this->load($category);

        if (!$settings->has($name)) {
            $message = \sprintf("Parameter %s doesn't exists.", $name);

            throw new InvalidArgumentException($message);
        }

        $settings->set($name, $value);
        $this->update($settings);
    }

    /**
     * Get a specific configuration setting, getting from the previously stored
     * PHP session data whenever possible.
     *
     * @param string $name       The setting name (composed if in a category, i.e. 'platform.institution')
     * @param bool   $loadFromDb Whether to load from the database
     */
    public function getSetting(string $name, bool $loadFromDb = false): mixed
    {
        $name = $this->validateSetting($name);

        $overridden = $this->settingsManagerHelper->getOverride($name);

        if (null !== $overridden) {
            return $overridden;
        }

        [$category, $variable] = explode('.', $name);

        if ($loadFromDb) {
            $settings = $this->load($category, $variable);
            if ($settings->has($variable)) {
                return $settings->get($variable);
            }

            return null;
        }

        $this->ensureUrlResolved();

        // MultiURL: avoid stale session schema cache in sub-URLs.
        // Sessions are host-bound, so changes on the main URL cannot invalidate a sub-URL session cache.
        // Resolve effective settings from DB once per request (runtime cache) to apply lock/unlock immediately.
        if (null !== $this->url && !$this->isMainUrlContext()) {
            if (!isset($this->resolvedSettings[$category])) {
                $this->resolvedSettings[$category] = $this->load($category);
            }

            $settings = $this->resolvedSettings[$category];
            if ($settings->has($variable)) {
                return $settings->get($variable);
            }

            error_log("Attempted to access undefined setting '$variable' in category '$category'.");

            return null;
        }

        // Main URL (or legacy single-URL): keep the fast session cache behavior.
        $this->loadAll();

        if (!empty($this->schemaList) && isset($this->schemaList[$category])) {
            $settings = $this->schemaList[$category];
            if ($settings->has($variable)) {
                return $settings->get($variable);
            }

            return null;
        }

        throw new InvalidArgumentException(\sprintf('Category %s not found', $category));
    }

    public function loadAll(): void
    {
        $this->ensureUrlResolved();

        $session = null;

        if ($this->request->getCurrentRequest()) {
            $session = $this->request->getCurrentRequest()->getSession();

            $cacheKey = $this->getSessionSchemaCacheKey();
            $schemaList = $session->get($cacheKey);

            if (!empty($schemaList)) {
                $this->schemaList = $schemaList;

                return;
            }
        }

        $schemas = array_keys($this->getSchemas());
        $schemaList = [];
        $settingsBuilder = new SettingsBuilder();
        $all = $this->getAllParametersByCategory();

        foreach ($schemas as $schema) {
            $schemaRegister = $this->schemaRegistry->get($schema);
            $schemaRegister->buildSettings($settingsBuilder);
            $name = $this->convertServiceToNameSpace($schema);
            $settings = new Settings();

            /** @var array<string, mixed> $parameters */
            $parameters = $all[$name] ?? [];

            $knownParameters = array_filter(
                $parameters,
                fn ($key): bool => $settingsBuilder->isDefined($key),
                ARRAY_FILTER_USE_KEY
            );

            $transformers = $settingsBuilder->getTransformers();
            foreach ($transformers as $parameter => $transformer) {
                if (\array_key_exists($parameter, $knownParameters)) {
                    if ('course_creation_use_template' === $parameter) {
                        if (empty($knownParameters[$parameter])) {
                            $knownParameters[$parameter] = null;
                        }
                    } else {
                        $knownParameters[$parameter] = $transformer->reverseTransform($knownParameters[$parameter]);
                    }
                }
            }

            $knownParameters = $this->normalizeNullsBeforeResolve($knownParameters, $settingsBuilder);
            $parameters = $settingsBuilder->resolve($knownParameters);
            $settings->setParameters($parameters);
            $schemaList[$name] = $settings;
        }
        $this->schemaList = $schemaList;
        if ($session && $this->request->getCurrentRequest()) {
            $cacheKey = $this->getSessionSchemaCacheKey();
            $session->set($cacheKey, $schemaList);
        }
    }

    public function load(string $schemaAlias, ?string $namespace = null, bool $ignoreUnknown = true): SettingsInterface
    {
        $this->ensureUrlResolved();

        $settings = new Settings();
        $schemaAliasNoPrefix = $schemaAlias;
        $schemaAlias = 'chamilo_core.settings.'.$schemaAlias;
        if ($this->schemaRegistry->has($schemaAlias)) {
            /** @var SchemaInterface $schema */
            $schema = $this->schemaRegistry->get($schemaAlias);
        } else {
            return $settings;
        }

        $settings->setSchemaAlias($schemaAlias);

        // We need to get a plain parameters array since we use the options resolver on it
        $parameters = $this->getParameters($schemaAliasNoPrefix);
        $settingsBuilder = new SettingsBuilder();
        $schema->buildSettings($settingsBuilder);

        // Remove unknown settings' parameters (e.g. From a previous version of the settings schema)
        if (true === $ignoreUnknown) {
            foreach ($parameters as $name => $value) {
                if (!$settingsBuilder->isDefined($name)) {
                    unset($parameters[$name]);
                }
            }
        }

        foreach ($settingsBuilder->getTransformers() as $parameter => $transformer) {
            if (\array_key_exists($parameter, $parameters)) {
                $parameters[$parameter] = $transformer->reverseTransform($parameters[$parameter]);
            }
        }
        $parameters = $this->normalizeNullsBeforeResolve($parameters, $settingsBuilder);
        $parameters = $settingsBuilder->resolve($parameters);
        $settings->setParameters($parameters);

        return $settings;
    }

    public function update(SettingsInterface $settings): void
    {
        $this->ensureUrlResolved();

        $namespace = (string) $settings->getSchemaAlias();

        /** @var SchemaInterface $schema */
        $schema = $this->schemaRegistry->get($settings->getSchemaAlias());

        $settingsBuilder = new SettingsBuilder();
        $schema->buildSettings($settingsBuilder);
        $raw = $settings->getParameters();
        $raw = $this->normalizeNullsBeforeResolve($raw, $settingsBuilder);
        $parameters = $settingsBuilder->resolve($raw);

        // Transform values to scalar strings for persistence.
        foreach ($parameters as $parameter => $value) {
            $parameters[$parameter] = $this->transformToString($value);
        }

        $settings->setParameters($parameters);

        $simpleCategoryName = str_replace('chamilo_core.settings.', '', $namespace);
        $url = $this->getUrl();

        // Restrict lookup to current URL so we do not override other URLs.
        $criteria = [
            'category' => $simpleCategoryName,
        ];

        if (null !== $url) {
            $criteria['url'] = $url;
        }

        $persistedParameters = $this->repository->findBy($criteria);

        /** @var array<string, SettingsCurrent> $persistedParametersMap */
        $persistedParametersMap = [];
        foreach ($persistedParameters as $parameter) {
            if ($parameter instanceof SettingsCurrent) {
                $persistedParametersMap[$parameter->getVariable()] = $parameter;
            }
        }

        // Preload canonical metadata (main URL) once to avoid N+1 queries.
        $canonicalByVar = $this->getCanonicalSettingsMap($simpleCategoryName);

        foreach ($parameters as $name => $value) {
            $canonical = $canonicalByVar[$name] ?? null;

            // MultiURL: respect access_url_changeable defined on main URL.
            $isChangeable = $this->isSettingChangeableForCurrentUrl($simpleCategoryName, $name);

            if (isset($persistedParametersMap[$name])) {
                $row = $persistedParametersMap[$name];

                // Always keep metadata in sync (title/comment/type/etc).
                $row->setCategory($simpleCategoryName);
                $this->syncSettingMetadataFromCanonical($row, $canonical, $name);

                // Only write value if changeable (or if we are on main URL).
                if ($isChangeable || $this->isMainUrlContext()) {
                    $row->setSelectedValue((string) $value);
                }

                // Do NOT force setUrl() here unless you really must.
                // Setting the URL on an existing row can accidentally "move" it across URLs if a query is wrong.
                $this->manager->persist($row);

                continue;
            }

            // Do not create rows for non-changeable settings in sub-URLs.
            if (!$isChangeable && !$this->isMainUrlContext()) {
                continue;
            }

            $row = $this->createSettingForCurrentUrl($simpleCategoryName, $name, (string) $value, $canonical);
            $this->manager->persist($row);
        }

        $this->applySearchEngineFieldsSyncIfNeeded($simpleCategoryName, $parameters);

        $this->manager->flush();
        $this->clearSessionSchemaCache();
        $this->propagatePlatformSettingsToCoursesIfNeeded($simpleCategoryName, $parameters);
    }

    /**
     * @throws ValidatorException
     */
    public function save(SettingsInterface $settings): void
    {
        $this->ensureUrlResolved();

        $namespace = (string) $settings->getSchemaAlias();

        /** @var SchemaInterface $schema */
        $schema = $this->schemaRegistry->get($settings->getSchemaAlias());

        $settingsBuilder = new SettingsBuilder();
        $schema->buildSettings($settingsBuilder);
        $raw = $settings->getParameters();
        $raw = $this->normalizeNullsBeforeResolve($raw, $settingsBuilder);
        $parameters = $settingsBuilder->resolve($raw);

        // Transform values to scalar strings for persistence.
        foreach ($parameters as $parameter => $value) {
            $parameters[$parameter] = $this->transformToString($value);
        }
        $settings->setParameters($parameters);

        $simpleCategoryName = str_replace('chamilo_core.settings.', '', $namespace);
        $url = $this->getUrl();

        // Restrict lookup to current URL so we do not override other URLs.
        $criteria = [
            'category' => $simpleCategoryName,
        ];

        if (null !== $url) {
            $criteria['url'] = $url;
        }

        $persistedParameters = $this->repository->findBy($criteria);

        /** @var array<string, SettingsCurrent> $persistedParametersMap */
        $persistedParametersMap = [];
        foreach ($persistedParameters as $parameter) {
            if ($parameter instanceof SettingsCurrent) {
                $persistedParametersMap[$parameter->getVariable()] = $parameter;
            }
        }

        // Preload canonical metadata (main URL) once to avoid N+1 queries.
        $canonicalByVar = $this->getCanonicalSettingsMap($simpleCategoryName);

        foreach ($parameters as $name => $value) {
            $canonical = $canonicalByVar[$name] ?? null;

            // MultiURL: respect access_url_changeable defined on main URL.
            $isChangeable = $this->isSettingChangeableForCurrentUrl($simpleCategoryName, $name);

            if (isset($persistedParametersMap[$name])) {
                $row = $persistedParametersMap[$name];

                // Always keep metadata in sync (title/comment/type/etc).
                $row->setCategory($simpleCategoryName);
                $this->syncSettingMetadataFromCanonical($row, $canonical, $name);

                // Only write value if changeable (or if we are on main URL).
                if ($isChangeable || $this->isMainUrlContext()) {
                    $row->setSelectedValue((string) $value);
                }

                $this->manager->persist($row);

                continue;
            }

            // Do not create rows for non-changeable settings in sub-URLs.
            if (!$isChangeable && !$this->isMainUrlContext()) {
                continue;
            }

            $row = $this->createSettingForCurrentUrl($simpleCategoryName, $name, (string) $value, $canonical);
            $this->manager->persist($row);
        }

        $this->applySearchEngineFieldsSyncIfNeeded($simpleCategoryName, $parameters);

        $this->manager->flush();
        $this->clearSessionSchemaCache();
        $this->propagatePlatformSettingsToCoursesIfNeeded($simpleCategoryName, $parameters);
    }

    /**
     * Sync JSON-defined search fields into search_engine_field table.
     */
    private function applySearchEngineFieldsSyncIfNeeded(string $category, array $parameters): void
    {
        if ('search' !== $category) {
            return;
        }

        if (!\array_key_exists('search_prefilter_prefix', $parameters)) {
            return;
        }

        $json = (string) $parameters['search_prefilter_prefix'];

        // Non-destructive by default (no deletes)
        $this->searchEngineFieldSynchronizer->syncFromJson($json, true);
    }

    /**
     * @param string $keyword
     */
    public function getParametersFromKeywordOrderedByCategory($keyword): array
    {
        $this->ensureUrlResolved();

        $qb = $this->repository->createQueryBuilder('s')
            ->where('s.variable LIKE :keyword OR s.title LIKE :keyword')
            ->setParameter('keyword', "%{$keyword}%")
        ;

        // MultiURL: when on a sub-URL, include both current + main URL rows.
        if (null !== $this->url && !$this->isMainUrlContext()) {
            $mainUrl = $this->getMainUrlEntity();
            if ($mainUrl) {
                $qb
                    ->andWhere('s.url IN (:urls)')
                    ->setParameter('urls', [$this->url, $mainUrl])
                ;
            } else {
                $qb
                    ->andWhere('s.url = :url')
                    ->setParameter('url', $this->url)
                ;
            }
        } elseif (null !== $this->url) {
            $qb
                ->andWhere('s.url = :url')
                ->setParameter('url', $this->url)
            ;
        }

        $parametersFromDb = $qb->getQuery()->getResult();

        // Deduplicate by variable: if locked on main URL => pick main; else pick current when available.
        $effective = $this->deduplicateByEffectiveValue($parametersFromDb);

        $parameters = [];

        foreach ($effective as $parameter) {
            /** @var SettingsCurrent $parameter */
            $category = $parameter->getCategory();
            $variable = $parameter->getVariable();

            $hidden = [];
            $serviceKey = 'chamilo_core.settings.'.$category;
            if ($this->schemaRegistry->has($serviceKey)) {
                $schema = $this->schemaRegistry->get($serviceKey);
                if (method_exists($schema, 'getHiddenSettings')) {
                    $hidden = $schema->getHiddenSettings();
                }
            }

            if (\in_array($variable, $hidden, true)) {
                continue;
            }

            $parameters[$category][] = $parameter;
        }

        return $parameters;
    }

    /**
     * @param string $namespace
     * @param string $keyword
     * @param bool   $returnObjects
     *
     * @return array
     */
    public function getParametersFromKeyword($namespace, $keyword = '', $returnObjects = false)
    {
        $this->ensureUrlResolved();

        if (empty($keyword)) {
            $criteria = [
                'category' => $namespace,
            ];

            if (null !== $this->url && !$this->isMainUrlContext()) {
                $mainUrl = $this->getMainUrlEntity();
                if ($mainUrl) {
                    $qb = $this->repository->createQueryBuilder('s')
                        ->where('s.category = :cat')
                        ->andWhere('s.url IN (:urls)')
                        ->setParameter('cat', $namespace)
                        ->setParameter('urls', [$this->url, $mainUrl])
                    ;

                    $parametersFromDb = $qb->getQuery()->getResult();
                } else {
                    $criteria['url'] = $this->url;
                    $parametersFromDb = $this->repository->findBy($criteria);
                }
            } elseif (null !== $this->url) {
                $criteria['url'] = $this->url;
                $parametersFromDb = $this->repository->findBy($criteria);
            } else {
                $parametersFromDb = $this->repository->findBy($criteria);
            }
        } else {
            $qb = $this->repository->createQueryBuilder('s')
                ->where('s.variable LIKE :keyword')
                ->andWhere('s.category = :cat')
                ->setParameter('keyword', "%{$keyword}%")
                ->setParameter('cat', $namespace)
            ;

            if (null !== $this->url && !$this->isMainUrlContext()) {
                $mainUrl = $this->getMainUrlEntity();
                if ($mainUrl) {
                    $qb->andWhere('s.url IN (:urls)')
                        ->setParameter('urls', [$this->url, $mainUrl])
                    ;
                } else {
                    $qb->andWhere('s.url = :url')
                        ->setParameter('url', $this->url)
                    ;
                }
            } elseif (null !== $this->url) {
                $qb->andWhere('s.url = :url')
                    ->setParameter('url', $this->url)
                ;
            }

            $parametersFromDb = $qb->getQuery()->getResult();
        }

        // Deduplicate to return effective rows only.
        $parametersFromDb = $this->deduplicateByEffectiveValue($parametersFromDb);

        if ($returnObjects) {
            return $parametersFromDb;
        }
        $parameters = [];

        /** @var SettingsCurrent $parameter */
        foreach ($parametersFromDb as $parameter) {
            $parameters[$parameter->getVariable()] = $parameter->getSelectedValue();
        }

        return $parameters;
    }

    private function validateSetting(string $name): string
    {
        if (!str_contains($name, '.')) {
            // This code allows the possibility of calling
            // api_get_setting('allow_skills_tool') instead of
            // the "correct" way api_get_setting('platform.allow_skills_tool')
            $items = $this->getVariablesAndCategories();

            if (isset($items[$name])) {
                $originalName = $name;
                $name = $this->renameVariable($name);
                $category = $this->fixCategory(
                    strtolower($name),
                    strtolower($items[$originalName])
                );
                $name = $category.'.'.$name;
            } else {
                $message = \sprintf('Parameter must be in format "category.name", "%s" given.', $name);

                throw new InvalidArgumentException($message);
            }
        }

        return $name;
    }

    /**
     * Load parameter from database (effective values).
     *
     * @param string $namespace
     *
     * @return array
     */
    private function getParameters($namespace)
    {
        $this->ensureUrlResolved();

        $parameters = [];
        $criteria = ['category' => $namespace];

        // Legacy single-URL: return raw category settings.
        if (null === $this->url) {
            $rows = $this->repository->findBy($criteria);

            /** @var SettingsCurrent $parameter */
            foreach ($rows as $parameter) {
                $parameters[$parameter->getVariable()] = $parameter->getSelectedValue();
            }

            return $parameters;
        }

        // Main URL: return only current URL rows.
        if ($this->isMainUrlContext()) {
            $rows = $this->repository->findBy($criteria + ['url' => $this->url]);

            /** @var SettingsCurrent $parameter */
            foreach ($rows as $parameter) {
                $parameters[$parameter->getVariable()] = $parameter->getSelectedValue();
            }

            return $parameters;
        }

        // Sub-URL: merge main + current according to access_url_changeable/access_url_locked on main.
        $mainUrl = $this->getMainUrlEntity();
        if (null === $mainUrl) {
            // Fallback: restrict to current URL if main URL cannot be resolved.
            $rows = $this->repository->findBy($criteria + ['url' => $this->url]);

            /** @var SettingsCurrent $parameter */
            foreach ($rows as $parameter) {
                $parameters[$parameter->getVariable()] = $parameter->getSelectedValue();
            }

            return $parameters;
        }

        /** @var SettingsCurrent[] $mainRows */
        $mainRows = $this->repository->findBy($criteria + ['url' => $mainUrl]);

        /** @var SettingsCurrent[] $currentRows */
        $currentRows = $this->repository->findBy($criteria + ['url' => $this->url]);

        $mainValueByVar = [];
        $changeableByVar = [];
        $lockedByVar = [];

        foreach ($mainRows as $row) {
            $var = $row->getVariable();
            $mainValueByVar[$var] = $row->getSelectedValue();
            $changeableByVar[$var] = (int) $row->getAccessUrlChangeable();
            $lockedByVar[$var] = (int) $row->getAccessUrlLocked();
        }

        // Start with main values
        foreach ($mainValueByVar as $var => $val) {
            $parameters[$var] = $val;
        }

        // Override only for changeable variables AND not locked on main
        foreach ($currentRows as $row) {
            $var = $row->getVariable();

            $isLocked = isset($lockedByVar[$var]) && 1 === (int) $lockedByVar[$var];
            if ($isLocked) {
                continue;
            }

            $isChangeable = !isset($changeableByVar[$var]) || 1 === (int) $changeableByVar[$var];
            if ($isChangeable) {
                $parameters[$var] = $row->getSelectedValue();
            }
        }

        return $parameters;
    }

    private function getAllParametersByCategory()
    {
        $this->ensureUrlResolved();

        $parameters = [];

        // Single URL mode: keep original behaviour.
        if (null === $this->url) {
            $all = $this->repository->findAll();

            /** @var SettingsCurrent $parameter */
            foreach ($all as $parameter) {
                $parameters[$parameter->getCategory()][$parameter->getVariable()] = $parameter->getSelectedValue();
            }

            return $parameters;
        }

        // Main URL: only return current URL rows.
        if ($this->isMainUrlContext()) {
            $all = $this->repository->findBy(['url' => $this->url]);

            /** @var SettingsCurrent $parameter */
            foreach ($all as $parameter) {
                $parameters[$parameter->getCategory()][$parameter->getVariable()] = $parameter->getSelectedValue();
            }

            return $parameters;
        }

        // Sub-URL: merge main + current according to access_url_changeable/access_url_locked on main.
        $mainUrl = $this->getMainUrlEntity();
        if (null === $mainUrl) {
            $all = $this->repository->findBy(['url' => $this->url]);

            /** @var SettingsCurrent $parameter */
            foreach ($all as $parameter) {
                $parameters[$parameter->getCategory()][$parameter->getVariable()] = $parameter->getSelectedValue();
            }

            return $parameters;
        }

        /** @var SettingsCurrent[] $mainRows */
        $mainRows = $this->repository->findBy(['url' => $mainUrl]);

        /** @var SettingsCurrent[] $currentRows */
        $currentRows = $this->repository->findBy(['url' => $this->url]);

        $changeableByVar = [];
        $lockedByVar = [];

        // Start with main values
        foreach ($mainRows as $row) {
            $cat = (string) $row->getCategory();
            $var = $row->getVariable();

            $parameters[$cat][$var] = $row->getSelectedValue();
            $changeableByVar[$var] = (int) $row->getAccessUrlChangeable();
            $lockedByVar[$var] = (int) $row->getAccessUrlLocked();
        }

        // Override with current values only for changeable variables AND not locked on main.
        foreach ($currentRows as $row) {
            $cat = (string) $row->getCategory();
            $var = $row->getVariable();

            $isLocked = isset($lockedByVar[$var]) && 1 === (int) $lockedByVar[$var];
            if ($isLocked) {
                continue;
            }

            $isChangeable = !isset($changeableByVar[$var]) || 1 === (int) $changeableByVar[$var];
            if ($isChangeable) {
                $parameters[$cat][$var] = $row->getSelectedValue();
            }
        }

        return $parameters;
    }

    /**
     * Check if a setting is changeable for the current URL, using the
     * access_url_changeable flag from the main URL (ID = 1).
     *
     * Note: if access_url_locked = 1 on main URL, it is considered NOT changeable
     * for sub-URLs (and it should not even be listed in sub-URLs).
     */
    private function isSettingChangeableForCurrentUrl(string $category, string $variable): bool
    {
        $this->ensureUrlResolved();

        // No URL bound: behave as legacy single-URL platform.
        if (null === $this->url) {
            return true;
        }

        // Main URL can always edit settings. UI already restricts who can see/edit fields.
        if ($this->isMainUrlContext()) {
            return true;
        }

        // Try to load main (canonical) URL.
        $mainUrl = $this->getMainUrlEntity();
        if (null === $mainUrl) {
            // If main URL is missing, fallback to permissive behaviour.
            return true;
        }

        /** @var SettingsCurrent|null $mainSetting */
        $mainSetting = $this->repository->findOneBy([
            'category' => $category,
            'variable' => $variable,
            'url' => $mainUrl,
        ]);

        if (null === $mainSetting) {
            // If there is no canonical row, do not block changes.
            return true;
        }

        // If the setting is globally locked on main URL, sub-URLs must not override it.
        if (1 === (int) $mainSetting->getAccessUrlLocked()) {
            return false;
        }

        // When access_url_changeable is false/0 on main URL,
        // secondary URLs must not override the value.
        return (bool) $mainSetting->getAccessUrlChangeable();
    }

    private function createSettingForCurrentUrl(
        string $category,
        string $variable,
        string $value,
        ?SettingsCurrent $canonical = null
    ): SettingsCurrent {
        $this->ensureUrlResolved();

        $url = $this->getUrl();

        // If canonical metadata is not provided, try to resolve it from main URL.
        if (null === $canonical) {
            $mainUrl = $this->getMainUrlEntity();
            if (null !== $mainUrl) {
                // 1) Try exact category first
                $found = $this->repository->findOneBy([
                    'category' => $category,
                    'variable' => $variable,
                    'url' => $mainUrl,
                ]);

                // 2) Try legacy category variant (e.g. "Platform")
                if (!$found instanceof SettingsCurrent) {
                    $found = $this->repository->findOneBy([
                        'category' => ucfirst($category),
                        'variable' => $variable,
                        'url' => $mainUrl,
                    ]);
                }

                // 3) As a last resort, ignore category (still restricted to main URL)
                if (!$found instanceof SettingsCurrent) {
                    $found = $this->repository->findOneBy([
                        'variable' => $variable,
                        'url' => $mainUrl,
                    ]);
                }

                if ($found instanceof SettingsCurrent) {
                    $canonical = $found;
                }
            }
        }

        // Fallback: any existing row for this variable (avoid losing metadata).
        if (null === $canonical) {
            $found = $this->repository->findOneBy([
                'variable' => $variable,
            ]);

            if ($found instanceof SettingsCurrent) {
                $canonical = $found;
            }
        }

        // IMPORTANT: Initialize typed properties before any getter is called.
        $setting = (new SettingsCurrent())
            ->setVariable($variable)
            ->setCategory($category)
            ->setSelectedValue($value)
            ->setUrl($url)
            ->setTitle($variable) // Safe default; may be overwritten by canonical metadata.
        ;

        // Sync metadata from canonical definition when possible.
        $this->syncSettingMetadataFromCanonical($setting, $canonical, $variable);

        return $setting;
    }

    /**
     * Get variables and categories as in 1.11.x.
     */
    private function getVariablesAndCategories(): array
    {
        return [
            'Institution' => 'Platform',
            'InstitutionUrl' => 'Platform',
            'siteName' => 'Platform',
            'site_name' => 'Platform',
            'emailAdministrator' => 'admin',
            // 'emailAdministrator' => 'Platform',
            'administratorSurname' => 'admin',
            'administratorTelephone' => 'admin',
            'administratorName' => 'admin',
            'show_administrator_data' => 'Platform',
            'show_tutor_data' => 'Session',
            'show_teacher_data' => 'Platform',
            'show_toolshortcuts' => 'Course',
            'allow_group_categories' => 'Course',
            'server_type' => 'Platform',
            'platformLanguage' => 'Language',
            'showonline' => 'Platform',
            'profile' => 'User',
            'default_document_quotum' => 'Course',
            'registration' => 'User',
            'default_group_quotum' => 'Course',
            'allow_registration' => 'Platform',
            'allow_registration_as_teacher' => 'Platform',
            'allow_lostpassword' => 'Platform',
            'allow_user_headings' => 'Course',
            'allow_personal_agenda' => 'agenda',
            'display_coursecode_in_courselist' => 'Platform',
            'display_teacher_in_courselist' => 'Platform',
            'permanently_remove_deleted_files' => 'Tools',
            'dropbox_allow_overwrite' => 'Tools',
            'dropbox_max_filesize' => 'Tools',
            'dropbox_allow_just_upload' => 'Tools',
            'dropbox_allow_student_to_student' => 'Tools',
            'dropbox_allow_group' => 'Tools',
            'dropbox_allow_mailing' => 'Tools',
            'extended_profile' => 'User',
            'student_view_enabled' => 'Platform',
            'show_navigation_menu' => 'Course',
            'enable_tool_introduction' => 'course',
            'page_after_login' => 'Platform',
            'time_limit_whosonline' => 'Platform',
            'breadcrumbs_course_homepage' => 'Course',
            'example_material_course_creation' => 'Platform',
            'account_valid_duration' => 'Platform',
            'use_session_mode' => 'Session',
            'allow_email_editor' => 'Tools',
            'show_email_addresses' => 'Platform',
            'service_ppt2lp' => 'NULL',
            'upload_extensions_list_type' => 'Security',
            'upload_extensions_blacklist' => 'Security',
            'upload_extensions_whitelist' => 'Security',
            'upload_extensions_skip' => 'Security',
            'upload_extensions_replace_by' => 'Security',
            'show_number_of_courses' => 'Platform',
            'show_empty_course_categories' => 'Platform',
            'show_back_link_on_top_of_tree' => 'Platform',
            'show_different_course_language' => 'Platform',
            'split_users_upload_directory' => 'Tuning',
            'display_categories_on_homepage' => 'Platform',
            'permissions_for_new_directories' => 'Security',
            'permissions_for_new_files' => 'Security',
            'show_tabs' => 'Platform',
            'default_forum_view' => 'Course',
            'platform_charset' => 'Languages',
            'survey_email_sender_noreply' => 'Course',
            'gradebook_enable' => 'Gradebook',
            'gradebook_score_display_coloring' => 'Gradebook',
            'gradebook_score_display_custom' => 'Gradebook',
            'gradebook_score_display_colorsplit' => 'Gradebook',
            'gradebook_score_display_upperlimit' => 'Gradebook',
            'gradebook_number_decimals' => 'Gradebook',
            'user_selected_theme' => 'Platform',
            'allow_course_theme' => 'Course',
            'show_closed_courses' => 'Platform',
            'extendedprofile_registration' => 'User',
            'extendedprofile_registrationrequired' => 'User',
            'add_users_by_coach' => 'Session',
            'extend_rights_for_coach' => 'Security',
            'extend_rights_for_coach_on_survey' => 'Security',
            'course_create_active_tools' => 'Tools',
            'show_session_coach' => 'Session',
            'allow_users_to_create_courses' => 'Platform',
            'allow_message_tool' => 'Tools',
            'allow_social_tool' => 'Tools',
            'show_session_data' => 'Session',
            'allow_use_sub_language' => 'language',
            'show_glossary_in_documents' => 'Course',
            'allow_terms_conditions' => 'Platform',
            'search_enabled' => 'Search',
            'search_prefilter_prefix' => 'Search',
            'search_show_unlinked_results' => 'Search',
            'allow_coach_to_edit_course_session' => 'Session',
            'show_glossary_in_extra_tools' => 'Course',
            'send_email_to_admin_when_create_course' => 'Platform',
            'go_to_course_after_login' => 'Course',
            'math_asciimathML' => 'Editor',
            'enabled_asciisvg' => 'Editor',
            'include_asciimathml_script' => 'Editor',
            'youtube_for_students' => 'Editor',
            'block_copy_paste_for_students' => 'Editor',
            'more_buttons_maximized_mode' => 'Editor',
            'students_download_folders' => 'Document',
            'users_copy_files' => 'Tools',
            'allow_students_to_create_groups_in_social' => 'Tools',
            'allow_send_message_to_all_platform_users' => 'Message',
            'message_max_upload_filesize' => 'Tools',
            'use_users_timezone' => 'profile',
            'timezone_value' => 'platform',
            'allow_user_course_subscription_by_course_admin' => 'Security',
            'show_link_bug_notification' => 'Platform',
            'show_link_ticket_notification' => 'Platform',
            'course_validation' => 'course',
            'course_validation_terms_and_conditions_url' => 'Platform',
            'enabled_wiris' => 'Editor',
            'allow_spellcheck' => 'Editor',
            'force_wiki_paste_as_plain_text' => 'Editor',
            'enabled_googlemaps' => 'Editor',
            'enabled_imgmap' => 'Editor',
            'enabled_support_svg' => 'Tools',
            'pdf_export_watermark_enable' => 'Platform',
            'pdf_export_watermark_by_course' => 'Platform',
            'pdf_export_watermark_text' => 'Platform',
            'enabled_insertHtml' => 'Editor',
            'students_export2pdf' => 'Document',
            'exercise_min_score' => 'Course',
            'exercise_max_score' => 'Course',
            'show_users_folders' => 'Tools',
            'show_default_folders' => 'Tools',
            'show_chat_folder' => 'Tools',
            'course_hide_tools' => 'Course',
            'show_groups_to_users' => 'Group',
            'accessibility_font_resize' => 'Platform',
            'hide_courses_in_sessions' => 'Session',
            'enable_quiz_scenario' => 'Course',
            'filter_terms' => 'Security',
            'header_extra_content' => 'Tracking',
            'footer_extra_content' => 'Tracking',
            'show_documents_preview' => 'Tools',
            'htmlpurifier_wiki' => 'Editor',
            'cas_activate' => 'CAS',
            'cas_server' => 'CAS',
            'cas_server_uri' => 'CAS',
            'cas_port' => 'CAS',
            'cas_protocol' => 'CAS',
            'cas_add_user_activate' => 'CAS',
            'update_user_info_cas_with_ldap' => 'CAS',
            'student_page_after_login' => 'Platform',
            'teacher_page_after_login' => 'Platform',
            'drh_page_after_login' => 'Platform',
            'sessionadmin_page_after_login' => 'Session',
            'student_autosubscribe' => 'Platform',
            'teacher_autosubscribe' => 'Platform',
            'drh_autosubscribe' => 'Platform',
            'sessionadmin_autosubscribe' => 'Session',
            'scorm_cumulative_session_time' => 'Course',
            'allow_hr_skills_management' => 'Gradebook',
            'enable_help_link' => 'Platform',
            'teachers_can_change_score_settings' => 'Gradebook',
            'allow_users_to_change_email_with_no_password' => 'User',
            'show_admin_toolbar' => 'display',
            'allow_global_chat' => 'Platform',
            'languagePriority1' => 'language',
            'languagePriority2' => 'language',
            'languagePriority3' => 'language',
            'languagePriority4' => 'language',
            'login_is_email' => 'Platform',
            'courses_default_creation_visibility' => 'Course',
            'gradebook_enable_grade_model' => 'Gradebook',
            'teachers_can_change_grade_model_settings' => 'Gradebook',
            'gradebook_default_weight' => 'Gradebook',
            'ldap_description' => 'LDAP',
            'shibboleth_description' => 'Shibboleth',
            'facebook_description' => 'Facebook',
            'gradebook_locking_enabled' => 'Gradebook',
            'gradebook_default_grade_model_id' => 'Gradebook',
            'allow_session_admins_to_manage_all_sessions' => 'Session',
            'allow_skills_tool' => 'Platform',
            'allow_public_certificates' => 'Course',
            'platform_unsubscribe_allowed' => 'Platform',
            'enable_iframe_inclusion' => 'Editor',
            'show_hot_courses' => 'Platform',
            'enable_webcam_clip' => 'Tools',
            'use_custom_pages' => 'Platform',
            'tool_visible_by_default_at_creation' => 'Tools',
            'prevent_session_admins_to_manage_all_users' => 'Session',
            'documents_default_visibility_defined_in_course' => 'Tools',
            'enabled_mathjax' => 'Editor',
            'meta_twitter_site' => 'Tracking',
            'meta_twitter_creator' => 'Tracking',
            'meta_title' => 'Tracking',
            'meta_description' => 'Tracking',
            'meta_image_path' => 'Tracking',
            'allow_teachers_to_create_sessions' => 'Session',
            'institution_address' => 'Platform',
            'chamilo_database_version' => 'null',
            'cron_remind_course_finished_activate' => 'Crons',
            'cron_remind_course_expiration_frequency' => 'Crons',
            'cron_remind_course_expiration_activate' => 'Crons',
            'allow_coach_feedback_exercises' => 'Session',
            'allow_my_files' => 'Platform',
            'ticket_allow_student_add' => 'Ticket',
            'ticket_send_warning_to_all_admins' => 'Ticket',
            'ticket_warn_admin_no_user_in_category' => 'Ticket',
            'ticket_allow_category_edition' => 'Ticket',
            'load_term_conditions_section' => 'Platform',
            'show_terms_if_profile_completed' => 'Profile',
            'hide_home_top_when_connected' => 'Platform',
            'hide_global_announcements_when_not_connected' => 'Platform',
            'course_creation_use_template' => 'Course',
            'allow_strength_pass_checker' => 'Security',
            'allow_captcha' => 'Security',
            'captcha_number_mistakes_to_block_account' => 'Security',
            'captcha_time_to_block' => 'Security',
            'drh_can_access_all_session_content' => 'Session',
            'display_groups_forum_in_general_tool' => 'Tools',
            'allow_tutors_to_assign_students_to_session' => 'Session',
            'allow_lp_return_link' => 'Course',
            'hide_scorm_export_link' => 'Course',
            'hide_scorm_copy_link' => 'Course',
            'hide_scorm_pdf_link' => 'Course',
            'session_days_before_coach_access' => 'Session',
            'session_days_after_coach_access' => 'Session',
            'pdf_logo_header' => 'Course',
            'order_user_list_by_official_code' => 'Platform',
            'email_alert_manager_on_new_quiz' => 'exercise',
            'show_official_code_exercise_result_list' => 'Tools',
            'auto_detect_language_custom_pages' => 'Platform',
            'lp_show_reduced_report' => 'Course',
            'allow_session_course_copy_for_teachers' => 'Session',
            'hide_logout_button' => 'Platform',
            'redirect_admin_to_courses_list' => 'Platform',
            'course_images_in_courses_list' => 'Course',
            'student_publication_to_take_in_gradebook' => 'Gradebook',
            'certificate_filter_by_official_code' => 'Gradebook',
            'exercise_max_keditors_in_page' => 'Tools',
            'document_if_file_exists_option' => 'Tools',
            'add_gradebook_certificates_cron_task_enabled' => 'Gradebook',
            'openbadges_backpack' => 'Gradebook',
            'cookie_warning' => 'Tools',
            'hide_course_group_if_no_tools_available' => 'Tools',
            'registration.soap.php.decode_utf8' => 'Platform',
            'allow_delete_attendance' => 'Tools',
            'gravatar_enabled' => 'Platform',
            'gravatar_type' => 'Platform',
            'limit_session_admin_role' => 'Session',
            'show_session_description' => 'Session',
            'hide_certificate_export_link_students' => 'Gradebook',
            'hide_certificate_export_link' => 'Gradebook',
            'dropbox_hide_course_coach' => 'Tools',
            'dropbox_hide_general_coach' => 'Tools',
            'session_course_ordering' => 'Session',
            'gamification_mode' => 'Platform',
            'prevent_multiple_simultaneous_login' => 'Security',
            'gradebook_detailed_admin_view' => 'Gradebook',
            'user_reset_password' => 'Security',
            'user_reset_password_token_limit' => 'Security',
            'my_courses_view_by_session' => 'Session',
            'show_full_skill_name_on_skill_wheel' => 'Platform',
            'messaging_allow_send_push_notification' => 'WebServices',
            'messaging_gdc_project_number' => 'WebServices',
            'messaging_gdc_api_key' => 'WebServices',
            'teacher_can_select_course_template' => 'Course',
            'allow_show_skype_account' => 'Platform',
            'allow_show_linkedin_url' => 'Platform',
            'enable_profile_user_address_geolocalization' => 'User',
            'show_official_code_whoisonline' => 'Profile',
            'icons_mode_svg' => 'display',
            'default_calendar_view' => 'agenda',
            'exercise_invisible_in_session' => 'exercise',
            'configure_exercise_visibility_in_course' => 'exercise',
            'allow_download_documents_by_api_key' => 'Webservices',
            'profiling_filter_adding_users' => 'course',
            'donotlistcampus' => 'platform',
            'course_creation_splash_screen' => 'Course',
            'translate_html' => 'Editor',
        ];
    }

    /**
     * Rename old variable with variable used in Chamilo 2.0.
     *
     * @param string $variable
     */
    private function renameVariable($variable)
    {
        $list = [
            'timezone_value' => 'timezone',
            'Institution' => 'institution',
            'SiteName' => 'site_name',
            'siteName' => 'site_name',
            'InstitutionUrl' => 'institution_url',
            'registration' => 'required_profile_fields',
            'platformLanguage' => 'platform_language',
            'languagePriority1' => 'language_priority_1',
            'languagePriority2' => 'language_priority_2',
            'languagePriority3' => 'language_priority_3',
            'languagePriority4' => 'language_priority_4',
            'gradebook_score_display_coloring' => 'my_display_coloring',
            'ProfilingFilterAddingUsers' => 'profiling_filter_adding_users',
            'course_create_active_tools' => 'active_tools_on_create',
            'emailAdministrator' => 'administrator_email',
            'administratorSurname' => 'administrator_surname',
            'administratorName' => 'administrator_name',
            'administratorTelephone' => 'administrator_phone',
            'registration.soap.php.decode_utf8' => 'decode_utf8',
            'profile' => 'changeable_options',
        ];

        return $list[$variable] ?? $variable;
    }

    /**
     * Replace old Chamilo 1.x category with 2.0 version.
     *
     * @param string $variable
     * @param string $defaultCategory
     */
    private function fixCategory($variable, $defaultCategory)
    {
        $settings = [
            'cookie_warning' => 'platform',
            'donotlistcampus' => 'platform',
            'administrator_email' => 'admin',
            'administrator_surname' => 'admin',
            'administrator_name' => 'admin',
            'administrator_phone' => 'admin',
            'exercise_max_keditors_in_page' => 'exercise',
            'allow_hr_skills_management' => 'skill',
            'accessibility_font_resize' => 'display',
            'account_valid_duration' => 'profile',
            'allow_global_chat' => 'chat',
            'allow_lostpassword' => 'registration',
            'allow_registration' => 'registration',
            'allow_registration_as_teacher' => 'registration',
            'required_profile_fields' => 'registration',
            'allow_skills_tool' => 'skill',
            'allow_terms_conditions' => 'registration',
            'allow_users_to_create_courses' => 'course',
            'auto_detect_language_custom_pages' => 'language',
            'platform_language' => 'language',
            'course_validation' => 'course',
            'course_validation_terms_and_conditions_url' => 'course',
            'display_categories_on_homepage' => 'display',
            'display_coursecode_in_courselist' => 'course',
            'display_teacher_in_courselist' => 'course',
            'drh_autosubscribe' => 'registration',
            'drh_page_after_login' => 'registration',
            'enable_help_link' => 'display',
            'example_material_course_creation' => 'course',
            'login_is_email' => 'profile',
            'noreply_email_address' => 'mail',
            'pdf_export_watermark_by_course' => 'document',
            'pdf_export_watermark_enable' => 'document',
            'pdf_export_watermark_text' => 'document',
            'platform_unsubscribe_allowed' => 'registration',
            'send_email_to_admin_when_create_course' => 'course',
            'show_admin_toolbar' => 'display',
            'show_administrator_data' => 'display',
            'show_back_link_on_top_of_tree' => 'display',
            'show_closed_courses' => 'display',
            'show_different_course_language' => 'display',
            'show_email_addresses' => 'display',
            'show_empty_course_categories' => 'display',
            'show_full_skill_name_on_skill_wheel' => 'skill',
            'show_hot_courses' => 'display',
            'show_link_bug_notification' => 'display',
            'show_number_of_courses' => 'display',
            'show_teacher_data' => 'display',
            'showonline' => 'display',
            'student_autosubscribe' => 'registration',
            'student_page_after_login' => 'registration',
            'student_view_enabled' => 'course',
            'teacher_autosubscribe' => 'registration',
            'teacher_page_after_login' => 'registration',
            'time_limit_whosonline' => 'display',
            'user_selected_theme' => 'profile',
            'hide_global_announcements_when_not_connected' => 'announcement',
            'hide_home_top_when_connected' => 'display',
            'hide_logout_button' => 'display',
            'institution_address' => 'platform',
            'redirect_admin_to_courses_list' => 'admin',
            'use_custom_pages' => 'platform',
            'allow_group_categories' => 'group',
            'allow_user_headings' => 'display',
            'default_document_quotum' => 'document',
            'default_forum_view' => 'forum',
            'default_group_quotum' => 'document',
            'enable_quiz_scenario' => 'exercise',
            'exercise_max_score' => 'exercise',
            'exercise_min_score' => 'exercise',
            'pdf_logo_header' => 'platform',
            'show_glossary_in_documents' => 'document',
            'show_glossary_in_extra_tools' => 'glossary',
            'survey_email_sender_noreply' => 'survey',
            'allow_coach_feedback_exercises' => 'exercise',
            'sessionadmin_autosubscribe' => 'registration',
            'sessionadmin_page_after_login' => 'registration',
            'show_tutor_data' => 'display',
            'allow_social_tool' => 'social',
            'allow_message_tool' => 'message',
            'allow_email_editor' => 'editor',
            'show_link_ticket_notification' => 'display',
            'permissions_for_new_directories' => 'document',
            'enable_profile_user_address_geolocalization' => 'profile',
            'allow_show_skype_account' => 'profile',
            'allow_show_linkedin_url' => 'profile',
            'allow_students_to_create_groups_in_social' => 'social',
            'default_calendar_view' => 'agenda',
            'documents_default_visibility_defined_in_course' => 'document',
            'message_max_upload_filesize' => 'message',
            'course_create_active_tools' => 'course',
            'tool_visible_by_default_at_creation' => 'document',
            'show_users_folders' => 'document',
            'show_default_folders' => 'document',
            'show_chat_folder' => 'chat',
            'enabled_support_svg' => 'editor',
            'enable_webcam_clip' => 'document',
            'permanently_remove_deleted_files' => 'document',
            'allow_delete_attendance' => 'attendance',
            'display_groups_forum_in_general_tool' => 'forum',
            'dropbox_allow_overwrite' => 'dropbox',
            'allow_user_course_subscription_by_course_admin' => 'course',
            'hide_course_group_if_no_tools_available' => 'group',
            'extend_rights_for_coach_on_survey' => 'survey',
            'show_official_code_exercise_result_list' => 'exercise',
            'dropbox_max_filesize' => 'dropbox',
            'dropbox_allow_just_upload' => 'dropbox',
            'dropbox_allow_student_to_student' => 'dropbox',
            'dropbox_allow_group' => 'dropbox',
            'dropbox_allow_mailing' => 'dropbox',
            'upload_extensions_list_type' => 'document',
            'upload_extensions_blacklist' => 'document',
            'upload_extensions_skip' => 'document',
            'changeable_options' => 'profile',
            'users_copy_files' => 'document',
            'document_if_file_exists_option' => 'document',
            'permissions_for_new_files' => 'document',
            'extended_profile' => 'profile',
            'split_users_upload_directory' => 'profile',
            'show_documents_preview' => 'document',
            'messaging_allow_send_push_notification' => 'webservice',
            'messaging_gdc_project_number' => 'webservice',
            'messaging_gdc_api_key' => 'webservice',
            'allow_download_documents_by_api_key' => 'webservice',
            'profiling_filter_adding_users' => 'course',
            'active_tools_on_create' => 'course',
        ];

        return $settings[$variable] ?? $defaultCategory;
    }

    private function transformToString($value): string
    {
        if (\is_array($value)) {
            return implode(',', $value);
        }

        if ($value instanceof Course) {
            return (string) $value->getId();
        }

        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (null === $value) {
            return '';
        }

        return (string) $value;
    }

    private function normalizeNullsBeforeResolve(array $parameters, SettingsBuilder $settingsBuilder): array
    {
        foreach ($parameters as $k => $v) {
            if (null === $v && $settingsBuilder->isDefined($k)) {
                unset($parameters[$k]);
            }
        }

        return $parameters;
    }

    /**
     * Resolve current AccessUrl automatically when not set by controllers.
     * This avoids mixing settings across URLs in MultiURL environments.
     */
    private function ensureUrlResolved(): void
    {
        if (null !== $this->url) {
            return;
        }

        $repo = $this->manager->getRepository(AccessUrl::class);

        $req = $this->request->getCurrentRequest() ?? $this->request->getMainRequest();
        if (null !== $req) {
            $host = $req->getHost();
            $scheme = $req->getScheme();

            // Try exact matches first (scheme + host, with and without trailing slash).
            $candidates = array_values(array_unique([
                $scheme.'://'.$host.'/',
                $scheme.'://'.$host,
                'https://'.$host.'/',
                'https://'.$host,
                'http://'.$host.'/',
                'http://'.$host,
            ]));

            foreach ($candidates as $candidate) {
                $found = $repo->findOneBy(['url' => $candidate]);
                if ($found instanceof AccessUrl) {
                    $this->url = $found;

                    return;
                }
            }

            // Fallback: match by host ignoring scheme and trailing slash.
            // This avoids "URL not resolved => legacy mode => mixed settings".
            $all = $repo->findAll();
            foreach ($all as $u) {
                if (!$u instanceof AccessUrl) {
                    continue;
                }

                $dbUrl = (string) $u->getUrl();
                $dbHost = parse_url($dbUrl, PHP_URL_HOST);

                if (null !== $dbHost && strtolower($dbHost) === strtolower($host)) {
                    $this->url = $u;

                    return;
                }
            }
        }

        // Fallback to main URL (ID=1).
        $main = $repo->find(1);
        if ($main instanceof AccessUrl) {
            $this->url = $main;

            return;
        }

        // Final fallback: first URL in DB.
        $first = $repo->findOneBy([], ['id' => 'ASC']);
        if ($first instanceof AccessUrl) {
            $this->url = $first;
        }
    }

    private function getMainUrlEntity(): ?AccessUrl
    {
        if ($this->mainUrlCache instanceof AccessUrl) {
            return $this->mainUrlCache;
        }

        $repo = $this->manager->getRepository(AccessUrl::class);
        $main = $repo->find(1);

        if ($main instanceof AccessUrl) {
            $this->mainUrlCache = $main;

            return $main;
        }

        return null;
    }

    private function isMainUrlContext(): bool
    {
        if (null === $this->url) {
            return true;
        }

        $id = $this->url->getId();

        return null !== $id && 1 === $id;
    }

    private function getSessionSchemaCacheKey(): string
    {
        $base = 'schemas';

        if (null === $this->url || null === $this->url->getId()) {
            return $base;
        }

        return $base.'_url_'.$this->url->getId();
    }

    private function clearSessionSchemaCache(): void
    {
        $this->resolvedSettings = [];
        $this->schemaList = [];

        $req = $this->request->getCurrentRequest() ?? $this->request->getMainRequest();
        if (null === $req) {
            return;
        }

        $session = $req->getSession();
        if (!$session) {
            return;
        }

        // Clear both legacy cache and any URL-scoped schema caches for this session.
        foreach (array_keys((array) $session->all()) as $key) {
            if ('schemas' === $key || str_starts_with($key, 'schemas_url_')) {
                $session->remove($key);
            }
        }
    }

    /**
     * Deduplicate a list of SettingsCurrent rows by variable, using effective MultiURL logic:
     * - If current URL is main or not set => return rows as-is.
     * - If on a sub-URL:
     *   - If main says access_url_locked = 1 => keep main row
     *   - Else if main says access_url_changeable = 0 => keep main row
     *   - Else => keep current row when available, fallback to main
     *
     * @param array<int, mixed> $rows
     *
     * @return SettingsCurrent[]
     */
    private function deduplicateByEffectiveValue(array $rows): array
    {
        if (null === $this->url || $this->isMainUrlContext()) {
            return array_values(array_filter($rows, fn ($r) => $r instanceof SettingsCurrent));
        }

        $mainUrl = $this->getMainUrlEntity();
        if (null === $mainUrl) {
            return array_values(array_filter($rows, fn ($r) => $r instanceof SettingsCurrent));
        }

        $byVar = [];
        $mainChangeable = [];
        $mainLocked = [];
        $mainRowByVar = [];
        $currentRowByVar = [];

        foreach ($rows as $r) {
            if (!$r instanceof SettingsCurrent) {
                continue;
            }

            $var = $r->getVariable();
            $rUrlId = $r->getUrl()->getId();

            if (1 === $rUrlId) {
                $mainRowByVar[$var] = $r;
                $mainChangeable[$var] = (int) $r->getAccessUrlChangeable();
                $mainLocked[$var] = (int) $r->getAccessUrlLocked();
            } elseif (null !== $this->url && $rUrlId === $this->url->getId()) {
                $currentRowByVar[$var] = $r;
            }
        }

        $vars = array_unique(array_merge(array_keys($mainRowByVar), array_keys($currentRowByVar)));

        foreach ($vars as $var) {
            $isLocked = isset($mainLocked[$var]) && 1 === (int) $mainLocked[$var];
            if ($isLocked) {
                if (isset($mainRowByVar[$var])) {
                    $byVar[$var] = $mainRowByVar[$var];
                } elseif (isset($currentRowByVar[$var])) {
                    $byVar[$var] = $currentRowByVar[$var];
                }

                continue;
            }

            $isNotChangeable = isset($mainChangeable[$var]) && 0 === (int) $mainChangeable[$var];
            if ($isNotChangeable) {
                if (isset($mainRowByVar[$var])) {
                    $byVar[$var] = $mainRowByVar[$var];
                } elseif (isset($currentRowByVar[$var])) {
                    $byVar[$var] = $currentRowByVar[$var];
                }

                continue;
            }

            if (isset($currentRowByVar[$var])) {
                $byVar[$var] = $currentRowByVar[$var];
            } elseif (isset($mainRowByVar[$var])) {
                $byVar[$var] = $mainRowByVar[$var];
            }
        }

        return array_values($byVar);
    }

    /**
     * Load canonical settings rows (main URL ID=1) for a given category.
     *
     * @return array<string, SettingsCurrent>
     */
    private function getCanonicalSettingsMap(string $category): array
    {
        $this->ensureUrlResolved();

        $mainUrl = $this->getMainUrlEntity();
        if (null === $mainUrl) {
            return [];
        }

        $categories = $this->getCategoryVariants($category);

        $qb = $this->repository->createQueryBuilder('s');
        $qb
            ->where('s.url = :url')
            ->andWhere('s.category IN (:cats)')
            ->setParameter('url', $mainUrl->getId())
            ->setParameter('cats', $categories)
        ;

        $rows = $qb->getQuery()->getResult();

        $map = [];
        foreach ($rows as $row) {
            if ($row instanceof SettingsCurrent) {
                $map[$row->getVariable()] = $row;
            }
        }

        return $map;
    }

    /**
     * Keep the row metadata consistent across URLs.
     * - Sync title/comment/type/scope/subkey/subkeytext/value_template_id from canonical row when available
     * - Never overwrite existing metadata if canonical is missing (prevents "title reset to variable")
     * - Sync access_url_changeable + access_url_locked from canonical row when available.
     */
    private function syncSettingMetadataFromCanonical(
        SettingsCurrent $setting,
        ?SettingsCurrent $canonical,
        string $fallbackVariable
    ): void {
        $isNew = null === $setting->getId();

        // If canonical is missing, do NOT destroy existing metadata.
        // Only ensure safe defaults for brand-new rows.
        if (!$canonical instanceof SettingsCurrent) {
            if ($isNew) {
                $setting->setTitle($fallbackVariable);

                // Safe defaults for new rows.
                $setting->setAccessUrlChangeable(1);
                $setting->setAccessUrlLocked(0);
            }

            return;
        }

        // Title: use canonical title when available, otherwise keep existing title (or fallback for new rows).
        $canonicalTitle = trim((string) $canonical->getTitle());
        if ('' !== $canonicalTitle) {
            $setting->setTitle($canonicalTitle);
        } elseif ($isNew) {
            $setting->setTitle($fallbackVariable);
        }

        // Comment: only overwrite if canonical has a non-null value, or if the row is new.
        if (method_exists($setting, 'setComment') && method_exists($canonical, 'getComment')) {
            $canonicalComment = $canonical->getComment();
            if (null !== $canonicalComment || $isNew) {
                $this->assignNullableString($setting, 'setComment', $canonicalComment);
            }
        }

        // Type: NEVER pass null to setType(string $type).
        if (method_exists($setting, 'setType') && method_exists($canonical, 'getType')) {
            $type = $canonical->getType();
            if (null !== $type && '' !== trim((string) $type)) {
                $setting->setType((string) $type);
            }
        }

        if (method_exists($setting, 'setScope') && method_exists($canonical, 'getScope')) {
            $scope = $canonical->getScope();
            if (null !== $scope) {
                $setting->setScope($scope);
            }
        }

        if (method_exists($setting, 'setSubkey') && method_exists($canonical, 'getSubkey')) {
            $subkey = $canonical->getSubkey();
            if (null !== $subkey) {
                $setting->setSubkey($subkey);
            }
        }

        if (method_exists($setting, 'setSubkeytext') && method_exists($canonical, 'getSubkeytext')) {
            $subkeytext = $canonical->getSubkeytext();
            if (null !== $subkeytext) {
                $setting->setSubkeytext($subkeytext);
            }
        }

        if (method_exists($setting, 'setValueTemplate') && method_exists($canonical, 'getValueTemplate')) {
            $tpl = $canonical->getValueTemplate();
            if (null !== $tpl) {
                $setting->setValueTemplate($tpl);
            }
        }

        // Sync MultiURL flags from canonical.
        $setting->setAccessUrlChangeable((int) $canonical->getAccessUrlChangeable());
        $setting->setAccessUrlLocked((int) $canonical->getAccessUrlLocked());
    }

    /**
     * Assign a nullable string to a setter, respecting parameter nullability.
     * If setter does not allow null, it will receive an empty string instead.
     */
    private function assignNullableString(object $target, string $setter, ?string $value): void
    {
        if (!method_exists($target, $setter)) {
            return;
        }

        $ref = new ReflectionMethod($target, $setter);
        $param = $ref->getParameters()[0] ?? null;

        if (null === $param) {
            return;
        }

        $type = $param->getType();
        $allowsNull = true;

        if ($type instanceof ReflectionNamedType) {
            $allowsNull = $type->allowsNull();
        }

        if (null === $value && !$allowsNull) {
            $target->{$setter}('');

            return;
        }

        $target->{$setter}($value);
    }

    /**
     * Return category variants to support legacy stored categories (e.g. "Platform" vs "platform").
     */
    private function getCategoryVariants(string $category): array
    {
        $variants = [
            $category,
            ucfirst($category),
        ];

        return array_values(array_unique($variants));
    }

    /**
     * Propagate selected platform settings to course settings (c_course_setting).
     * This is designed to scale: add categories/variables to COURSE_SETTINGS_PROPAGATION.
     *
     * @param string $category   Simple category name (e.g. "ai_helpers")
     * @param array  $parameters Persisted platform parameters (already stringified)
     */
    private function propagatePlatformSettingsToCoursesIfNeeded(string $category, array $parameters): void
    {
        if (!isset(self::COURSE_SETTINGS_PROPAGATION[$category])) {
            return;
        }

        // Example gate for AI helpers: if the master switch is off, do nothing.
        if ('ai_helpers' === $category && ('true' !== (string) ($parameters['enable_ai_helpers'] ?? 'false'))) {
            return;
        }

        $vars = self::COURSE_SETTINGS_PROPAGATION[$category];

        // Build list of course settings to enable based on platform values.
        // Strategy: enable only those set to true at platform-level.
        $varToValue = [];
        foreach ($vars as $var) {
            if ('true' === (string) ($parameters[$var] ?? 'false')) {
                $varToValue[$var] = 'true';
            }
        }

        if (empty($varToValue)) {
            return;
        }

        // By default: only insert missing rows, do NOT overwrite existing course choices.
        // If you want to force overwrite to true across all courses, set $force = true.
        $force = false;

        $this->upsertCourseSettingsForAllCourses($category, $varToValue, $force);
    }

    /**
     * Upsert course settings for all courses in batches.
     *
     * @param array<string,string> $varToValue variable => value
     */
    private function upsertCourseSettingsForAllCourses(string $category, array $varToValue, bool $force = false): void
    {
        // Fetch all course IDs (batch-friendly)
        $courseIdRows = $this->manager->createQueryBuilder()
            ->select('c.id')
            ->from(Course::class, 'c')
            ->getQuery()
            ->getScalarResult()
        ;

        $courseIds = array_map(
            static fn (array $r): int => (int) ($r['id'] ?? 0),
            $courseIdRows
        );
        $courseIds = array_values(array_filter($courseIds, static fn (int $id): bool => $id > 0));

        if (empty($courseIds)) {
            return;
        }

        $vars = array_keys($varToValue);
        $batchSize = 300;

        for ($offset = 0; $offset < \count($courseIds); $offset += $batchSize) {
            $chunk = \array_slice($courseIds, $offset, $batchSize);

            // Load existing settings for this chunk
            $existing = $this->manager->createQueryBuilder()
                ->select('cs')
                ->from(CCourseSetting::class, 'cs')
                ->where('cs.cId IN (:cids)')
                ->andWhere('cs.variable IN (:vars)')
                ->andWhere('cs.category = :cat')
                ->setParameter('cids', $chunk)
                ->setParameter('vars', $vars)
                ->setParameter('cat', $category)
                ->getQuery()
                ->getResult()
            ;

            /** @var array<int, array<string, CCourseSetting>> $byCourse */
            $byCourse = [];
            foreach ($existing as $row) {
                if (!$row instanceof CCourseSetting) {
                    continue;
                }
                $cId = (int) $row->getCId();
                $var = (string) $row->getVariable();
                $byCourse[$cId][$var] = $row;
            }

            foreach ($chunk as $cId) {
                foreach ($varToValue as $var => $value) {
                    $row = $byCourse[$cId][$var] ?? null;

                    if ($row instanceof CCourseSetting) {
                        if ($force && (string) $row->getValue() !== $value) {
                            $row->setValue($value);
                            $this->manager->persist($row);
                        }

                        continue;
                    }

                    $new = new CCourseSetting();
                    $new
                        ->setCId($cId)
                        ->setVariable($var)
                        ->setTitle($var)
                        ->setCategory($category)
                        ->setValue($value)
                    ;

                    $this->manager->persist($new);
                }
            }

            $this->manager->flush();
            $this->manager->clear();
        }
    }
}
