<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Resource;

use InvalidArgumentException;

class Template
{
    protected string $index;

    protected string $list;

    protected string $edit;

    protected string $viewResource;

    protected string $new;

    protected string $newFolder;

    protected string $diskSpace;

    protected string $info;

    protected string $infoAjax;

    protected string $preview;

    protected string $upload;

    public function __construct()
    {
        $this->index = '@ChamiloCore/Resource/index.html.twig';
        $this->list = '@ChamiloCore/Resource/index.html.twig';
        $this->edit = '@ChamiloCore/Resource/edit.html.twig';
        // New resource
        $this->new = '@ChamiloCore/Resource/new.html.twig';
        // New resource node (new folder)
        $this->newFolder = '@ChamiloCore/Resource/new_folder.html.twig';
        $this->viewResource = '@ChamiloCore/Resource/view_resource.html.twig';
        $this->diskSpace = '@ChamiloCore/Resource/disk_space.html.twig';
        $this->info = '@ChamiloCore/Resource/info.html.twig';
        $this->infoAjax = '@ChamiloCore/Resource/info_ajax.html.twig';
        $this->preview = '@ChamiloCore/Resource/preview.html.twig';
        $this->upload = '@ChamiloCore/Resource/upload.html.twig';
    }

    public function getFromAction(string $action, $isAjax = false)
    {
        $action = str_replace('Action', '', $action);
        if ($isAjax) {
            $action .= 'Ajax';
        }

        if (property_exists($this, $action)) {
            return $this->{$action};
        }

        throw new InvalidArgumentException(sprintf('No template found for action: %s', $action));
    }

    public function setIndex(string $index): self
    {
        $this->index = $index;

        return $this;
    }

    public function setList(string $list): self
    {
        $this->list = $list;

        return $this;
    }

    public function setEdit(string $edit): self
    {
        $this->edit = $edit;

        return $this;
    }

    public function setViewResource(string $viewResource): self
    {
        $this->viewResource = $viewResource;

        return $this;
    }

    public function setNew(string $new): self
    {
        $this->new = $new;

        return $this;
    }

    public function setNewFolder(string $newFolder): self
    {
        $this->newFolder = $newFolder;

        return $this;
    }

    public function setDiskSpace(string $diskSpace): self
    {
        $this->diskSpace = $diskSpace;

        return $this;
    }

    public function setInfo(string $info): self
    {
        $this->info = $info;

        return $this;
    }

    public function setPreview(string $preview): self
    {
        $this->preview = $preview;

        return $this;
    }

    public function setUpload(string $upload): self
    {
        $this->upload = $upload;

        return $this;
    }
}
