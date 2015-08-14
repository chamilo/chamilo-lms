<?php
/* For licensing terms, see /license.txt */
use \Doctrine\DBAL\Types\Type;
/**
 * BuyCoursesUtils
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
class BuyCoursesUtils
{

    const TABLE_SESSION = 'plugin_buy_session';
    const TABLE_SESSION_COURSE = 'plugin_buy_session_course';
    const TABLE_SESSION_TEMPORARY = 'plugin_buy_session_temporary';
    const TABLE_SESSION_SALE = 'plugin_buy_session_sale';
    const TABLE_COURSE = 'plugin_buy_course';
    const TABLE_COUNTRY = 'plugin_buy_course_country';
    const TABLE_PAYPAL = 'plugin_buy_course_paypal';
    const TABLE_TRANSFER = 'plugin_buy_course_transfer';
    const TABLE_TEMPORAL = 'plugin_buy_course_temporal';
    const TABLE_SALE = 'plugin_buy_course_sale';

    private static $plugin;

    /**
     * @static
     * @param type $plugin
     */
    public static function setPlugin($plugin)
    {
        self::$plugin = $plugin;
    }

    /**
     * Generate a form for save currency
     * @static
     * @return \FormValidator
     */
    public static function getCurrencyForm()
    {
        $form = new FormValidator('currency');

        if ($form->validate()) {
            $formValues = $form->getSubmitValues();

            self::$plugin->selectCurrency($formValues['currency']);

            Display::addFlash(
                Display::return_message(get_lang('Saved'), 'success')
            );

            header('Location:' . api_get_self());
            exit;
        }

        $currencies = self::$plugin->getCurrencies();

        $currencySelect = $form->addSelect(
            'currency',
            [
                self::$plugin->get_lang('CurrencyType'),
                self::$plugin->get_lang('InfoCurrency')
            ],
            [get_lang('Select')]
        );

        foreach ($currencies as $currency) {
            $currencyText = implode(
                ' => ',
                [
                    $currency['country_name'],
                    $currency['currency_code']
                ]
            );
            $currencyValue = $currency['country_id'];

            $currencySelect->addOption($currencyText, $currencyValue);

            if ($currency['status']) {
                $currencySelect->setSelected($currencyValue);
            }
        }

        $form->addButtonSave(get_lang('Save'));

        return $form;
    }

    /**
     * Generate a form for save Paypal params
     * @static
     * @return \FormValidator
     */
    public static function getPaypalForm()
    {
        $form = new FormValidator('paypal');

        if ($form->validate()) {
            $formValues = $form->getSubmitValues();

            self::$plugin->savePaypalParams($formValues);

            Display::addFlash(
                Display::return_message(get_lang('Saved'), 'success')
            );

            header('Location:' . api_get_self());
            exit;
        }

        $form->addText(
            'username',
            self::$plugin->get_lang('ApiUsername'),
            false,
            ['cols-size' => [3, 8, 1]]
        );
        $form->addText(
            'password',
            self::$plugin->get_lang('ApiPassword'),
            false,
            ['cols-size' => [3, 8, 1]]
        );
        $form->addText(
            'signature',
            self::$plugin->get_lang('ApiSignature'),
            false,
            ['cols-size' => [3, 8, 1]]
        );
        $form->addCheckBox(
            'sandbox',
            null,
            self::$plugin->get_lang('Sandbox')
        );
        $form->addButtonSave(get_lang('Save'));

        $form->setDefaults(self::$plugin->getPaypalParams());

        return $form;
    }

    /**
     * Generate a form for add a transfer account
     * @static
     * @return \FormValidator
     */
    public static function getTransferForm()
    {
        $form = new FormValidator('transfer_account');

        if ($form->validate()) {
            $formValues = $form->getSubmitValues();

            self::$plugin->saveTransferAccount($formValues);

            Display::addFlash(
                Display::return_message(get_lang('Saved'), 'success')
            );

            header('Location:' . api_get_self());
            exit;
        }

        $form->addText(
            'tname',
            get_lang('Name'),
            false,
            ['cols-size' => [3, 8, 1]]
        );
        $form->addText(
            'taccount',
            self::$plugin->get_lang('BankAccount'),
            false,
            ['cols-size' => [3, 8, 1]]
        );
        $form->addText(
            'tswift',
            get_lang('SWIFT'),
            false,
            ['cols-size' => [3, 8, 1]]
        );
        $form->addButtonCreate(get_lang('Add'));

        return $form;
    }

    /**
     * Sync the courses and sessions
     */
    public static function sync()
    {
        $buySessionCourseTable = Database::get_main_table(TABLE_BUY_SESSION_COURSE);
        $sessionCourseTable = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $tableBuyCourse = Database::get_main_table(TABLE_BUY_COURSE);
        $tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
        $tableBuySession = Database::get_main_table(TABLE_BUY_SESSION);
        $tableSession = Database::get_main_table(TABLE_MAIN_SESSION);

        Database::update(
            $buySessionCourseTable,
            ['sync' => 0]
        );

        $sql = "
            SELECT session_id, c_id, nbr_users
            FROM $sessionCourseTable";
        $res = Database::query($sql);

        while ($row = Database::fetch_assoc($res)) {
            $sql = "
                SELECT 1 FROM $buySessionCourseTable
                WHERE session_id=" . $row['session_id'];
            $result = Database::query($sql);

            if (Database::affected_rows($result) > 0) {
                Database::update(
                    $buySessionCourseTable,
                    ['sync' => 1],
                    ['session_id = ?' => $row['session_id']]
                );
            } else {
                $courseCode = api_get_course_info_by_id($row['c_id'])['code'];
                Database::insert(
                    $buySessionCourseTable,
                    [
                        'session_id' => $row['session_id'],
                        'course_code' => $courseCode,
                        'nbr_users' => $row['nbr_users'],
                        'sync' => 1
                    ]
                );
            }
        }
        
        Database::delete(
            $buySessionCourseTable,
            ['sync = ?' => 0]
        );

        Database::update(
            $tableBuyCourse,
            ['sync' => 0]
        );

        $sql = "SELECT id, code, title FROM $tableCourse";
        $res = Database::query($sql);

        while ($row = Database::fetch_assoc($res)) {
            $sql = "
                SELECT session_id FROM $buySessionCourseTable
                WHERE course_code = '" . $row['code'] . "' LIMIT 1";
            $courseIdSession = Database::fetch_assoc(Database::query($sql))['session_id'];

            if (!is_numeric($courseIdSession)) {
                $courseIdSession = 0;
            }

            $sql = "
                SELECT 1 FROM $tableBuyCourse
                WHERE course_id='" . $row['id'] . "'";
            $result = Database::query($sql);

            if (Database::affected_rows($result) > 0) {
                Database::update(
                    $tableBuyCourse,
                    [
                        'sync' => 1,
                        'session_id' => $courseIdSession
                    ],
                    ['course_id = ?' => $row['id']]
                );
            } else {
                Database::insert(
                    $tableBuyCourse,
                    [
                        'session_id' => $courseIdSession,
                        'course_id' => $row['id'],
                        'code' => $row['code'],
                        'title' => $row['title'],
                        'visible' => 0,
                        'sync' => 1
                    ]
                );
            }
        }

        Database::delete(
            $tableBuyCourse,
            ['sync = ?' => 0]
        );

        Database::update(
            $tableBuySession,
            ['sync' => 0]
        );

        $sql = "
            SELECT id, name, access_start_date, access_end_date
            FROM $tableSession";
        $res = Database::query($sql);

        while ($row = Database::fetch_assoc($res)) {
            $sql = "SELECT 1 FROM $tableBuySession WHERE session_id='" . $row['id'] . "';";
            $result = Database::query($sql);

            if (Database::affected_rows($result) > 0) {
                Database::update(
                    $tableBuySession,
                    ['sync' => 1],
                    ['session_id = ?' => $row['id']]
                );
            } else {
                Database::insert(
                    $tableBuySession,
                    [
                        'session_id' => $row['id'],
                        'name' => $row['name'],
                        'date_start' => $row['access_start_date'],
                        'date_end' => $row['access_end_date'],
                        'visible' => 0,
                        'sync' => 1
                    ]
                );
            }
        }

        Database::delete(
            $tableBuySession,
            ['sync = ?' => 0]
        );
    }

}
