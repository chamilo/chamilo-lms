<?php
/* For license terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Class Keycloak.
 */
class KeycloakPlugin extends Plugin
{
    /**
     * Keycloak constructor.
     */
    protected function __construct()
    {
        parent::__construct(
            '1.1',
            'Julio Montoya',
            [
                'tool_enable' => 'boolean',
            ]
        );
    }

    /**
     * @return $this
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * @return string
     */
    public function get_block_title()
    {
        return $this->get('block_title');
    }

    /**
     * @return string
     */
    public function get_content()
    {
        return $this->get('content');
    }

    /**
     * Deletes all keycloak chamilo session data.
     */
    public function logout()
    {
        Session::erase('samlUserdata');
        Session::erase('samlNameId');
        Session::erase('samlNameIdFormat');
        Session::erase('samlSessionIndex');
        Session::erase('AuthNRequestID');
    }
}
