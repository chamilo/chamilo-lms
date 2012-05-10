<?php

/**
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class AdminPage
{

    protected $title = '';
    protected $breadcrumbs = '';

    function __construct($title = '', $breadcrumbs = array())
    {
        global $this_section;
        $this_section = SECTION_PLATFORM_ADMIN;
        
        api_protect_admin_script();
        
        if (empty($title)) {
            $title = get_lang(get_class($this));
        }

        $this->title = $title;

        if (empty($breadcrumbs)) {
            $breadcrumbs[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
        }

        $this->breadcrumbs = $breadcrumbs;
    }

    public function get_title()
    {
        return $this->title;
    }

    public function get_breadcrumbs()
    {
        return $this->breadcrumbs;
    }

    function display()
    {
        $this->display_header();
        $this->display_content();
        $this->display_footer();
    }

    public function display_header()
    {
        $breadcrumbs = $this->get_breadcrumbs();
        $title = $this->get_title();

        global $interbreadcrumb;
        $interbreadcrumb = $breadcrumbs;
        Display :: display_header($title);
    }

    public function display_content()
    {
        ;
    }

    public function display_footer()
    {
        Display :: display_footer();
    }

}