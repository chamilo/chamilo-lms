<?php
/* For licensing terms, see /license.txt */

/**
 * Create a user in Drupal website when a user is registered in Chamilo LMS.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 *
 * @package chamilo.plugin.createDrupalUser
 */
class CreateDrupalUser extends Plugin implements HookPluginInterface
{
    public const EXTRAFIELD_VARIABLE_NAME = 'drupal_user_id';

    /**
     * Class constructor.
     */
    protected function __construct()
    {
        $parameters = [
            'drupal_domain' => 'text',
        ];

        parent::__construct('1.0', 'Angel Fernando Quiroz Campos', $parameters);
    }

    /**
     * Instance the plugin.
     *
     * @staticvar null $result
     *
     * @return CreateDrupalUser
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * Install the plugin.
     */
    public function install()
    {
        $this->createExtraField();
        $this->installHook();
    }

    /**
     * Uninstall the plugin.
     */
    public function uninstall()
    {
        $this->uninstallHook();
        $this->deleteExtraField();
    }

    /**
     * Install the Create User hook.
     */
    public function installHook()
    {
        /** @var HookCreateDrupalUser $observer */
        $observer = HookCreateDrupalUser::create();
        HookCreateUser::create()->attach($observer);
    }

    /**
     * Uninstall the Create User hook.
     */
    public function uninstallHook()
    {
        /** @var HookCreateDrupalUser $observer */
        $observer = HookCreateDrupalUser::create();
        $event = HookCreateUser::create();

        if ($event) {
            $event->detach($observer);
        }
    }

    /**
     * Get the drupal_user_id extra field information.
     *
     * @return array The info
     */
    private function getExtraFieldInfo()
    {
        $extraField = new ExtraField('user');
        $extraFieldHandler = $extraField->get_handler_field_info_by_field_variable(
            self::EXTRAFIELD_VARIABLE_NAME
        );

        return $extraFieldHandler;
    }

    /**
     * Create the drupal_user_id when it not exists.
     */
    private function createExtraField()
    {
        $extraFieldExists = $this->getExtraFieldInfo() !== false;

        if (!$extraFieldExists) {
            $extraField = new ExtraField('user');
            $extraField->save(
                [
                    'field_type' => ExtraField::FIELD_TYPE_INTEGER,
                    'variable' => self::EXTRAFIELD_VARIABLE_NAME,
                    'display_text' => get_plugin_lang('DrupalUserId', 'CreateDrupalUser'),
                    'default_value' => null,
                    'field_order' => null,
                    'visible_to_self' => false,
                    'changeable' => false,
                    'filter' => null,
                ]
            );
        }
    }

    /**
     * Delete the drupal_user_id and values.
     */
    private function deleteExtraField()
    {
        $extraFieldInfo = $this->getExtraFieldInfo();
        $extraFieldExists = $extraFieldInfo !== false;

        if ($extraFieldExists) {
            $extraField = new ExtraField('user');
            $extraField->delete($extraFieldInfo['id']);
        }
    }

    /*
    public function notifyDocumentAction(HookDocumentActionEventInterface $hook)
    {
        $data = $hook->getEventData();
        if ($data['type'] === HOOK_EVENT_TYPE_PRE) {
            $data['actions'][] = Display::return_icon('edit.png');
        }

        return $data;
    }

    public function notifyDocumentItemAction(HookDocumentItemActionEventInterface $hook)
    {
        $data = $hook->getEventData();
        if ($data['type'] === HOOK_EVENT_TYPE_PRE) {
            $data['actions'][] = $data['id'].' - '.$data['title'];
        }

        return $data;
    }*/
}
