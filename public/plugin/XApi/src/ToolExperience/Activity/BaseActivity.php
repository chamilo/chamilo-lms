<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Activity;

use Chamilo\CoreBundle\Entity\User;

/**
 * Class BaseActivity.
 */
abstract class BaseActivity
{
    protected User $user;

    /**
     * @var \Chamilo\CoreBundle\Entity\Course|null
     */
    protected $course;

    /**
     * @var \Chamilo\CoreBundle\Entity\Session|null
     */
    protected $session;

    /**
     * Build a plain xAPI activity payload.
     */
    abstract public function generate(): array;

    protected function generateIri(string $path, string $resource, array $params = []): string
    {
        $cidReq = api_get_cidreq();
        $url = api_get_path($path).ltrim($resource, '/');

        if (!empty($params)) {
            $url .= '?'.http_build_query($params, '', '&', \PHP_QUERY_RFC3986);

            if (!empty($cidReq)) {
                $url .= '&'.$cidReq;
            }

            return $url;
        }

        if (!empty($cidReq)) {
            $url .= '?'.$cidReq;
        }

        return $url;
    }

    protected function buildActivity(
        string $id,
        ?string $name = null,
        ?string $description = null,
        ?string $typeIri = null,
        array $extensions = []
    ): array {
        $activity = [
            'objectType' => 'Activity',
            'id' => trim($id),
        ];

        $definition = $this->buildDefinition($name, $description, $typeIri, $extensions);

        if (!empty($definition)) {
            $activity['definition'] = $definition;
        }

        return $activity;
    }

    protected function buildDefinition(
        ?string $name = null,
        ?string $description = null,
        ?string $typeIri = null,
        array $extensions = []
    ): array {
        $definition = [];

        $nameMap = $this->buildLanguageMap($name);
        if (!empty($nameMap)) {
            $definition['name'] = $nameMap;
        }

        $descriptionMap = $this->buildLanguageMap($description);
        if (!empty($descriptionMap)) {
            $definition['description'] = $descriptionMap;
        }

        if (!empty($typeIri)) {
            $definition['type'] = trim($typeIri);
        }

        if (!empty($extensions)) {
            $definition['extensions'] = $extensions;
        }

        return $definition;
    }

    protected function buildLanguageMap(?string $value, ?string $languageIso = null): array
    {
        $value = trim((string) $value);

        if ('' === $value) {
            return [];
        }

        return [
            $this->resolveLanguageIso($languageIso) => $value,
        ];
    }

    protected function resolveLanguageIso(?string $language = null): string
    {
        if (!empty($language)) {
            return api_get_language_isocode($language);
        }

        $languageSource = function_exists('api_get_interface_language')
            ? api_get_interface_language()
            : api_get_setting('platformLanguage');

        if (!empty($languageSource)) {
            return api_get_language_isocode($languageSource);
        }

        return 'en';
    }
}
