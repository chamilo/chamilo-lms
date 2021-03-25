<?php

/* For license terms, see /license.txt */

namespace Chamilo\PluginBundle\TopLinks\Form;

use Chamilo\PluginBundle\Entity\TopLinks\TopLink;
use FormValidator;
use Security;

/**
 * Class LinkForm.
 *
 * @package Chamilo\PluginBundle\TopLinks\Form
 */
class LinkForm extends FormValidator
{
    /**
     * @var TopLink
     */
    private $link;

    /**
     * LinkForm constructor.
     *
     * @param \Chamilo\PluginBundle\Entity\TopLinks\TopLink|null $link
     */
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

        $action = api_get_self().'?'.http_build_query($actionParams);

        parent::__construct('frm_link', 'post', $action, '');
    }

    public function validate(): bool
    {
        return parent::validate() && Security::check_token('get');
    }

    public function exportValues($elementList = null)
    {
        Security::clear_token();

        return parent::exportValues($elementList);
    }

    public function createElements()
    {
        $this->addText('title', get_lang('LinkName'));
        $this->addUrl('url', 'URL');
        $this->addRule('url', get_lang('GiveURL'), 'url');
        $this->addSelect(
            'target',
            [
                get_lang('LinkTarget'),
                get_lang('AddTargetOfLinkOnHomepage'),
            ],
            [
                '_blank' => get_lang('LinkOpenBlank'),
                '_self' => get_lang('LinkOpenSelf'),
            ]
        );
        $this->addButtonSave(get_lang('SaveLink'), 'submitLink');
    }

    public function setDefaults($defaultValues = null, $filter = null)
    {
        $defaults = [];

        if ($this->link) {
            $defaults['title'] = $this->link->getTitle();
            $defaults['url'] = $this->link->getUrl();
            $defaults['target'] = $this->link->getTarget();
        }

        parent::setDefaults($defaults, null);
    }
}
