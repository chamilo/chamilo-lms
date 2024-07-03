<?php

/* For licensing terms, see /license.txt */

class CheckExtraFieldAuthorsCompanyPlugin extends Plugin
{
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
    protected $companyExists;

    /**
     * @var bool
     */
    protected $authorsExists;

    /**
     * @var bool
     */
    protected $priceExists;

    /**
     * @var bool
     */
    protected $authorLpItemExists;

    /**
     * @var bool
     */
    protected $authorLpExists;

    /**
     * @var array
     */
    protected $companyField;

    /**
     * @var array
     */
    protected $authorsField;

    /**
     * @var array
     */
    protected $priceField;

    /**
     * @var array
     */
    protected $authorLpItemField;

    /**
     * @var array
     */
    protected $authorLpField;

    public function __construct()
    {
        parent::__construct(
            '1.2',
            'Carlos Alvarado, Julio Montoya'
        );
        $this->tblExtraField = Database::get_main_table(TABLE_EXTRA_FIELD);
        $this->tblExtraFieldOption = Database::get_main_table(TABLE_EXTRA_FIELD_OPTIONS);
        $field = new ExtraField('user');
        $companyField = $field->get_handler_field_info_by_field_variable('company');
        $this->companyExists = false;
        if (!empty($companyField)) {
            $this->companyExists = true;
            $this->companyField = $companyField;
        } else {
            $this->companyField = [
                'field_type' => ExtraField::FIELD_TYPE_RADIO,
                'variable' => 'company',
                'display_text' => 'Company',
                'default_value' => '',
                'field_order' => 0,
                'visible_to_self' => 0,
                'visible_to_others' => 0,
                'changeable' => 1,
                'filter' => 1,
            ];
        }
        $field = new ExtraField('lp');
        $authorsField = $field->get_handler_field_info_by_field_variable('authors');

        $this->authorsExists = false;
        if (empty($authorsField)) {
            $this->authorsExists = true;
            $this->authorsField = $authorsField;
        }
    }

    /**
     * Create a new instance of CheckExtraFieldAuthorsCompanyPlugin.
     *
     * @return CheckExtraFieldAuthorsCompanyPlugin
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
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
     * Save the arrangement for company, it is adjusted internally so that the values match the necessary ones.
     */
    public function saveCompanyField()
    {
        $data = $this->companyField;
        if (!empty($data)) {
            $data['field_type'] = (int) $data['field_type'];
            $data['field_order'] = (int) $data['field_order'];
            $data['visible_to_self'] = (int) $data['visible_to_self'];
            $data['visible_to_others'] = (int) $data['visible_to_others'];
            $data['changeable'] = (int) $data['changeable'];
            $data['filter'] = (int) $data['filter'];
            $data['default_value'] = '';
            $data['variable'] = 'company';
            $data['visible'] = 1;
            $data['display_text'] = strtolower(Database::escape_string($data['display_text']));
            $schedule = new ExtraField('user');
            $this->companyField['id'] = $schedule->save($data);
        }
    }

    /**
     * Insert the option fields for company with the generic values Company 1, company 2 and company 3.
     */
    public function setCompanyExtrafieldData()
    {
        $companies = [
            0 => 'Company 1',
            1 => 'Company 2',
            2 => 'Company 3',
        ];
        $companyId = (int) $this->companyField['id'];
        if ($companyId != 0) {
            for ($i = 0; $i < count($companies); $i++) {
                $order = $i + 1;
                $extraFieldOptionValue = $companies[$i];
                if ($companyId != null) {
                    $query = "SELECT *
                              FROM ".$this->tblExtraFieldOption."
                              WHERE
                                    option_value = '$extraFieldOptionValue' AND
                                    field_id = $companyId";
                    $extraFieldOption = Database::fetch_assoc(Database::query($query));
                    if (isset($extraFieldOption['id']) && $extraFieldOption['id'] && $extraFieldOption['field_id'] == $companyId) {
                        // Update?
                    } else {
                        $query = "
                        INSERT INTO ".$this->tblExtraFieldOption."
                            (`field_id`, `option_value`, `display_text`, `priority`, `priority_message`, `option_order`) VALUES
                            ( '$companyId', '$extraFieldOptionValue', '$extraFieldOptionValue', NULL, NULL, '$order');
                        ";
                        Database::query($query);
                    }
                }
            }
        }
    }

    /**
     * Save the arrangement for authors, it is adjusted internally so that the values match the necessary ones.
     */
    public function saveAuthorsField()
    {
        $data = [
            'field_type' => ExtraField::FIELD_TYPE_SELECT_MULTIPLE,
            'variable' => 'authors',
            'display_text' => 'Authors',
            'default_value' => '',
            'field_order' => 0,
            'visible_to_self' => 1,
            'visible_to_others' => 0,
            'changeable' => 1,
            'filter' => 1,
        ];
        $schedule = new ExtraField('lp');
        $schedule->save($data);
    }

