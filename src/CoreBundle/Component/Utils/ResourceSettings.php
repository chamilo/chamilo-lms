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

    public function __construct()
    {
        $this->allowNodeCreation = true;
        $this->allowResourceCreation = true;
        $this->allowResourceUpload = true;
        $this->allowResourceEdit = true;
    }

    public function isAllowNodeCreation(): bool
    {
        return $this->allowNodeCreation;
    }

    public function setAllowNodeCreation(bool $allowNodeCreation): ResourceSettings
    {
        $this->allowNodeCreation = $allowNodeCreation;

        return $this;
    }

    public function isAllowResourceCreation(): bool
    {
        return $this->allowResourceCreation;
    }

    public function setAllowResourceCreation(bool $allowResourceCreation): ResourceSettings
    {
        $this->allowResourceCreation = $allowResourceCreation;

        return $this;
    }

    public function isAllowResourceUpload(): bool
    {
        return $this->allowResourceUpload;
    }

    public function setAllowResourceUpload(bool $allowResourceUpload): ResourceSettings
    {
        $this->allowResourceUpload = $allowResourceUpload;

        return $this;
    }

    public function isAllowResourceEdit(): bool
    {
        return $this->allowResourceEdit;
    }

    public function setAllowResourceEdit(bool $allowResourceEdit): ResourceSettings
    {
        $this->allowResourceEdit = $allowResourceEdit;

        return $this;
    }
}
