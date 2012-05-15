<?php

/**
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class AdminPage extends Page
{

    /**
     *
     * @return AdminPage
     */
    static function create($title = '')
    {
        return new self($title);
    }
    
    function __construct($title = '')
    {
        global $this_section;
        $this_section = SECTION_PLATFORM_ADMIN;

        api_protect_admin_script();

        if (empty($title)) {
            $title = get_lang(get_class($this));
        }

        $this->title = $title;

        $this->breadcrumbs = array();
        $this->breadcrumbs[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
    }

}