<?php

/**
 * Web page wrapper. Usage:
 * 
 *  Page::create()->title('my_title')->display($content);
 *  
 *  $page = Page::create()->title('my_title')->help('help')->content($content);
 *  $page->display();
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class Page
{

    protected $title = null;
    protected $help = null;
    protected $header = null;
    protected $content;
    protected $breadcrumbs = '';
    protected $message = null;
    protected $warning = null;
    protected $error = null;

    /**
     *
     * @return Page
     */
    static function create($title = '')
    {
        return new self($title);
    }

    function __construct($title = '')
    {
        $this->title = $title;
    }

    /**
     *
     * @param $header
     * @return Page 
     */
    function header($header)
    {
        $this->header = $header;
        return $this;
    }

    /**
     *
     * @param string $title
     * @return Page 
     */
    function title($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     *
     * @param array $crumbs_
     * @return Page 
     */
    function breadcrumbs($crumbs)
    {        
        $this->breadcrumbs = $crumbs;
        return $this;
    }

    /**
     *
     * @param string $help help file name
     * @return Page 
     */
    function help($help)
    {
        $this->help = $help;
        return $this;
    }

    /**
     *
     * @param string $message
     * @return Page 
     */
    function message($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     *
     * @param string $warning
     * @return Page 
     */
    function warning($warning)
    {
        $this->warning = $warning;
        return $this;
    }

    /**
     *
     * @param string $error
     * @return Page 
     */
    function error($error)
    {
        $this->error = $error;
        return $this;
    }

    /**
     *
     * @param object|string $content
     * @return Page 
     */
    function content($content)
    {
        $this->content = $content;
        return $this;
    }

    function __toString()
    {
        $this->display($this->content);
    }

    function display($content = null)
    {
        $this->display_header();
        $this->display_content($content);
        $this->display_footer();
    }

    function display_header()
    {
        global $interbreadcrumb;
        $interbreadcrumb = $this->breadcrumbs;

        Display::display_header($this->title, $this->help, $this->header);
        if ($message = $this->message) {
            Display::display_confirmation_message($message);
        }
        if ($warning = $this->warning) {
            Display::display_warning_message($warning);
        }
        if ($error = $this->error) {
            Display::display_error_message($error);
        }
    }

    protected function display_content($content)
    {
        $content = $content ? $content : $this->content;
        echo $content;
    }

    function display_footer()
    {
        Display::display_footer();
    }

}