<?php

namespace Link;

use Chamilo;

/**
 * Edit/Create link form.
 * 
 * @license /licence.txt
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class LinkForm extends \FormValidator
{

    protected $link;

    function __construct($form_name = 'link', $method = 'post', $action = '', $target = '', $attributes = null, $track_submit = true)
    {
        parent::__construct($form_name, $method, $action, $target, $attributes, $track_submit);
    }

    /**
     *
     * @return \Link\LinkCategory
     */
    public function get_link()
    {
        return $this->link;
    }

    public function set_link($value)
    {
        $this->link = $value;
    }

    /**
     *
     * @param \Link\LinkCategory $link
     */
    function init($link = null)
    {
        $this->set_link($link);

        $defaults = array();
        $defaults['url'] = $link->url ? $link->url : 'http://';
        $defaults['title'] = $link->title;
        $defaults['description'] = $link->description;
        $defaults['category_id'] = $link->category_id;
        $defaults['display_order'] = $link->display_order;
        $defaults['on_homepage'] = $link->on_homepage;
        $defaults['target'] = $link->target;

        $this->add_hidden('c_id', $link->c_id);
        $this->add_hidden('id', $link->id);
        $this->add_hidden('session_id', $link->session_id);

        $form_name = $category->id ? get_lang('LinkMod') : get_lang('LinkAdd');
        $this->add_header($form_name);


        $this->add_textfield('url', get_lang('Url'), $required = true, array('class' => 'span6'));
        $this->addRule('url', get_lang('MalformedUrl'), 'regex', '|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i');

        $this->add_textfield('title', get_lang('Title'), $required = false, array('class' => 'span6'));

        $this->add_textarea('description', get_lang('Description'), array('class' => 'span3'));

        $this->add_checkbox('on_homepage', '', get_lang('OnHomepage'));

        $id = $link->id;
        if ($id) {
            $url = Chamilo::url('/main/metadata/index.php', array('eid' => "Link.$id"));
            $metadata = '<a class="control-text" href="' . $url . '">' . get_lang('AddMetadata') . '</a>';
            $this->add_label(get_lang('Metadata'), $metadata);
        }

        $options = array();
        $options[0] = '--';
        $categories = LinkCategoryRepository::instance()->find_by_course($link->c_id, $link->session_id);
        foreach ($categories as $category) {
            $options[$category->id] = $category->category_title;
        }
        $this->add_select('category_id', get_lang('Category'), $options);

        $targets = array(
            '_self' => get_lang('LinkOpenSelf'),
            '_blank' => get_lang('LinkOpenBlank'),
            '_parent' => get_lang('LinkOpenParent'),
            '_top' => get_lang('LinkOpenTop')
        );
        $this->add_select('target', get_lang('LinkTarget'), $targets);
        //$help = '<span class="help-block">' . get_lang('AddTargetOfLinkOnHomepage') . '</span>';
        //$this->add_label('', $help);

        $this->add_button('save', get_lang('Save'), array('class' => 'btn save'));

        $this->setDefaults($defaults);
    }

    function update_model()
    {
        $values = $this->exportValues();
        $link = $this->get_link();
        $link->url = $values['url'];
        $link->title = $values['title'];
        $link->description = $values['description'];
        $link->category_id = $values['category_id'];
        $link->on_homepage = isset($values['on_homepage']) ? $values['on_homepage'] : false;
        $link->target = $values['target'];
    }

}