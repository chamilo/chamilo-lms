<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ThemeBundle\Event;

/**
 * Holds all events used by the theme.
 */
class ThemeEvents
{
    /**
     * Used to receive notification data.
     */
    public const THEME_NOTIFICATIONS = 'theme.notifications';
    /**
     * Used to receive message data.
     */
    public const THEME_MESSAGES = 'theme.messages';
    /**
     * Used to receive task data.
     */
    public const THEME_TASKS = 'theme.tasks';

    public const THEME_NAVBAR_USER = 'theme.navbar_user';
    /**
     * used to receive breadcrumb data.
     */
    public const THEME_BREADCRUMB = 'theme.breadcrumb';
    /**
     * used to receive the current user for the sidebar.
     */
    public const THEME_SIDEBAR_USER = 'theme.sidebar_user';
    /**
     * Used for searching.
     *
     * @unused
     */
    public const THEME_SIDEBAR_SEARCH = 'theme.sidebar_search';
    /**
     * Used to receive the sidebar menu data.
     */
    public const THEME_SIDEBAR_SETUP_MENU = 'theme.sidebar_setup_menu';
    /**
     * Used to receive the sidebar menu data.
     */
    public const THEME_SIDEBAR_SETUP_MENU_KNP = 'theme.sidebar_setup_menu_knp';

    public const THEME_SIDEBAR_ACTIVATE_MENU = 'theme.sidebar_activate_menu';
}
