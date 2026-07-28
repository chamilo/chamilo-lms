<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Ai;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\State\Ai\WysiwygTranslationProcessor;
use Chamilo\CoreBundle\State\Ai\WysiwygTranslationProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/wysiwyg_translation',
            security: "is_granted('ROLE_USER')",
            name: 'get_wysiwyg_translation_configuration',
            provider: WysiwygTranslationProvider::class,
        ),
        new Post(
            uriTemplate: '/wysiwyg_translation',
            security: "is_granted('ROLE_USER')",
            read: false,
            name: 'create_wysiwyg_translation',
            processor: WysiwygTranslationProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['wysiwyg_translation:read']],
    denormalizationContext: ['groups' => ['wysiwyg_translation:write']],
)]
final class WysiwygTranslation
{
    #[Groups(['wysiwyg_translation:read'])]
    public bool $enabled = false;

    #[Groups(['wysiwyg_translation:read'])]
    public string $sourceLanguage = '';

    /**
     * @var array<int, array{label: string, value: string}>
     */
    #[Groups(['wysiwyg_translation:read'])]
    public array $languages = [];

    /**
     * @var array<int, array{label: string, value: string}>
     */
    #[Groups(['wysiwyg_translation:read'])]
    public array $providers = [];

    #[Groups(['wysiwyg_translation:read'])]
    public bool $allowAllLanguages = false;

    #[Groups(['wysiwyg_translation:read', 'wysiwyg_translation:write'])]
    public string $csrfToken = '';

    #[Groups(['wysiwyg_translation:read', 'wysiwyg_translation:write'])]
    public string $html = '';

    /**
     * @var array<string>
     */
    #[Groups(['wysiwyg_translation:write'])]
    public array $targetLanguages = [];

    #[Groups(['wysiwyg_translation:write'])]
    public string $provider = '';

    /**
     * @var array<string>
     */
    #[Groups(['wysiwyg_translation:read'])]
    public array $addedLanguages = [];

    /**
     * @var array<string>
     */
    #[Groups(['wysiwyg_translation:read'])]
    public array $skippedLanguages = [];
}
