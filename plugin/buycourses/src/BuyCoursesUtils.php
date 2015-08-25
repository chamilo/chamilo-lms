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

    const TABLE_PAYPAL = 'plugin_buycourses_paypal_account';
    const TABLE_CURRENCY = 'plugin_buycourses_currency';
    const TABLE_ITEM = 'plugin_buycourses_item';
    const TABLE_SALE = 'plugin_buycourses_sale';
    const TABLE_TRANSFER = 'plugin_buycourses_transfer';

    const TABLE_SESSION = 'plugin_buy_session';
    const TABLE_SESSION_COURSE = 'plugin_buy_session_course';
    const TABLE_SESSION_TEMPORARY = 'plugin_buy_session_temporary';
    const TABLE_SESSION_SALE = 'plugin_buy_session_sale';
    const TABLE_COURSE = 'plugin_buy_course';
    const TABLE_COUNTRY = 'plugin_buy_course_country';
    const TABLE_TEMPORAL = 'plugin_buy_course_temporal';

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
                    $currency['iso_code']
                ]
            );
            $currencyValue = $currency['id'];

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

}
