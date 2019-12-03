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

    /**
     * @return bool
     */
    public function isAllowNodeCreation(): bool
    {
        return $this->allowNodeCreation;
    }

    /**
     * @param bool $allowNodeCreation
     *
     * @return ResourceSettings
     */
    public function setAllowNodeCreation(bool $allowNodeCreation): ResourceSettings
    {
        $this->allowNodeCreation = $allowNodeCreation;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAllowResourceCreation(): bool
    {
        return $this->allowResourceCreation;
    }

    /**
     * @param bool $allowResourceCreation
     *
     * @return ResourceSettings
     */
    public function setAllowResourceCreation(bool $allowResourceCreation): ResourceSettings
    {
        $this->allowResourceCreation = $allowResourceCreation;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAllowResourceUpload(): bool
    {
        return $this->allowResourceUpload;
    }

    /**
     * @param bool $allowResourceUpload
     *
     * @return ResourceSettings
     */
    public function setAllowResourceUpload(bool $allowResourceUpload): ResourceSettings
    {
        $this->allowResourceUpload = $allowResourceUpload;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAllowResourceEdit(): bool
    {
        return $this->allowResourceEdit;
    }

    /**
     * @param bool $allowResourceEdit
     *
     * @return ResourceSettings
     */
    public function setAllowResourceEdit(bool $allowResourceEdit): ResourceSettings
    {
        $this->allowResourceEdit = $allowResourceEdit;

        return $this;
    }
}
