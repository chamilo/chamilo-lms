<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Serializer;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Encoder\DecoderInterface;

use const JSON_ERROR_NONE;

#[AutoconfigureTag('serializer.encoder')]
final readonly class ForumMultipartDecoder implements DecoderInterface
{
    public const FORMAT = 'multipart';

    public function __construct(
        private RequestStack $requestStack,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function decode(string $data, string $format, array $context = []): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return [];
        }

        return array_merge(
            $this->decodeValues($request->request->all()),
            $request->files->all(),
        );
    }

    public function supportsDecoding(string $format): bool
    {
        return self::FORMAT === $format;
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private function decodeValues(array $values): array
    {
        foreach ($values as $key => $value) {
            $values[$key] = $this->decodeValue($value);
        }

        return $values;
    }

    private function decodeValue(mixed $value): mixed
    {
        if (\is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->decodeValue($item), $value);
        }

        if (!\is_string($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);
        if (JSON_ERROR_NONE === json_last_error()) {
            return $decoded;
        }

        return $value;
    }
}
