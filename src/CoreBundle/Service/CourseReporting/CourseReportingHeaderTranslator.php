<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\CourseReporting;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Uses Symfony translations first, then the shipped Vue dictionaries when no
 * server-side catalogue contains the requested term.
 */
final class CourseReportingHeaderTranslator
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $messages = [];

    public function __construct(
        private readonly TranslatorInterface $translator,
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
    ) {}

    public function translate(string $label, string $locale): string
    {
        $locale = str_replace('-', '_', trim($locale));
        if (1 !== preg_match('/^[a-z]{2,3}(?:_[A-Za-z0-9]{2,8})*$/i', $locale)) {
            return $this->translator->trans($label);
        }

        $translated = $this->translator->trans($label, [], null, $locale);
        if ($translated !== $label) {
            return $translated;
        }

        $messages = $this->getMessages($locale);
        $value = $messages[$label] ?? null;

        return \is_string($value) && '' !== $value ? $value : $label;
    }

    /**
     * @return array<string, mixed>
     */
    private function getMessages(string $locale): array
    {
        if (isset($this->messages[$locale])) {
            return $this->messages[$locale];
        }

        $file = $this->projectDir.'/assets/locales/'.$locale.'.json';
        if (!is_file($file)) {
            return $this->messages[$locale] = [];
        }

        $contents = file_get_contents($file);
        if (false === $contents) {
            return $this->messages[$locale] = [];
        }

        $decoded = json_decode($contents, true);

        return $this->messages[$locale] = \is_array($decoded) ? $decoded : [];
    }
}
