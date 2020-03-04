<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Utils;

class ResourceSettings
{
    /** @var bool */
    public $allowNodeCreation;
    /** @var bool */
    public $allowResourceCreation;
    /** @var bool */
    public $allowResourceUpload;
    /** @var bool */
    public $allowResourceEdit;
    public $allowDownloadAll;
    public $allowDiskSpace;
    public $allowToSaveEditorToResourceFile;

    public function __construct()
    {
        $this->allowNodeCreation = true;
        $this->allowResourceCreation = true;
        $this->allowResourceUpload = true;
        $this->allowResourceEdit = true;
        $this->allowDownloadAll = false;
        $this->allowDiskSpace = false;
        $this->allowToSaveEditorToResourceFile = false;
    }

    public function isAllowNodeCreation(): bool
    {
        return $this->allowNodeCreation;
    }

    public function setAllowNodeCreation(bool $allowNodeCreation): self
    {
        $this->allowNodeCreation = $allowNodeCreation;

        return $this;
    }

    public function isAllowResourceCreation(): bool
    {
        return $this->allowResourceCreation;
    }

    public function setAllowResourceCreation(bool $allowResourceCreation): self
    {
        $this->allowResourceCreation = $allowResourceCreation;

        return $this;
    }

    public function isAllowResourceUpload(): bool
    {
        return $this->allowResourceUpload;
    }

    public function setAllowResourceUpload(bool $allowResourceUpload): self
    {
        $this->allowResourceUpload = $allowResourceUpload;

        return $this;
    }

    public function isAllowResourceEdit(): bool
    {
        return $this->allowResourceEdit;
    }

    public function setAllowResourceEdit(bool $allowResourceEdit): self
    {
        $this->allowResourceEdit = $allowResourceEdit;

        return $this;
    }

    public function isAllowDownloadAll(): bool
    {
        return $this->allowDownloadAll;
    }

    public function setAllowDownloadAll(bool $allowDownloadAll): self
    {
        $this->allowDownloadAll = $allowDownloadAll;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAllowDiskSpace(): bool
    {
        return $this->allowDiskSpace;
    }

    /**
     * @param bool $allowDiskSpace
     *
     * @return ResourceSettings
     */
    public function setAllowDiskSpace(bool $allowDiskSpace): ResourceSettings
    {
        $this->allowDiskSpace = $allowDiskSpace;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAllowToSaveEditorToResourceFile(): bool
    {
        return $this->allowToSaveEditorToResourceFile;
    }

    /**
     * @param bool $allowToSaveEditorToResourceFile
     *
     * @return ResourceSettings
     */
    public function setAllowToSaveEditorToResourceFile(bool $allowToSaveEditorToResourceFile): ResourceSettings
    {
        $this->allowToSaveEditorToResourceFile = $allowToSaveEditorToResourceFile;

        return $this;
    }


}
