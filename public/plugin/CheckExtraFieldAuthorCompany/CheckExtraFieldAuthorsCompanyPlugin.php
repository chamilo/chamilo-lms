<?php

/* For licensing terms, see /license.txt */

/**
 * Ensures the extra fields required by the extra reports are available.
 *
 * The plugin does not remove fields during uninstall because those fields can
 * contain production reporting data.
 */
class CheckExtraFieldAuthorsCompanyPlugin extends Plugin
{
    private const FIELD_COMPANY = 'company';
    private const FIELD_AUTHORS = 'authors';
    private const FIELD_PRICE = 'price';
    private const FIELD_AUTHOR_LP_ITEM = 'authorlpitem';
    private const FIELD_AUTHOR_LP = 'authorlp';

    /**
     * @var string
     */
    protected $tblExtraFieldOption;

    /**
     * @var string
     */
    protected $tblExtraField;

    /**
     * @var bool
     */
    protected $companyExists = false;

    /**
     * @var bool
     */
    protected $authorsExists = false;

    /**
     * @var bool
     */
    protected $priceExists = false;

    /**
     * @var bool
     */
    protected $authorLpItemExists = false;

    /**
     * @var bool
     */
    protected $authorLpExists = false;

    /**
     * @var array
     */
    protected $companyField = [];

    /**
     * @var array
     */
    protected $authorsField = [];

    /**
     * @var array
     */
    protected $priceField = [];

    /**
     * @var array
     */
    protected $authorLpItemField = [];

    /**
     * @var array
     */
    protected $authorLpField = [];

    public function __construct()
    {
        parent::__construct(
            '1.2',
            'Carlos Alvarado, Julio Montoya'
        );

        $this->tblExtraField = Database::get_main_table(TABLE_EXTRA_FIELD);
        $this->tblExtraFieldOption = Database::get_main_table(TABLE_EXTRA_FIELD_OPTIONS);
    }

    /**
     * Create a new instance of CheckExtraFieldAuthorsCompanyPlugin.
     *
     * @return CheckExtraFieldAuthorsCompanyPlugin
     */
    public static function create()
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    /**
     * Perform the plugin installation.
     */
    public function install()
    {
        $this->saveCompanyField();
        $this->setCompanyExtrafieldData();
        $this->saveAuthorsField();
        $this->savePrice();
        $this->saveAuthorLPItem();
        $this->saveAuthorLp();
    }

    /**
     * Save the required user company extra field.
     */
    public function saveCompanyField()
    {
        $this->companyField = $this->ensureExtraField(
            'user',
            self::FIELD_COMPANY,
            [
                'value_type' => ExtraField::FIELD_TYPE_RADIO,
                'variable' => self::FIELD_COMPANY,
                'display_text' => 'Company',
                'default_value' => '',
                'field_order' => 0,
                'visible_to_self' => 0,
                'visible_to_others' => 0,
                'changeable' => 1,
                'filter' => 1,
                'visible' => 1,
            ]
        );

        $this->companyExists = !empty($this->companyField['id']);

        return $this->companyField['id'] ?? false;
    }

    /**
     * Insert the option fields for company with the generic values Company 1, Company 2 and Company 3.
     */
    public function setCompanyExtrafieldData()
    {
        if (empty($this->companyField['id'])) {
            $this->saveCompanyField();
        }

        $companyId = (int) ($this->companyField['id'] ?? 0);
        if (0 === $companyId) {
            return;
        }

        $companies = [
            'Company 1',
            'Company 2',
            'Company 3',
        ];

        foreach ($companies as $index => $extraFieldOptionValue) {
            $order = $index + 1;
            $safeValue = Database::escape_string($extraFieldOptionValue);

            $query = "SELECT id
                      FROM {$this->tblExtraFieldOption}
                      WHERE option_value = '$safeValue'
                      AND field_id = $companyId
                      LIMIT 1";
            $extraFieldOption = Database::fetch_assoc(Database::query($query));

            if (!empty($extraFieldOption['id'])) {
                continue;
            }

            $query = "INSERT INTO {$this->tblExtraFieldOption}
                      (field_id, option_value, display_text, priority, priority_message, option_order)
                      VALUES
                      ($companyId, '$safeValue', '$safeValue', NULL, NULL, $order)";
            Database::query($query);
        }
    }

