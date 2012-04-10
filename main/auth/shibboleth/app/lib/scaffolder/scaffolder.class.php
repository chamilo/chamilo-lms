<?php

/**
 * Scaffolder. Genereate code templates from the database layout.
 * See /template/ for the code being generated
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class Scaffolder
{

    /**
     *
     * @staticvar boolean $result
     * @return Scaffolder 
     */
    public static function instance()
    {
        static $result = false;
        if (empty($result))
        {
            $result = new self();
        }
        return $result;
    }

    public function scaffold($table_name, $class_name = '', $prefix = '_')
    {
        $db_name = Database :: get_main_database();
        $sql = "SELECT * FROM `$db_name`.`$table_name` LIMIT 1";

        $fields = array();
        $unique_fields = array();
        $rs = Database::query($sql, null, __FILE__);
        while ($field = mysql_fetch_field($rs))
        {
            $fields[] = $field;
            if ($field->primary_key)
            {
                /**
                 * Could move that to an array to support multiple keys
                 */
                $id_name = $field->name;
            }
            if ($field->unique_key | $field->primary_key)
            {
                $keys[] = $field->name;
            }
        }
        $name = $table_name;
        $class_name = ucfirst($table_name);



        ob_start();
        include dirname(__FILE__) . '/template/model.php';
        $result = ob_get_clean();
        return $result;
    }

}