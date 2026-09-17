<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Service\VueTranslationsBuilder;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Serves the vue-i18n messages a locale defines on its own.
 *
 * Only a sublanguage needs this. The languages shipped with the code have a file in
 * assets/locales/, which webpack enumerates at build time and hands the browser as its
 * own chunk. A sublanguage is created after that build — by the 1.11.x upgrade, or by an
 * administrator in Administration > Languages — so nothing would reach the browser
 * without a rebuild, which a production server cannot be asked to run.
 *
 * The terms come from var/translations/messages.<iso>.po, which both the upgrade and the
 * administration page write, and which the Symfony translator already reads at runtime.
 * That file is the single source of truth; this endpoint only reshapes it for vue-i18n.
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
     * The iso comes from the URL, so it is never used to build a path. It selects a row
     * first, and the path below is composed from the stored isocode.
     */
    #[Route('/locales/{iso}.json', name: 'chamilo_locale_messages', methods: ['GET'])]
    public function messages(string $iso): JsonResponse
    {
        $language = $this->languageRepository->findOneBy(['isocode' => $iso, 'available' => true]);

        if (!$language instanceof Language) {
            return new JsonResponse(['error' => 'Unknown locale'], JsonResponse::HTTP_NOT_FOUND);
        }

        $storedIso = $language->getIsocode();

        // An administrator editing a term rewrites the .po file, so its modification time
        // is the cache version. No invalidation hook is needed, and none can be forgotten.
        $poFile = $this->projectDir.'/var/translations/messages.'.$storedIso.'.po';
        $version = is_file($poFile) ? (string) filemtime($poFile) : '0';

        $messages = $this->cache->get(
            'vue_locale.'.rawurlencode($storedIso).'.'.$version,
            function (ItemInterface $item) use ($language): array {
                $item->expiresAfter(3600);

                return $this->builder->buildOverrides($language);
            }
        );

        $response = new JsonResponse($messages);
        // Private: the answer is the same for everyone, but a shared cache keyed by URL
        // would outlive an administrator's edit, which the .po time above already handles.
        $response->setPrivate();
        $response->setMaxAge(0);

        return $response;
    }
}
