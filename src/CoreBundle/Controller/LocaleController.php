<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Translation\VueTranslationsBuilder;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

use const JSON_FORCE_OBJECT;

/**
 * Serves the vue-i18n messages a locale defines on its own.
 *
 * Only a sublanguage needs it: webpack enumerates assets/locales/ at build time, so a
 * language created afterwards would need a rebuild to reach the browser. The terms come
 * from var/translations/messages.<iso>.po, which the upgrade and the administration page
 * both write.
 */
class LocaleController extends BaseController
{
    public function __construct(
        private readonly LanguageRepository $languageRepository,
        private readonly VueTranslationsBuilder $builder,
        #[Autowire(service: 'cache.app')]
        private readonly CacheInterface $cache,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {}

    /**
     * The iso from the URL selects a row first; the path below uses the stored isocode.
     */
    #[Route('/locales/{iso}.json', name: 'chamilo_locale_messages', methods: ['GET'])]
    public function messages(string $iso): JsonResponse
    {
        $language = $this->languageRepository->findOneBy(['isocode' => $iso, 'available' => true]);

        if (!$language instanceof Language) {
            return new JsonResponse(['error' => 'Unknown locale'], JsonResponse::HTTP_NOT_FOUND);
        }

        $storedIso = $language->getIsocode();

        // An edit rewrites the .po, so its modification time is the cache version.
        $poFile = $this->projectDir.'/var/translations/messages.'.$storedIso.'.po';
        $version = is_file($poFile) ? (string) filemtime($poFile) : '0';

        $messages = $this->cache->get(
            'vue_locale.'.rawurlencode($storedIso).'.'.$version,
            function (ItemInterface $item) use ($language): array {
                $item->expiresAfter(3600);

                return $this->builder->buildOverrides($language);
            }
        );

        // FORCE_OBJECT: a locale that overrides nothing must answer {}, not [].
        $response = JsonResponse::fromJsonString(json_encode($messages, JSON_FORCE_OBJECT));
        $response->setPrivate();
        $response->setMaxAge(0);

        return $response;
    }
}
