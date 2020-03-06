<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Resource;

class Settings
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
    // Shows an extra ckeditor input to save the HTML content into a ResourceFile.
    public $allowToSaveEditorToResourceFile;
    public $templates;

    public function __construct()
    {
        $this->allowNodeCreation = false;
        $this->allowResourceCreation = false;
        $this->allowResourceUpload = false;
        $this->allowResourceEdit = false;
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

    public function isAllowDiskSpace(): bool
    {
        return $this->allowDiskSpace;
    }

    public function setAllowDiskSpace(bool $allowDiskSpace): self
    {
        $this->allowDiskSpace = $allowDiskSpace;

        return $this;
    }

    public function isAllowToSaveEditorToResourceFile(): bool
    {
        return $this->allowToSaveEditorToResourceFile;
    }

    public function setAllowToSaveEditorToResourceFile(bool $allowToSaveEditorToResourceFile): self
    {
        $this->allowToSaveEditorToResourceFile = $allowToSaveEditorToResourceFile;

        return $this;
    }
}
