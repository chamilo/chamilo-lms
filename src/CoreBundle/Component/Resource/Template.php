<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Resource;

class Template
{
    protected $index;
    protected $list;
    protected $edit;
    protected $viewResource;
    protected $new;
    protected $newFolder;
    protected $diskSpace;
    protected $info;
    protected $preview;
    protected $upload;

    public function __construct()
    {
        $this->index = '@ChamiloTheme/Resource/index.html.twig';
        $this->list = '@ChamiloTheme/Resource/index.html.twig';
        $this->edit = '@ChamiloTheme/Resource/edit.html.twig';
        // New resource
        $this->new = '@ChamiloTheme/Resource/new.html.twig';
        // New resource node (new folder)
        $this->newFolder = '@ChamiloTheme/Resource/new_folder.html.twig';
        $this->viewResource = '@ChamiloTheme/Resource/view_resource.html.twig';
        $this->diskSpace = '@ChamiloTheme/Resource/disk_space.html.twig';
        $this->info = '@ChamiloTheme/Resource/info.html.twig';
        $this->preview = '@ChamiloTheme/Resource/preview.html.twig';
        $this->upload = '@ChamiloTheme/Resource/upload.html.twig';
    }

    public function getFromAction(string $action)
    {
        $action = str_replace('Action', '', $action);

        if (property_exists($this, $action)) {
            return $this->$action;
        }

        throw new \InvalidArgumentException("No template found for action: $action");
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
