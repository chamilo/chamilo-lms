<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Chamilo\PluginBundle\Entity\XApi\Lrs;

use Doctrine\ORM\Mapping as ORM;
use Xabbuh\XApi\Model\Attachment as AttachmentModel;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\IRL;
use Xabbuh\XApi\Model\LanguageMap;

/**
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * @ORM\Table(name="xapi_attachment")
 * @ORM\Entity()
 */
class Attachment
{
    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    public $identifier;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\PluginBundle\Entity\XApi\Lrs\Statement", inversedBy="attachments")
     */
    public $statement;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    public $usageType;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    public $contentType;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    public $length;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    public $sha2;

    /**
     * @var array
     *
     * @ORM\Column(type="json")
     */
    public $display;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    public $hasDescription;

    /**
     * @var array|null
     *
     * @ORM\Column(type="json", nullable=true)
     */
    public $description;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    public $fileUrl;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    public $content;

    /**
     * @param \Xabbuh\XApi\Model\Attachment $model
     *
     * @return \Chamilo\PluginBundle\Entity\XApi\Lrs\Attachment
     */
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

    /**
     * @return \Xabbuh\XApi\Model\Attachment
     */
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
