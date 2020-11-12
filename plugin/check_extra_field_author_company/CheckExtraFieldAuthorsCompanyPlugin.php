<?php

/* For licensing terms, see /license.txt */

/**
 * Class CheckExtraFieldAuthorsCompanyPlugin.
 */
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
    protected $authorsExist;
    /**
     * @var bool
     */
    protected $companyExist;
    /**
     * @var array
     */
    protected $authorsField;
    /**
     * @var array
     */
    protected $companyField;

    /**
     * CheckExtraFieldAuthorsCompanyPlugin constructor.
     */
    public function __construct()
    {
        parent::__construct(
            '1.0',
            'Carlos Alvarado'
        );
        $this->tblExtraField = Database::get_main_table(TABLE_EXTRA_FIELD);
        $this->tblExtraFieldOption = Database::get_main_table(TABLE_EXTRA_FIELD_OPTIONS);
        $companyField = ExtraField::getDisplayNameByVariable('company');
        $this->companyExist = false;
        if (!empty($companyField)) {
            $this->companyExist = true;
        }
        $authorsField = ExtraField::getDisplayNameByVariable('authors');
        $this->authorsExist = false;
        if (!empty($authorsField)) {
            $this->authorsExist = false;
        }
        $this->authorsField = [
            'extra_field_type' => ExtraField::FIELD_TYPE_DATE,
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
        $this->companyField = [
            'extra_field_type' => ExtraField::FIELD_TYPE_TEXT,
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
        $companyExist = $this->CompanyFieldExist();
        if ($companyExist == false) {
            $this->SaveCompanyField();
            $this->setCompanyExtrafieldData();
        }
        $authorsExist = $this->AuthorsFieldExist();
        if ($authorsExist == false) {
            $this->SaveAuthorsField();
        }
    }

    /**
     * Verify that the "company" field exists in the database.
     *
     * @return bool
     */
    public function CompanyFieldExist()
    {
        $this->getCompanyField();
        $this->companyExist = (isset($this->companyField['id'])) ? true : false;

        return $this->companyExist;
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
     * Save the arrangement for company, it is adjusted internally so that the values match the necessary ones.
     */
    public function SaveCompanyField()
    {
        $data = $this->companyField;
        $data['extra_field_type'] = (int) $data['extra_field_type'];
        $data['field_type'] = (int) $data['field_type'];
        $data['field_order'] = (int) $data['field_order'];
        $data['visible_to_self'] = (int) $data['visible_to_self'];
        $data['visible_to_others'] = (int) $data['visible_to_others'];
        $data['changeable'] = (int) $data['changeable'];
        $data['filter'] = (int) $data['filter'];
        $data['default_value'] = '';
        $data['variable'] = 'company';
        $data['visible'] = 1;
        $data['display_text'] = strtolower(Database::escape_string(Security::remove_XSS($data['display_text'])));
        $schedule = new ExtraField('user');
        $schedule->save($data);
    }

    /**
     * Verify that the "authors" field exists in the database.
     *
     * @return bool
     */
    public function AuthorsFieldExist()
    {
        $this->getAuthorsField();
        $this->authorsExist = (isset($this->authorsField['id'])) ? true : false;

        return $this->authorsExist;
    }

    /**
     * Returns the content of the extra field "authors" if it exists in the database, if not, it returns an arrangement
     * with the basic elements for its operation.
     *
     * @return array
     */
    public function getAuthorsField()
    {
        $authorsField = $this->getInfoExtrafield('authors');
        if (count($authorsField) > 1) {
            $this->authorsField = $authorsField;
        } else {
            $authorsField = $this->authorsField;
        }

        return $authorsField;
    }

    /**
     * Save the arrangement for authors, it is adjusted internally so that the values match the necessary ones.
     */
    public function SaveAuthorsField()
    {
        $data = $this->authorsField;
        $data['extra_field_type'] = (int) $data['extra_field_type'];
        $data['field_type'] = (int) $data['field_type'];
        $data['field_order'] = (int) $data['field_order'];
        $data['visible_to_self'] = (int) $data['visible_to_self'];
        $data['visible_to_others'] = (int) $data['visible_to_others'];
        $data['changeable'] = (int) $data['changeable'];
        $data['filter'] = (int) $data['filter'];
        $data['default_value'] = null;
        $data['variable'] = 'authors';
        $data['visible'] = 1;
        $data['display_text'] = strtolower(Database::escape_string(Security::remove_XSS($data['display_text'])));
        $schedule = new ExtraField('lp');
        $schedule->save($data);
    }

    /**
     * Remove the extra fields set by the plugin.
     */
    public function uninstall()
    {
        $companyExist = $this->CompanyFieldExist();
        if ($companyExist == true) {
            $this->removeCompanyField();
        }
        $authorsExist = $this->AuthorsFieldExist();
        if ($authorsExist == true) {
            $this->removeAuthorsField();
        }
    }

    /**
     * Remove the extra fields "company".
     */
    public function removeCompanyField()
    {
        $data = $this->getCompanyField();
        $this->deleteQuery($data);
    }

    /**
     * Remove the extra fields "authors".
     */
    public function removeAuthorsField()
    {
        $data = $this->getAuthorsField();
        $this->deleteQuery($data);
    }

    /**
     *Insert the option fields for company with the generic values Company 1, company 2 and company 3.
     */
    public function setCompanyExtrafieldData()
    {
        $companys = [
            0 => 'Company 1',
            1 => 'Company 2',
            2 => 'Company 3',
        ];
        $order = 0;
        $query = "SELECT
                    id
                FROM
                    ".$this->tblExtraField."
                WHERE
                    variable = 'company'";
        $companyId = Database::fetch_assoc(Database::query($query));
        $companyId = isset($companyId['id']) ? $companyId['id'] : null;
        for ($i = 0; $i < count($companys); $i++) {
            $order = $i + 1;
            $extraFieldOptionValue = $companys[$i];
            if ($companyId != null) {
                $query = "SELECT * from ".$this->tblExtraFieldOption." where option_value = '$extraFieldOptionValue'";
                $extraFieldOption = Database::fetch_assoc(Database::query($query));

                if (isset($extraFieldOption['id']) && $extraFieldOption['id'] && $extraFieldOption['field_id'] == $companyId) {
                    // Update?
                } else {
                    $query = "
                    INSERT INTO ".$this->tblExtraFieldOption."
                        (`field_id`, `option_value`, `display_text`, `priority`, `priority_message`, `option_order`) VALUES
                        ( '$companyId', '$extraFieldOptionValue', '$extraFieldOptionValue', NULL, NULL, '$order');
                    ";
                    $data = Database::query($query);
                }
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
        $variableName = strtolower(Database::escape_string(Security::remove_XSS($variableName)));
        $tblExtraField = $this->tblExtraField;
        $query = "SELECT
            *
        FROM
            $tblExtraField
        WHERE
            variable = '$variableName'";
        $data = Database::fetch_assoc(Database::query($query));
        if ($data == false or !isset($data['display_text'])) {
            return [];
        }

        return $data;
    }

    /**
     * Executes fix removal for authors or company.
     *
     * @param $data
     */
    protected function deleteQuery($data)
    {
        $exist = null;
        $validVariable = false;
        $variable = $data['variable'];
        $extraFieldTypeInt = (int) $data['extra_field_type'];
        $FieldType = (int) $data['field_type'];
        $id = (int) $data['id'];
        $extraFieldType = null;
        if ($variable == 'company') {
            $validVariable = true;
            $extraFieldType = 'user';
        } elseif ($variable == 'authors') {
            $validVariable = true;
            $extraFieldType = 'lp';
        }
        if ($validVariable == true && $id != 0 && !empty($extraFieldType)) {
            $query = "SELECT
            id
        FROM
            ".$this->tblExtraField."
        WHERE
            id = $id
            AND
            variable = '$variable'
            AND
            extra_field_type = $extraFieldTypeInt
            AND
            field_type = $FieldType
            ";
            $data = Database::fetch_assoc(Database::query($query));
            if (isset($data['id'])) {
                $obj = new ExtraField($extraFieldType);
                $res = $obj->delete($data['id']);
            }
        }
    }
}
