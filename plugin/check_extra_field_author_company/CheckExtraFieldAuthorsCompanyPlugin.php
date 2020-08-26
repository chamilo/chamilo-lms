<?php

require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/../../main/inc/lib/extra_field.lib.php';

class CheckExtraFieldAuthorsCompanyPlugin
{
    protected $tblExtraField;
    protected $authorsExist;
    protected $companyExist;
    protected $authorsField;
    protected $companyField;

    public function __construct()
    {
        $this->tblExtraField = Database::get_main_table(TABLE_EXTRA_FIELD);

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
            'extra_field_type' => 6,
            'field_type' => 5,
            'variable' => 'authors',
            'display_text' => 'Authors',
            'default_value' => null,
            'field_order' => 0,
            'visible_to_self' => 0,
            'visible_to_others' => 0,
            'changeable' => 0,
            'filter' => 0,
        ];
        $this->companyField = [
            'extra_field_type' => 1,
            'field_type' => 3,
            'variable' => 'company',
            'display_text' => 'Company',
            'default_value' => null,
            'field_order' => 0,
            'visible_to_self' => 0,
            'visible_to_others' => 0,
            'changeable' => 0,
            'filter' => 0,
        ];
    }

    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    public function install()
    {
        $companyExist = $this->CompanyFieldExist();
        if ($companyExist == false) {
            $this->SaveCompanyField();
        }
        $authorsExist = $this->AuthorsFieldExist();
        if ($authorsExist == false) {
            $this->SaveAuthorsField();
        }
    }

    public function CompanyFieldExist()
    {
        $this->getCompanyField();
        $this->companyExist = (isset($this->companyField['id'])) ? true : false;

        return $this->companyExist;
    }

    public function getCompanyField()
    {
        $this->companyField = $this->getDbCompanyField();

        return $this->companyField;
    }

    public function setCompanyField($companyField)
    {
        $this->companyField = $companyField;

        return $this;
    }

    public function getDbCompanyField()
    {
        $companyField = $this->getInfoExtrafield('authors');
        $this->companyField = $companyField;

        return $companyField;
    }

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
        $data['default_value'] = null;
        $data['variable'] = 'company';
        $data['display_text'] = strtolower(Database::escape_string(Security::remove_XSS($data['display_text'])));

        $this->queryInsertUpdate($data);
    }

    public function AuthorsFieldExist()
    {
        $this->getAuthorsField();
        $this->authorsExist = (isset($this->authorsField['id'])) ? true : false;

        return $this->authorsExist;
    }

    public function getAuthorsField()
    {
        $this->authorsField = $this->getDbAuthorsField();

        return $this->authorsField;
    }

    public function setAuthorsField($authorsField)
    {
        $this->authorsField = $authorsField;

        return $this;
    }

    public function getDbAuthorsField()
    {
        $authorsField = $this->getInfoExtrafield('authors');

        $this->authorsField = $authorsField;

        return $authorsField;
    }

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
        $data['display_text'] = strtolower(Database::escape_string(Security::remove_XSS($data['display_text'])));

        $this->queryInsertUpdate($data);
    }

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

    public function removeCompanyField()
    {
        $data = $this->getCompanyField();
        $this->deleteQuery($data);
    }

    public function removeAuthorsField()
    {
        $data = $this->getAuthorsField();
        $this->deleteQuery($data);
    }

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

    protected function queryInsertUpdate($data)
    {
        $data['extra_field_type'] = (int) $data['extra_field_type'];
        $data['field_type'] = (int) $data['field_type'];
        $data['field_order'] = (int) $data['field_order'];
        $data['visible_to_self'] = (int) $data['visible_to_self'];
        $data['visible_to_others'] = (int) $data['visible_to_others'];
        $data['changeable'] = (int) $data['changeable'];
        $data['filter'] = (int) $data['filter'];
        $data['default_value'] = null;
        // $data['variable'] = $variable;
        $data['display_text'] = strtolower(Database::escape_string(Security::remove_XSS($data['display_text'])));
        $exist = null;
        $validVariable = false;
        $variable = $data['variable'];
        if ($variable == 'company') {
            $exist = $this->CompanyFieldExist();
            $validVariable = true;
        } elseif ($variable == 'authors') {
            $exist = $this->AuthorsFieldExist();
            $validVariable = true;
        }
        if ($exist == true) {
            $query = "
UPDATE ".$this->tblExtraField."
    SET
     `extra_field_type` = ".$data['extra_field_type'].",
     `field_type` =  ".$data['field_type'].",
     `variable` = '".$data['variable']."',
     `default_value` = ".$data['default_value'].",
     `field_order` = ".$data['field_order'].",
     `visible_to_self` = ".$data['visible_to_self'].",
     `visible_to_others` = ".$data['visible_to_others'].",
     `changeable` = ".$data['changeable'].",
     `filter` = ".$data['filter']."
    WHERE
        (`id` = ".$data['id'].");
	";
        } else {
            $query = "
INSERT INTO ".$this->tblExtraField." (
	`extra_field_type`,
	`field_type`,
	`variable`,
	`display_text`,
	`default_value`,
	`field_order`,
	`visible_to_self`,
	`visible_to_others`,
	`changeable`,
	`filter`
)
VALUES
	(
		".$data['extra_field_type'].",
		".$data['field_type'].",
		'".$data['variable']."',
		'".$data['display_text']."',
		".$data['default_value'].",
		".$data['field_order'].",
		".$data['visible_to_self'].",
		".$data['visible_to_others'].",
		".$data['changeable'].",
		".$data['filter']."
	);
	";
        }
        if ($validVariable == true) {
            Database::query($query);
        }
    }

    protected function deleteQuery($data)
    {
        $exist = null;
        $validVariable = false;
        $variable = $data['variable'];
        $extraFieldType = (int) $data['extra_field_type'];
        $FieldType = (int) $data['field_type'];
        $id = (int) $data['id'];

        if ($variable == 'company') {
            $exist = $this->CompanyFieldExist();
            $validVariable = true;
        } elseif ($variable == 'authors') {
            $exist = $this->AuthorsFieldExist();
            $validVariable = true;
        }
        if ($validVariable == true && $id != 0) {
            $query = "DELETE FROM ".$this->tblExtraField."
            WHERE
            id = $id
            AND
            variable = '$variable'
            AND
            extra_field_type = $extraFieldType
            AND
            field_type = $FieldType
            ";
            Database::query($query);
        }
    }
}