    /**
     * Save the required LP authors extra field.
     */
    public function saveAuthorsField()
    {
        $this->authorsField = $this->ensureExtraField(
            'lp',
            self::FIELD_AUTHORS,
            [
                'value_type' => ExtraField::FIELD_TYPE_SELECT_MULTIPLE,
                'variable' => self::FIELD_AUTHORS,
                'display_text' => 'Authors',
                'default_value' => '',
                'field_order' => 0,
                'visible_to_self' => 1,
                'visible_to_others' => 0,
                'changeable' => 1,
                'filter' => 1,
                'visible' => 1,
            ]
        );

        $this->authorsExists = !empty($this->authorsField['id']);

        return $this->authorsField['id'] ?? false;
    }

    /**
     * Save the required LP item price extra field.
     */
    public function savePrice()
    {
        $this->priceField = $this->ensureExtraField(
            'lp_item',
            self::FIELD_PRICE,
            [
                'visible_to_self' => 1,
                'visible_to_others' => 1,
                'changeable' => 1,
                'filter' => 0,
                'variable' => self::FIELD_PRICE,
                'display_text' => 'SalePrice',
                'value_type' => ExtraField::FIELD_TYPE_INTEGER,
                'visible' => 1,
            ]
        );

        $this->priceExists = !empty($this->priceField['id']);

        return $this->priceField['id'] ?? false;
    }

    /**
     * Save the required LP item author extra field.
     */
    public function saveAuthorLPItem()
    {
        $this->authorLpItemField = $this->ensureExtraField(
            'lp_item',
            self::FIELD_AUTHOR_LP_ITEM,
            [
                'visible_to_self' => 1,
                'visible_to_others' => 0,
                'changeable' => 1,
                'filter' => 0,
                'variable' => self::FIELD_AUTHOR_LP_ITEM,
                'display_text' => 'LearningPathItemByAuthor',
                'value_type' => ExtraField::FIELD_TYPE_SELECT_MULTIPLE,
                'visible' => 1,
            ]
        );

        $this->authorLpItemExists = !empty($this->authorLpItemField['id']);

        return $this->authorLpItemField['id'] ?? false;
    }

    /**
     * Save the required user LP author marker extra field.
     */
    public function saveAuthorLp()
    {
        $this->authorLpField = $this->ensureExtraField(
            'user',
            self::FIELD_AUTHOR_LP,
            [
                'variable' => self::FIELD_AUTHOR_LP,
                'display_text' => 'Authors',
                'changeable' => 1,
                'visible_to_self' => 1,
                'visible_to_others' => 0,
                'filter' => 0,
                'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
                'visible' => 1,
            ]
        );

        $this->authorLpExists = !empty($this->authorLpField['id']);

        return $this->authorLpField['id'] ?? false;
    }

    /**
     * Remove the extra fields set by the plugin.
     */
    public function uninstall()
    {
        // Intentionally keep all extra fields and their values for data persistence.
    }

    /**
     * Verify that the "company" field exists in the database.
     */
    public function companyFieldExists(): bool
    {
        $this->companyField = $this->getCompanyField();
        $this->companyExists = !empty($this->companyField['id']);

        return $this->companyExists;
    }

    /**
     * Returns the content of the extra field "company".
     *
     * @return array
     */
    public function getCompanyField()
    {
        return $this->getFieldInfo(
            'user',
            self::FIELD_COMPANY,
            [
                'value_type' => ExtraField::FIELD_TYPE_RADIO,
                'variable' => self::FIELD_COMPANY,
                'display_text' => 'Company',
                'default_value' => '',
                'field_order' => 0,
                'visible_to_self' => 0,
                'visible_to_others' => 0,
                'changeable' => 1,
                'filter' => 1,
                'visible' => 1,
            ]
        );
    }

    /**
     * Verify that the "authors" field exists in the database.
     */
    public function authorsFieldExists(): bool
    {
        $this->authorsField = $this->getAuthorsField();
        $this->authorsExists = !empty($this->authorsField['id']);

        return $this->authorsExists;
    }

    /**
     * Returns the content of the extra field "authors".
     *
     * @return array
     */
    public function getAuthorsField()
    {
        return $this->getFieldInfo(
            'lp',
            self::FIELD_AUTHORS,
            [
                'value_type' => ExtraField::FIELD_TYPE_SELECT_MULTIPLE,
                'variable' => self::FIELD_AUTHORS,
                'display_text' => 'Authors',
                'default_value' => '',
                'field_order' => 0,
                'visible_to_self' => 1,
                'visible_to_others' => 0,
                'changeable' => 1,
                'filter' => 1,
                'visible' => 1,
            ]
        );
    }

    /**
     * Verify that the "price" field exists in the database.
     */
    public function priceFieldExists(): bool
    {
        $this->priceField = $this->getPriceField();
        $this->priceExists = !empty($this->priceField['id']);

        return $this->priceExists;
    }

