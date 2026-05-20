<?php

/* For license terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\PluginBundle\TopLinks\Form;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\PluginBundle\TopLinks\Entity\TopLink;
use FormValidator;
use Security;

class LinkForm extends FormValidator
{
    private ?TopLink $link;

    private array $fallbacks = [
        'LinkName' => 'Link name',
        'LinkTarget' => 'Target',
        'AddTargetOfLinkOnHomepage' => 'Choose how this link opens from the course tool.',
        'LinkOpenBlank' => 'Open in a new tab',
        'LinkOpenSelf' => 'Open in the same tab',
        'AddImage' => 'Image',
        'UpdateImage' => 'Update image',
        'Only PNG, JPG or GIF images allowed' => 'Only PNG, JPG or GIF images are allowed.',
        'AllowedTopLinkFormats' => 'Allowed formats: internal path starting with /, http:// or https://.',
        'SaveLink' => 'Save link',
    ];

    public function __construct(TopLink $link = null)
    {
        $this->link = $link;

        $actionParams = [
            'action' => 'add',
            'sec_token' => Security::get_existing_token(),
        ];

        if ($this->link) {
            $actionParams['action'] = 'edit';
            $actionParams['link'] = $this->link->getId();
        }

        parent::__construct('frm_link', 'post', api_get_self().'?'.http_build_query($actionParams), '');
    }

    public function validate(): bool
    {
        if (!parent::validate() || !Security::check_token('get')) {
            return false;
        }

        $url = (string) $this->exportValue('url');

        if (!$this->isValidUrl($url)) {
            $this->setElementError('url', get_lang('GiveURL'));

            return false;
        }

        return true;
    }

    public function exportValues($elementList = null)
    {
        Security::clear_token();

        return parent::exportValues($elementList);
    }

    public function createElements(): void
    {
        global $htmlHeadXtra;

        $htmlHeadXtra[] = api_get_css_asset('cropper/dist/cropper.min.css');
        $htmlHeadXtra[] = api_get_asset('cropper/dist/cropper.min.js');

        $this->addText(
            'title',
            $this->lang('LinkName'),
            false,
            [
                'class' => 'w-full rounded-lg border border-gray-25 px-3 py-2 text-body-2 text-gray-90 focus:border-primary focus:ring-primary',
            ]
        );
        $this->addRule('title', get_lang('ThisFieldIsRequired'), 'required');

        $this->addText(
            'url',
            'URL',
            false,
            [
                'class' => 'w-full rounded-lg border border-gray-25 px-3 py-2 text-body-2 text-gray-90 focus:border-primary focus:ring-primary',
                'placeholder' => '/search/ui or https://example.com',
            ]
        );
        $this->addRule('url', get_lang('ThisFieldIsRequired'), 'required');

        $this->addHtml(
            '<p class="mt-1 text-caption text-gray-50">'.
            htmlspecialchars($this->lang('AllowedTopLinkFormats'), ENT_QUOTES).
            '</p>'
        );

        $this->addSelect(
            'target',
            $this->lang('LinkTarget'),
            [
                '_blank' => $this->lang('LinkOpenBlank'),
                '_self' => $this->lang('LinkOpenSelf'),
            ],
            [
                'class' => 'rounded-lg border border-gray-25 px-3 py-2 text-body-2 text-gray-90 focus:border-primary focus:ring-primary',
            ]
        );
        $this->addHtml(
            '<p class="mt-1 text-caption text-gray-50">'.
            htmlspecialchars($this->lang('AddTargetOfLinkOnHomepage'), ENT_QUOTES).
            '</p>'
        );
        $this->addFile(
            'picture',
            $this->link ? $this->lang('UpdateImage') : $this->lang('AddImage'),
            [
                'id' => 'picture',
                'class' => 'picture-form',
                'crop_image' => true,
                'crop_ratio' => '1 / 1',
                'accept' => 'image/*',
            ]
        );
        $this->addHtml(
            '<p class="mt-1 text-caption text-gray-50">'.
            htmlspecialchars($this->lang('Only PNG, JPG or GIF images allowed'), ENT_QUOTES).
            '</p>'
        );
        $allowedPictureTypes = api_get_supported_image_extensions(false);
        $this->addRule(
            'picture',
            get_lang('OnlyImagesAllowed').' ('.implode(', ', $allowedPictureTypes).')',
            'filetype',
            $allowedPictureTypes
        );
        $this->addButtonSave($this->lang('SaveLink'), 'submitLink');
    }

    public function returnForm()
    {
        $defaults = [];

        if ($this->link) {
            $defaults['title'] = $this->link->getTitle();
            $defaults['url'] = $this->link->getUrl();
            $defaults['target'] = $this->link->getTarget();
        }

        $this->setDefaults($defaults);

        return parent::returnForm();
    }

    public function setLink(TopLink $link): LinkForm
    {
        $this->link = $link;

        return $this;
    }

    public function saveImage(): ?string
    {
        if (
            empty($_FILES['picture']['tmp_name'])
            || !is_uploaded_file((string) $_FILES['picture']['tmp_name'])
        ) {
            return $this->link ? $this->link->getIcon() : null;
        }

        $tmpName = (string) $_FILES['picture']['tmp_name'];
        $originalName = (string) $_FILES['picture']['name'];
        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExtensions = api_get_supported_image_extensions(false);

        if (!in_array($extension, $allowedExtensions, true) || false === getimagesize($tmpName)) {
            return $this->link ? $this->link->getIcon() : null;
        }

        $newFilename = md5((string) $this->link->getId()).'.'.$extension;
        $plugin = \TopLinksPlugin::create();
        $storagePath = $plugin->getIconStoragePath($newFilename);
        $pluginsFilesystem = Container::getPluginsFileSystem();

        if ($this->link && $this->link->getIcon() && $this->link->getIcon() !== $newFilename) {
            $plugin->deleteIcon($this->link->getIcon());
        }

        if ($pluginsFilesystem->fileExists($storagePath)) {
            $pluginsFilesystem->delete($storagePath);
        }

        $data = file_get_contents($tmpName);

        if (false === $data) {
            return $this->link ? $this->link->getIcon() : null;
        }

        $pluginsFilesystem->write($storagePath, $data);

        return $newFilename;
    }

    private function lang(string $key): string
    {
        $translated = \TopLinksPlugin::create()->get_lang($key);

        if ('' !== trim((string) $translated) && $translated !== $key) {
            return $translated;
        }

        $globalTranslation = get_lang($key);
        if ('' !== trim((string) $globalTranslation) && $globalTranslation !== $key) {
            return $globalTranslation;
        }

        return $this->fallbacks[$key] ?? $key;
    }

    private function isValidUrl(string $url): bool
    {
        $url = trim($url);

        if ('' === $url || str_contains($url, "\n") || str_contains($url, "\r")) {
            return false;
        }

        if (preg_match('/^javascript:/i', $url) || str_starts_with($url, '//')) {
            return false;
        }

        if (str_starts_with($url, '/')) {
            return true;
        }

        return (bool) filter_var($url, FILTER_VALIDATE_URL)
            && (str_starts_with($url, 'http://') || str_starts_with($url, 'https://'));
    }
}
