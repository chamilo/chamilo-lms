<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Ai;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\State\Ai\TermsAndConditionsTranslationProcessor;
use Chamilo\CoreBundle\State\Ai\TermsAndConditionsTranslationProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/terms_and_conditions_translation',
            security: "is_granted('ROLE_ADMIN')",
            name: 'get_terms_and_conditions_translation_configuration',
            provider: TermsAndConditionsTranslationProvider::class,
        ),
        new Post(
            uriTemplate: '/terms_and_conditions_translation',
            security: "is_granted('ROLE_ADMIN')",
            read: false,
            name: 'create_terms_and_conditions_translation',
            processor: TermsAndConditionsTranslationProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['terms_translation:read']],
    denormalizationContext: ['groups' => ['terms_translation:write']],
)]
final class TermsAndConditionsTranslation
{
    #[Groups(['terms_translation:read'])]
    public bool $enabled = false;

    /**
     * @var array<int, array{label: string, value: int, isocode: string, latestVersion: int|null}>
     */
    #[Groups(['terms_translation:read'])]
    public array $languages = [];

    /**
     * @var array<int, array{label: string, value: string}>
     */
    #[Groups(['terms_translation:read'])]
    public array $providers = [];

    #[Groups(['terms_translation:read', 'terms_translation:write'])]
    public string $csrfToken = '';

    #[Groups(['terms_translation:write'])]
    public int $sourceLanguageId = 0;

    #[Groups(['terms_translation:read', 'terms_translation:write'])]
    public int $targetLanguageId = 0;

    #[Groups(['terms_translation:write'])]
    public string $provider = '';

    /**
     * @var array<int, string>
     */
    #[Groups(['terms_translation:read', 'terms_translation:write'])]
    public array $sections = [];
}