    /**
     * Save the arrangement for price, it is adjusted internally so that the values match the necessary ones.
     */
    public function savePrice()
    {
        $schedule = new ExtraField('lp_item');
        $data = [];
        $data['visible_to_self'] = 1;
        $data['visible_to_others'] = 1;
        $data['changeable'] = 1;
        $data['filter'] = 0;
        $data['variable'] = 'price';
        $data['display_text'] = 'SalePrice';
        $data['field_type'] = ExtraField::FIELD_TYPE_INTEGER;

        $schedule->save($data);
    }

    /**
     * Save the arrangement for AuthorLPItem, it is adjusted internally so that the values match the necessary ones.
     */
    public function saveAuthorLPItem()
    {
        $schedule = new ExtraField('lp_item');
        $data = [];
        $data['visible_to_self'] = 1;
        $data['visible_to_others'] = 0;
        $data['changeable'] = 1;
        $data['filter'] = 0;
        $data['variable'] = 'authorlpitem';
        $data['display_text'] = 'LearningPathItemByAuthor';
        $data['field_type'] = ExtraField::FIELD_TYPE_SELECT_MULTIPLE;
        $schedule->save($data);
    }

    /**
     * Save the arrangement for authorlp, it is adjusted internally so that the values match the necessary ones.
     */
    public function saveAuthorLp()
    {
        $schedule = new ExtraField('user');
        $data = [];
        $data['variable'] = 'authorlp';
        $data['display_text'] = 'authors';
        $data['changeable'] = 1;
        $data['visible_to_self'] = 1;
        $data['visible_to_others'] = 0;
        $data['filter'] = 0;
        $data['field_type'] = ExtraField::FIELD_TYPE_CHECKBOX;
        $schedule->save($data);
    }

    /**
     * Remove the extra fields set by the plugin.
     */
    public function uninstall()
    {
        $companyExists = $this->companyFieldExists();
        if ($companyExists == true) {
            // $this->removeCompanyField();
        }
        $authorsExists = $this->authorsFieldExists();
        if ($authorsExists == true) {
            // $this->removeAuthorsField();
        }
        $priceExists = $this->priceFieldExists();
        if ($priceExists == true) {
            // $this->>removePriceField();
        }
        $authorLpItemExists = $this->authorLpItemFieldExists();
        if ($authorLpItemExists == true) {
            // $this->removeAuthorLpItemField();
        }
        $authorLpExists = $this->authorLpFieldExists();
        if ($authorLpExists == true) {
            // $this->removeAuthorLpField();
        }
    }

    /**
     * Verify that the "company" field exists in the database.
     */
    public function companyFieldExists(): bool
    {
        $this->getCompanyField();
        $this->companyExists = (isset($this->companyField['id'])) ? true : false;

        return $this->companyExists;
    }

    /**
     * Returns the content of the extra field "company" if it exists in the database, if not, it returns an arrangement
     * with the basic elements for its operation.
     *
     * @return array
     */
    public function getCompanyField()
    {
        $companyField = $this->getInfoExtrafield('company');
        if (count($companyField) > 1) {
            $this->companyField = $companyField;
        } else {
            $companyField = $this->companyField;
        }

        return $companyField;
    }

    /**
     * Verify that the "authors" field exists in the database.
     */
    public function authorsFieldExists(): bool
    {
        $this->getAuthorsField();
        $this->authorsExists = (isset($this->authorsField['id'])) ? true : false;

        return $this->authorsExists;
    }

    /**
     * Returns the content of the extra field "authors" if it exists in the database, if not, it returns an arrangement
     * with the basic elements for its operation.
     *
     * @return array
     */
    public function getAuthorsField()
    {
        $schedule = new ExtraField('lp');
        $data = $schedule->get_handler_field_info_by_field_variable('authors');
        if (empty($data)) {
            $this->authorsField = $data;
        } else {
            $data = $this->authorsField;
        }

        return $data;
    }

    /**
     * Verify that the "price" field exists in the database.
     */
    public function priceFieldExists(): bool
    {
        $this->getPriceField();
        $this->priceExists = (isset($this->priceField['id'])) ? true : false;

        return $this->priceExists;
    }

