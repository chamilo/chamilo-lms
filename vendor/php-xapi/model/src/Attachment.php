<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Model;

/**
 * An Experience API statement {@link https://github.com/adlnet/xAPI-Spec/blob/master/xAPI-Data.md#attachments attachment}.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class Attachment
{
    private $usageType;
    private $contentType;
    private $length;
    private $sha2;
    private $display;
    private $description;
    private $fileUrl;
    private $content;

    /**
     * @param IRI              $usageType   The type of usage of this attachment
     * @param string           $contentType The content type of the attachment
     * @param int              $length      The length of the attachment data in octets
     * @param string           $sha2        The SHA-2 hash of the attachment data
     * @param LanguageMap      $display     Localized display name (title)
     * @param LanguageMap|null $description Localized description
     * @param IRL|null         $fileUrl     An IRL at which the attachment data can be retrieved
     * @param string|null      $content     The raw attachment content, please note that the content is not validated against
     *                                      the given SHA-2 hash
     */
    public function __construct(IRI $usageType, string $contentType, int $length, string $sha2, LanguageMap $display, LanguageMap $description = null, IRL $fileUrl = null, string $content = null)
    {
        if (null === $fileUrl && null === $content) {
            throw new \InvalidArgumentException('An attachment cannot be created without a file URL or raw content data.');
        }

        $this->usageType = $usageType;
        $this->contentType = $contentType;
        $this->length = $length;
        $this->sha2 = $sha2;
        $this->display = $display;
        $this->description = $description;
        $this->fileUrl = $fileUrl;
        $this->content = $content;
    }

    public function getUsageType(): IRI
    {
        return $this->usageType;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function getSha2(): string
    {
        return $this->sha2;
    }

    public function getDisplay(): LanguageMap
    {
        return $this->display;
    }

    public function getDescription(): ?LanguageMap
    {
        return $this->description;
    }

    public function getFileUrl(): ?IRL
    {
        return $this->fileUrl;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function equals(Attachment $attachment): bool
    {
        if (!$this->usageType->equals($attachment->usageType)) {
            return false;
        }

        if ($this->contentType !== $attachment->contentType) {
            return false;
        }

        if ($this->length !== $attachment->length) {
            return false;
        }

        if ($this->sha2 !== $attachment->sha2) {
            return false;
        }

        if (!$this->display->equals($attachment->display)) {
            return false;
        }

        if (null !== $this->description xor null !== $attachment->description) {
            return false;
        }

        if (null !== $this->description && null !== $attachment->description && !$this->description->equals($attachment->description)) {
            return false;
        }

        if (null !== $this->fileUrl xor null !== $attachment->fileUrl) {
            return false;
        }

        if (null !== $this->fileUrl && null !== $attachment->fileUrl && !$this->fileUrl->equals($attachment->fileUrl)) {
            return false;
        }

        return true;
    }
}
