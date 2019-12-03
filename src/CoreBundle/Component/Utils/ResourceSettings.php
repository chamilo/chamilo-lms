<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Utils;

class ResourceSettings
{
    /** @var bool */
    public $allowNodeFolderCreation;
    /** @var bool */
    public $allowResourceContentCreation;
    /** @var bool */
    public $allowResourceUploadCreation;
    /** @var bool */
    public $allowEditResource;

    public function __construct()
    {
        $this->allowNodeFolderCreation = true;
        $this->allowResourceContentCreation = true;
        $this->allowResourceUploadCreation = true;
        $this->allowEditResource = true;
    }

    /**
     * @return bool
     */
    public function isAllowNodeFolderCreation(): bool
    {
        return $this->allowNodeFolderCreation;
    }

    /**
     * @param bool $allowNodeFolderCreation
     *
     * @return ResourceSettings
     */
    public function setAllowNodeFolderCreation(bool $allowNodeFolderCreation): ResourceSettings
    {
        $this->allowNodeFolderCreation = $allowNodeFolderCreation;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAllowResourceContentCreation(): bool
    {
        return $this->allowResourceContentCreation;
    }

    /**
     * @param bool $allowResourceContentCreation
     *
     * @return ResourceSettings
     */
    public function setAllowResourceContentCreation(bool $allowResourceContentCreation): ResourceSettings
    {
        $this->allowResourceContentCreation = $allowResourceContentCreation;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAllowResourceUploadCreation(): bool
    {
        return $this->allowResourceUploadCreation;
    }

    /**
     * @param bool $allowResourceUploadCreation
     *
     * @return ResourceSettings
     */
    public function setAllowResourceUploadCreation(bool $allowResourceUploadCreation): ResourceSettings
    {
        $this->allowResourceUploadCreation = $allowResourceUploadCreation;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAllowEditResource(): bool
    {
        return $this->allowEditResource;
    }

    /**
     * @param bool $allowEditResource
     *
     * @return ResourceSettings
     */
    public function setAllowEditResource(bool $allowEditResource): ResourceSettings
    {
        $this->allowEditResource = $allowEditResource;

        return $this;
    }
}