    /**
     * Returns the content of the extra field "price" if it exists in the database, if not, it returns an arrangement
     * with the basic elements for its operation.
     *
     * @return array
     */
    public function getPriceField()
    {
        $schedule = new ExtraField('lp_item');
        $data = $schedule->get_handler_field_info_by_field_variable('price');
        if (empty($data)) {
            $this->priceField = $data;
        } else {
            $data = $this->priceField;
        }

        return $data;
    }

    /**
     * Verify that the "authorlpitem" field exists in the database.
     */
    public function authorLpItemFieldExists(): bool
    {
        $this->getAuthorLpItemField();
        $this->authorLpItemExists = (isset($this->authorLpItemField['id'])) ? true : false;

        return $this->authorLpItemExists;
    }

    /**
     * Returns the content of the extra field "authorlpitem" if it exists in the database, if not, it returns an arrangement
     * with the basic elements for its operation.
     *
     * @return array
     */
    public function getAuthorLpItemField()
    {
        $schedule = new ExtraField('lp_item');
        $data = $schedule->get_handler_field_info_by_field_variable('authorlpitem');
        if (empty($data)) {
            $this->authorLpItemField = $data;
        } else {
            $data = $this->authorLpItemField;
        }

        return $data;
    }

    /**
     * Verify that the "authorlp" field exists in the database.
     */
    public function authorLpFieldExists(): bool
    {
        $this->getAuthorLpField();
        $this->authorLpExists = (isset($this->authorLpField['id'])) ? true : false;

        return $this->authorLpExists;
    }

    /**
     * Returns the content of the extra field "authorlp" if it exists in the database, if not, it returns an arrangement
     * with the basic elements for its operation.
     *
     * @return array
     */
    public function getAuthorLpField()
    {
        $field = new ExtraField('user');
        $data = $field->get_handler_field_info_by_field_variable('authorlp');
        if (empty($data)) {
            $this->authorLpField = $data;
        } else {
            $data = $this->authorLpField;
        }

        return $data;
    }

    /**
     * Remove the extra fields "company".
     */
    public function removeCompanyField()
    {
        $data = $this->getCompanyField();
        // $this->deleteQuery($data);
    }

    /**
     * Remove the extra fields "authors".
     */
    public function removeAuthorsField()
    {
        $data = $this->getAuthorsField();
        // $this->deleteQuery($data);
    }

    /**
     * Remove the extra fields "price".
     */
    public function removePriceField()
    {
        $data = $this->getPriceField();
        // $this->deleteQuery($data);
    }

    /**
     * Remove the extra fields "authorlpitem".
     */
    public function removeAuthorLpItemField()
    {
        $data = $this->getAuthorLpItemField();
        // $this->deleteQuery($data);
    }

    /**
     * Remove the extra fields "authorlp".
     */
    public function removeAuthorLpField()
    {
        $data = $this->getAuthorLpField();
        // $this->deleteQuery($data);
    }

    /**
     * Executes fix removal for authors or company.
     *
     * @param $data
     */
    protected function deleteQuery($data)
    {
        $validVariable = false;
        $variable = $data['variable'];
        $extraFieldTypeInt = (int) $data['extra_field_type'];
        $FieldType = (int) $data['field_type'];
        $id = (int) $data['id'];
        $extraFieldType = null;
        switch ($variable) {
            case 'company':
            case 'authorlp':
                $validVariable = true;
                $extraFieldType = 'user';
                break;
            case 'authors':
                $validVariable = true;
                $extraFieldType = 'lp';
                break;
            case 'price':
            case 'authorlpitem':
                $validVariable = true;
                $extraFieldType = 'lp_item';
                break;
        }
        if ($variable === 'company') {
        } elseif ($variable === 'authors') {
        }
        if ($validVariable == true && $id != 0 && !empty($extraFieldType)) {
            $query = "SELECT id
                        FROM
                            ".$this->tblExtraField."
                        WHERE
                            id = $id AND
                            variable = '$variable' AND
                            extra_field_type = $extraFieldTypeInt AND
                            field_type = $FieldType
                        ";
            $data = Database::fetch_assoc(Database::query($query));
            if (isset($data['id'])) {
                $obj = new ExtraField($extraFieldType);
                $obj->delete($data['id']);
            }
        }
    }

    /**
     * Returns the array of an element in the database that matches the variable.
     *
     * @param string $variableName
     *
     * @return array
     */
    protected function getInfoExtrafield($variableName = null)
    {
        if ($variableName == null) {
            return [];
        }
        $variableName = strtolower(Database::escape_string($variableName));
        $tblExtraField = $this->tblExtraField;
        $query = "SELECT * FROM $tblExtraField WHERE variable = '$variableName'";
        $data = Database::fetch_assoc(Database::query($query));
        if ($data == false || !isset($data['display_text'])) {
            return [];
        }

        return $data;
    }
}
