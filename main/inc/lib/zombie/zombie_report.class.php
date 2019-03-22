<?php

/**
 * Description of zombie_report.
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class ZombieReport implements Countable
{
    protected $additional_parameters = [];

    protected $parameters_form = null;

    public function __construct($additional_parameters = [])
    {
        $this->additional_parameters = $additional_parameters;
    }

    /**
     * @return ZombieReport
     */
    public static function create($additional_parameters = [])
    {
        return new self($additional_parameters);
    }

    public function get_additional_parameters()
    {
        return $this->additional_parameters;
    }

    public function get_parameters()
    {
        $result = [
            'items' => [
                [
                    'name' => 'ceiling',
                    'label' => get_lang('LastAccess'),
                    'type' => 'date_picker',
                    'default' => $this->get_ceiling('Y-m-d'),
                    'rules' => [
                        [
                            'type' => 'date',
                            'message' => get_lang('Date'),
                        ],
                    ],
                ],
                [
                    'name' => 'active_only',
                    'label' => get_lang('ActiveOnly'),
                    'type' => 'checkbox',
                    'default' => $this->get_active_only(),
                ],
                [
                    'name' => 'submit_button',
                    'type' => 'button',
                    'value' => get_lang('Search'),
                    'attributes' => ['class' => 'search'],
                ],
            ],
        ];

        return $result;
    }

    /**
     * @return FormValidator
     */
    public function get_parameters_form()
    {
        $form = new FormValidator(
            'zombie_report_parameters',
            'get',
            null,
            null,
            ['class' => 'well form-horizontal form-search']
        );

        $form->addDatePicker('ceiling', get_lang('LastAccess'));
        $form->addCheckBox('active_only', get_lang('ActiveOnly'));
        $form->addButtonSearch(get_lang('Search'));

        $params = [
            'active_only' => $this->get_active_only(),
            'ceiling' => $this->get_ceiling('Y-m-d'),
        ];
        $form->setDefaults($params);
        $additional = $this->get_additional_parameters();
        foreach ($additional as $key => $value) {
            $value = Security::remove_XSS($value);
            $form->addHidden($key, $value);
        }

        return $form;
    }

    public function display_parameters($return = false)
    {
        $form = $this->get_parameters_form();
        $result = $form->returnForm();

        if ($return) {
            return $result;
        } else {
            echo $result;
        }
    }

    public function is_valid()
    {
        $form = $this->get_parameters_form();

        return $form->isSubmitted() == false || $form->validate();
    }

    public function get_ceiling($format = null)
    {
        $result = Request::get('ceiling');
        $result = $result ? $result : ZombieManager::last_year();

        $result = is_array($result) && count($result) == 1 ? reset($result) : $result;
        $result = is_array($result) ? mktime(0, 0, 0, $result['F'], $result['d'], $result['Y']) : $result;
        $result = is_numeric($result) ? (int) $result : $result;
        $result = is_string($result) ? strtotime($result) : $result;
        if ($format) {
            $result = date($format, $result);
        }

        return $result;
    }

    public function get_active_only()
    {
        $result = Request::get('active_only', false);
        $result = $result === 'true' ? true : $result;
        $result = $result === 'false' ? false : $result;
        $result = (bool) $result;

        return $result;
    }

    public function get_action()
    {
        /**
         * todo check token.
         */
        $check = Security::check_token('post');
        Security::clear_token();
        if (!$check) {
            return 'display';
        }

        return Request::post('action', 'display');
    }

    public function perform_action()
    {
        $ids = Request::post('id');
        if (empty($ids)) {
            return $ids;
        }

        $action = $this->get_action();
        switch ($action) {
            case 'activate':
                return UserManager::activate_users($ids);
                break;
            case 'deactivate':
                return UserManager::deactivate_users($ids);
                break;
            case 'delete':
                return UserManager::delete_users($ids);
        }

        return false;
    }

    public function count()
    {
        $ceiling = $this->get_ceiling();
        $active_only = $this->get_active_only();
        $items = ZombieManager::listZombies($ceiling, $active_only, null, null);

        return count($items);
    }

    public function get_data($from, $count, $column, $direction)
    {
        $ceiling = $this->get_ceiling();
        $active_only = $this->get_active_only();
        $items = ZombieManager::listZombies($ceiling, $active_only, $from, $count, $column, $direction);
        $result = [];
        foreach ($items as $item) {
            $row = [];
            $row[] = $item['user_id'];
            $row[] = $item['official_code'];
            $row[] = $item['firstname'];
            $row[] = $item['lastname'];
            $row[] = $item['username'];
            $row[] = $item['email'];
            $row[] = $item['status'];
            $row[] = $item['auth_source'];
            $row[] = api_format_date($item['registration_date'], DATE_FORMAT_SHORT);
            $row[] = api_format_date($item['login_date'], DATE_FORMAT_SHORT);
            $row[] = $item['active'];
            $result[] = $row;
        }

        return $result;
    }

    public function display_data($return = false)
    {
        $count = [$this, 'count'];
        $data = [$this, 'get_data'];

        $parameters = [];
        $parameters['sec_token'] = Security::get_token();
        $parameters['ceiling'] = $this->get_ceiling();
        $parameters['active_only'] = $this->get_active_only() ? 'true' : 'false';
        $additional_parameters = $this->get_additional_parameters();
        $parameters = array_merge($additional_parameters, $parameters);

        $table = new SortableTable('zombie_users', $count, $data, 1, 50);
        $table->set_additional_parameters($parameters);

        $col = 0;
        $table->set_header($col++, '', false);
        $table->set_header($col++, get_lang('OfficialCode'));
        $table->set_header($col++, get_lang('FirstName'));
        $table->set_header($col++, get_lang('LastName'));
        $table->set_header($col++, get_lang('LoginName'));
        $table->set_header($col++, get_lang('Email'));
        $table->set_header($col++, get_lang('Profile'));
        $table->set_header($col++, get_lang('AuthenticationSource'));
        $table->set_header($col++, get_lang('RegisteredDate'));
        $table->set_header($col++, get_lang('LastAccess'), false);
        $table->set_header($col, get_lang('Active'), false);

        $table->set_column_filter(5, [$this, 'format_email']);
        $table->set_column_filter(6, [$this, 'format_status']);
        $table->set_column_filter(10, [$this, 'format_active']);

        $table->set_form_actions([
            'activate' => get_lang('Activate'),
            'deactivate' => get_lang('Deactivate'),
            'delete' => get_lang('Delete'),
        ]);

        if ($return) {
            return $table->return_table();
        } else {
            echo $table->return_table();
        }
    }

    /**
     * Table formatter for the active column.
     *
     * @param string $active
     *
     * @return string
     */
    public function format_active($active)
    {
        $active = $active == '1';
        if ($active) {
            $image = 'accept';
            $text = get_lang('Yes');
        } else {
            $image = 'error';
            $text = get_lang('No');
        }

        $result = Display::return_icon($image.'.png', $text);

        return $result;
    }

    public function format_status($status)
    {
        $statusname = api_get_status_langvars();

        return $statusname[$status];
    }

    public function format_email($email)
    {
        return Display::encrypted_mailto_link($email, $email);
    }

    public function display($return = false)
    {
        $result = $this->display_parameters($return);
        $valid = $this->perform_action();

        if ($valid) {
            echo Display::return_message(get_lang('Updated'), 'confirmation');
        }

        $result .= $this->display_data($return);

        if ($return) {
            return $result;
        }
    }
}