    /**
     * Returns the content of the extra field "price".
     *
     * @return array
     */
    public function getPriceField()
    {
        return $this->getFieldInfo(
            'lp_item',
            self::FIELD_PRICE,
            [
                'visible_to_self' => 1,
                'visible_to_others' => 1,
                'changeable' => 1,
                'filter' => 0,
                'variable' => self::FIELD_PRICE,
                'display_text' => 'SalePrice',
                'value_type' => ExtraField::FIELD_TYPE_INTEGER,
                'visible' => 1,
            ]
        );
    }

    /**
     * Verify that the "authorlpitem" field exists in the database.
     */
    public function authorLpItemFieldExists(): bool
    {
        $this->authorLpItemField = $this->getAuthorLpItemField();
        $this->authorLpItemExists = !empty($this->authorLpItemField['id']);

        return $this->authorLpItemExists;
    }

    /**
     * Returns the content of the extra field "authorlpitem".
     *
     * @return array
     */
    public function getAuthorLpItemField()
    {
        return $this->getFieldInfo(
            'lp_item',
            self::FIELD_AUTHOR_LP_ITEM,
            [
                'visible_to_self' => 1,
                'visible_to_others' => 0,
                'changeable' => 1,
                'filter' => 0,
                'variable' => self::FIELD_AUTHOR_LP_ITEM,
                'display_text' => 'LearningPathItemByAuthor',
                'value_type' => ExtraField::FIELD_TYPE_SELECT_MULTIPLE,
                'visible' => 1,
            ]
        );
    }

    /**
     * Verify that the "authorlp" field exists in the database.
     */
    public function authorLpFieldExists(): bool
    {
        $this->authorLpField = $this->getAuthorLpField();
        $this->authorLpExists = !empty($this->authorLpField['id']);

        return $this->authorLpExists;
    }

    /**
     * Returns the content of the extra field "authorlp".
     *
     * @return array
     */
    public function getAuthorLpField()
    {
        return $this->getFieldInfo(
            'user',
            self::FIELD_AUTHOR_LP,
            [
                'variable' => self::FIELD_AUTHOR_LP,
                'display_text' => 'Authors',
                'changeable' => 1,
                'visible_to_self' => 1,
                'visible_to_others' => 0,
                'filter' => 0,
                'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
                'visible' => 1,
            ]
        );
    }

    /**
     * Remove the extra field "company".
     */
    public function removeCompanyField()
    {
        // Intentionally disabled to preserve existing data.
    }

    /**
     * Remove the extra field "authors".
     */
    public function removeAuthorsField()
    {
        // Intentionally disabled to preserve existing data.
    }

    /**
     * Remove the extra field "price".
     */
    public function removePriceField()
    {
        // Intentionally disabled to preserve existing data.
    }

    /**
     * Remove the extra field "authorlpitem".
     */
    public function removeAuthorLpItemField()
    {
        // Intentionally disabled to preserve existing data.
    }

    /**
     * Remove the extra field "authorlp".
     */
    public function removeAuthorLpField()
    {
        // Intentionally disabled to preserve existing data.
    }

    /**
     * Return an extra field definition for the requested handler and variable.
     *
     * @param string $handler
     * @param string $variable
     * @param array  $fallback
     *
     * @return array
     */
    protected function getFieldInfo($handler, $variable, array $fallback = [])
    {
        $field = new ExtraField($handler);
        $data = $field->get_handler_field_info_by_field_variable($variable);

        if (!empty($data) && is_array($data)) {
            return $data;
        }

        return $fallback;
    }

    /**
     * Create the field when missing and return its current definition.
     *
     * @param string $handler
     * @param string $variable
     * @param array  $definition
     *
     * @return array
     */
    protected function ensureExtraField($handler, $variable, array $definition)
    {
        $field = new ExtraField($handler);
        $data = $field->get_handler_field_info_by_field_variable($variable);

        if (!empty($data) && is_array($data)) {
            return $data;
        }

        $id = $field->save($definition);
        $data = $field->get_handler_field_info_by_field_variable($variable);

        if (!empty($data) && is_array($data)) {
            return $data;
        }

        $definition['id'] = (int) $id;

        return $definition;
    }

    /**
     * Returns the array of an element in the database that matches the variable.
     *
     * @param string|null $variableName
     *
     * @return array
     */
    protected function getInfoExtrafield($variableName = null)
    {
        if (null === $variableName) {
            return [];
        }

        return $this->getFieldInfo('user', (string) $variableName);
    }
}
