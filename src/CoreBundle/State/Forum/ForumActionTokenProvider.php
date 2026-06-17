<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State\Forum;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Forum\ForumActionToken;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProviderInterface<ForumActionToken>
 */
final readonly class ForumActionTokenProvider implements ProviderInterface
{
    private const FORUM_ACTION_TOKEN_INTENTION = 'forum_action';

    public function __construct(
        private CsrfTokenManagerInterface $csrfTokenManager,
        private SettingsManager $settingsManager,
        private LanguageRepository $languageRepository,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ForumActionToken
    {
        $actionToken = new ForumActionToken();
        $actionToken->token = $this->csrfTokenManager->getToken(self::FORUM_ACTION_TOKEN_INTENTION)->getValue();
        $actionToken->settings = [
            'defaultForumView' => $this->getDefaultForumView(),
            'forumFoldCategories' => $this->isTruthySetting(
                $this->settingsManager->getSetting('forum.forum_fold_categories', true),
            ),
            'allowForumPostRevisions' => $this->isTruthySetting(
                $this->settingsManager->getSetting('forum.allow_forum_post_revisions', true),
            ),
            'hideForumPostRevisionLanguage' => $this->isTruthySetting(
                $this->settingsManager->getSetting('forum.hide_forum_post_revision_language', true),
            ),
            'categoryLanguageFilter' => $this->getCategoryLanguageFilterSettings(),
        ];

        return $actionToken;
    }

    private function getDefaultForumView(): string
    {
        $defaultView = (string) ($this->settingsManager->getSetting('forum.default_forum_view', true) ?? 'flat');

        return \in_array($defaultView, ['flat', 'threaded', 'nested'], true) ? $defaultView : 'flat';
    }

    /**
     * @return array{enabled: bool, options: array<int, array{label: string, value: string}>}
     */
    private function getCategoryLanguageFilterSettings(): array
    {
        $enabled = $this->isTruthySetting(
            $this->settingsManager->getSetting('forum.allow_forum_category_language_filter', true),
        );
        $options = $enabled ? $this->getPlatformLanguageOptions() : [];

        return [
            'enabled' => $enabled && [] !== $options,
            'options' => $options,
        ];
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function getPlatformLanguageOptions(): array
    {
        $options = [];

        foreach ($this->languageRepository->getAllAvailableToArray(true, true) as $isoCode => $languageName) {
            $isoCode = trim((string) $isoCode);
            $languageName = trim((string) $languageName);

            if ('' === $isoCode) {
                continue;
            }

            $options[] = [
                'label' => '' !== $languageName ? $languageName : $isoCode,
                'value' => $isoCode,
            ];
        }

        return $options;
    }

    private function isTruthySetting(mixed $value): bool
    {
        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
