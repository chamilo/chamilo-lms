<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XApi\Repository\Doctrine\Mapping;

use Xabbuh\XApi\Model\Attachment as AttachmentModel;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\IRL;
use Xabbuh\XApi\Model\LanguageMap;

/**
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
class Attachment
{
    public $identifier;

    public $statement;

    /**
     * @var string
     */
    public $usageType;

    /**
     * @var string
     */
    public $contentType;

    /**
     * @var int
     */
    public $length;

    /**
     * @var string
     */
    public $sha2;

    /**
     * @var array
     */
    public $display;

    /**
     * @var bool
     */
    public $hasDescription;

    /**
     * @var array|null
     */
    public $description;

    /**
     * @var string|null
     */
    public $fileUrl;

    /**
     * @var string|null
     */
    public $content;

    public static function fromModel(AttachmentModel $model)
    {
        $attachment = new self();
        $attachment->usageType = $model->getUsageType()->getValue();
        $attachment->contentType = $model->getContentType();
        $attachment->length = $model->getLength();
        $attachment->sha2 = $model->getSha2();
        $attachment->display = array();

        if (null !== $model->getFileUrl()) {
            $attachment->fileUrl = $model->getFileUrl()->getValue();
        }

        $attachment->content = $model->getContent();

        $display = $model->getDisplay();

        foreach ($display->languageTags() as $languageTag) {
            $attachment->display[$languageTag] = $display[$languageTag];
        }

        if (null !== $description = $model->getDescription()) {
            $attachment->hasDescription = true;
            $attachment->description = array();

            foreach ($description->languageTags() as $languageTag) {
                $attachment->description[$languageTag] = $description[$languageTag];
            }
        } else {
            $attachment->hasDescription = false;
        }

        return $attachment;
    }

    public function getModel()
    {
        $description = null;
        $fileUrl = null;

        if ($this->hasDescription) {
            $description = LanguageMap::create($this->description);
        }

        if (null !== $this->fileUrl) {
            $fileUrl = IRL::fromString($this->fileUrl);
        }

        return new AttachmentModel(IRI::fromString($this->usageType), $this->contentType, $this->length, $this->sha2, LanguageMap::create($this->display), $description, $fileUrl, $this->content);
    }
}
