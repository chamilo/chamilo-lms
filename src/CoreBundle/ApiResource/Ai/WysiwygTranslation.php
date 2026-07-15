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
            name: 'get_wysiwyg_translation_configuration',
            provider: WysiwygTranslationProvider::class,
            security: "is_granted('ROLE_USER')",
        ),
        new Post(
            uriTemplate: '/wysiwyg_translation',
            read: false,
            name: 'create_wysiwyg_translation',
            processor: WysiwygTranslationProcessor::class,
            security: "is_granted('ROLE_USER')",
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
     * @var list<string>
     */
    #[Groups(['wysiwyg_translation:write'])]
    public array $targetLanguages = [];

    #[Groups(['wysiwyg_translation:write'])]
    public string $provider = '';

    /**
     * @var list<string>
     */
    #[Groups(['wysiwyg_translation:read'])]
    public array $addedLanguages = [];

    /**
     * @var list<string>
     */
    #[Groups(['wysiwyg_translation:read'])]
    public array $skippedLanguages = [];
}
