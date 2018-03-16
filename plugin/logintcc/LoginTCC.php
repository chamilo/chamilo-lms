<?php

/**
 * Class LoginTCC.
 */
class LoginTCC extends Plugin
{
    public $isCoursePlugin = false;

    /**
     * Constructor.
     */
    protected function __construct()
    {
        parent::__construct(
            'O.1',
            'Julio Montoya',
            [
                'tool_enable' => 'boolean',
                'webservice_url' => 'text',
                'sso_url' => 'text',
                'hash' => 'text',
            ]
        );
    }

    /**
     * @return LoginTCC|null
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * Install.
     */
    public function install()
    {
        $extraField = new ExtraField('user');
        $data = $extraField->get_handler_field_info_by_field_variable('tcc_user_id');
        if (empty($data)) {
            $params = [
                'field_type' => 1,
                'variable' => 'tcc_user_id',
                'display_text' => 'TCC user id',
                'default_value' => 0,
                'visible' => false,
                'changeable' => true,
                'filter' => false,
            ];
            $extraField->save($params);
        }

        $data = $extraField->get_handler_field_info_by_field_variable('tcc_hash_key');
        if (empty($data)) {
            $params = [
                'field_type' => 1,
                'variable' => 'tcc_hash_key',
                'display_text' => 'TCC hash key',
                'default_value' => 0,
                'visible' => false,
                'changeable' => true,
                'filter' => false,
            ];
            $extraField->save($params);
        }
    }

    /**
     * Uninstall.
     */
    public function uninstall()
    {
    }
}
