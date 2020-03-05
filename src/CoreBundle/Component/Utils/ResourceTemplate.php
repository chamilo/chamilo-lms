<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Utils;

class ResourceTemplate
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

    /**
     * @param string $index
     *
     * @return ResourceTemplate
     */
    public function setIndex(string $index): ResourceTemplate
    {
        $this->index = $index;

        return $this;
    }

    /**
     * @param string $list
     *
     * @return ResourceTemplate
     */
    public function setList(string $list): ResourceTemplate
    {
        $this->list = $list;

        return $this;
    }

    /**
     * @param string $edit
     *
     * @return ResourceTemplate
     */
    public function setEdit(string $edit): ResourceTemplate
    {
        $this->edit = $edit;

        return $this;
    }

    /**
     * @param string $viewResource
     *
     * @return ResourceTemplate
     */
    public function setViewResource(string $viewResource): ResourceTemplate
    {
        $this->viewResource = $viewResource;

        return $this;
    }

    /**
     * @param string $new
     *
     * @return ResourceTemplate
     */
    public function setNew(string $new): ResourceTemplate
    {
        $this->new = $new;

        return $this;
    }

    /**
     * @param string $newFolder
     *
     * @return ResourceTemplate
     */
    public function setNewFolder(string $newFolder): ResourceTemplate
    {
        $this->newFolder = $newFolder;

        return $this;
    }

    /**
     * @param string $diskSpace
     *
     * @return ResourceTemplate
     */
    public function setDiskSpace(string $diskSpace): ResourceTemplate
    {
        $this->diskSpace = $diskSpace;

        return $this;
    }

    /**
     * @param string $info
     *
     * @return ResourceTemplate
     */
    public function setInfo(string $info): ResourceTemplate
    {
        $this->info = $info;

        return $this;
    }

    /**
     * @param string $preview
     *
     * @return ResourceTemplate
     */
    public function setPreview(string $preview): ResourceTemplate
    {
        $this->preview = $preview;

        return $this;
    }

    /**
     * @param string $upload
     *
     * @return ResourceTemplate
     */
    public function setUpload(string $upload): ResourceTemplate
    {
        $this->upload = $upload;

        return $this;
    }
}
