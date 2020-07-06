<?php
/* For licensing terms, see /license.txt */

/**
 * Class Gradebook.
 * This class provides methods for the notebook management.
 * Include/require it in your code to use its features.
 */
class Gradebook extends Model
{
    public $columns = [
        'id',
        'name',
        'description',
        'course_code',
        'parent_id',
        'grade_model_id',
        'session_id',
        'weight',
        'user_id',
    ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
        $this->table_skill = Database::get_main_table(TABLE_MAIN_SKILL);
        $this->table_skill_rel_gradebook = Database::get_main_table(TABLE_MAIN_SKILL_REL_GRADEBOOK);
    }

    /**
     * Returns true if the gradebook is active and visible in a course, false
     * otherwise.
     *
     * @param int $c_id Course integer id, defaults to the current course
     *
     * @return bool
     */
    public static function is_active($c_id = null)
    {
        $name = 'gradebook';
        $table = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
        $sql = "SELECT * from $table
                WHERE variable='course_hide_tools' AND subkey='$name'
                LIMIT 1";
        $result = Database::query($sql);
        $setting = Database::store_result($result);
        $setting = isset($setting[0]) ? $setting[0] : null;
        $setting = $setting ? $setting : [];
        $inactive = isset($setting['selected_value']) && 'true' == $setting['selected_value'];

        if ($inactive) {
            return false;
        }

        $c_id = $c_id ? intval($c_id) : api_get_course_int_id();
        $table = Database::get_course_table(TABLE_TOOL_LIST);
        $sql = "SELECT * from $table
                WHERE c_id = $c_id and name='$name'
                LIMIT 1";
        $result = Database::query($sql);
        $item = Database::store_result($result, 'ASSOC');
        $item = isset($item[0]) ? $item[0] : null;
        if (empty($item)) {
            return true;
        }

        return '1' == $item['visibility'];
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public function get_all($options = [])
    {
        $gradebooks = parent::get_all($options);
        foreach ($gradebooks as &$gradebook) {
            if (empty($gradebook['name'])) {
                $gradebook['name'] = $gradebook['course_code'];
            }
        }

        return $gradebooks;
    }

    /**
     * @param int   $gradebook_id
     * @param array $skill_list
     * @param bool  $deleteSkillNotInList
     *
     * @return bool
     */
    public function updateSkillsToGradeBook(
        $gradebook_id,
        $skill_list,
        $deleteSkillNotInList = true
    ) {
        $skill_gradebook = new SkillRelGradebook();
        $skill_gradebooks_source = $skill_gradebook->get_all(
            ['where' => ['gradebook_id = ?' => $gradebook_id]]
        );
        $clean_gradebook = [];

        if (!empty($skill_gradebooks_source)) {
            foreach ($skill_gradebooks_source as $source) {
                $clean_gradebook[] = $source['skill_id'];
            }
        }

        //Cleaning skills
        if (!empty($skill_list)) {
            $skill_list = array_map('intval', $skill_list);
            $skill_list = array_filter($skill_list);
        }

        if (!empty($skill_list)) {
            if (!empty($clean_gradebook)) {
                $skill_to_remove = array_diff($clean_gradebook, $skill_list);
            }

            foreach ($skill_list as $skill_id) {
                $params = [];
                $params['gradebook_id'] = $gradebook_id;
                $params['skill_id'] = $skill_id;
                if (!$skill_gradebook->existsGradeBookSkill($gradebook_id, $skill_id)) {
                    $skill_gradebook->save($params);
                }
            }
        } else {
            $skill_to_remove = $clean_gradebook;
        }

        if ($deleteSkillNotInList) {
            if (!empty($skill_to_remove)) {
                foreach ($skill_to_remove as $remove) {
                    $skill_item = $skill_gradebook->getSkillInfo(
                        $remove,
                        $gradebook_id
                    );
                    $skill_gradebook->delete($skill_item['id']);
                }
            }
        }

        return false;
    }

    /**
     * Returns a Form validator Obj.
     *
     * @todo the form should be auto generated
     *
     * @param   string  url
     * @param   string  action add, edit
     *
     * @return FormValidator
     */
    public function show_skill_form($gradebook_id, $url, $header = null)
    {
        $form = new FormValidator('gradebook_add_skill', 'POST', $url);
        // Setting the form elements
        if (!isset($header)) {
            $header = get_lang('Add');
        }
        $id = isset($_GET['id']) ? intval($_GET['id']) : '';

        $form->addHeader($header);
        $form->addElement('hidden', 'id', $id);

        $skill = new Skill();
        $skills = $skill->get_all();
        $clean_skill_list = [];
        foreach ($skills as $skill) {
            $clean_skill_list[$skill['id']] = $skill['name'];
        }
        $form->addElement(
            'select',
            'skill',
            get_lang('Skills'),
            $clean_skill_list,
            [
                'multiple' => 'multiple',
            ]
        );

        $selected_skills = self::getSkillsByGradebook($gradebook_id);
        $clean_selected_skills = [];
        if (!empty($selected_skills)) {
            foreach ($selected_skills as $skill) {
                $clean_selected_skills[] = $skill['id'];
            }
        }

        $form->addButtonCreate(get_lang('Add'), 'submit');
        $form->setDefaults(['skill' => $clean_selected_skills]);

        return $form;
    }

    /**
     * @param int $gradebook_id
     *
     * @return array|resource
     */
    public function getSkillsByGradebook($gradebook_id)
    {
        $gradebook_id = intval($gradebook_id);
        $sql = "SELECT skill.id, skill.name 
                FROM {$this->table_skill} skill
                INNER JOIN {$this->table_skill_rel_gradebook} skill_rel_gradebook
                ON skill.id = skill_rel_gradebook.skill_id
                WHERE skill_rel_gradebook.gradebook_id = $gradebook_id";
        $result = Database::query($sql);
        $result = Database::store_result($result, 'ASSOC');

        return $result;
    }

    /**
     * Displays the title + grid.
     */
    public function display()
    {
        // action links
        echo self::returnGrid();
    }

    /**
     * Displays the title + grid.
     */
    public function returnGrid()
    {
        // action links
        return Display::grid_html('gradebooks');
    }
}
