<?php
/* For licensing terms, see /license.txt */
/**
*   This class provides methods for the notebook management.
*   Include/require it in your code to use its features.
*   @package chamilo.library
*/
/**
 * Code
 */


    
    
class Gradebook extends Model {    
    var $columns = array('id', 'name','description', 'course_code', 'parent_id');
    
    public function __construct() {
        $this->table                        = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
        $this->table_skill                  = Database::get_main_table(TABLE_MAIN_SKILL);
        $this->table_skill_rel_gradebook    = Database::get_main_table(TABLE_MAIN_SKILL_REL_GRADEBOOK);                    
    }
    
    public function update_skills_to_gradebook($gradebook_id, $skill_list) {
        
        if (!empty($skill_list)) {
            
            //Cleaning skills
            $skill_list = array_map('intval', $skill_list);
            $skill_list = array_filter($skill_list);            
            $skill_gradebook = new SkillRelGradebook();
            $skill_gradebooks_source = $skill_gradebook->get_all(array('where'=>array('gradebook_id = ?' =>$gradebook_id)));
            $clean_gradebook = array();
            if (!empty($skill_gradebooks_source)) {
                foreach($skill_gradebooks_source as $source) {
                    $clean_gradebook[]= $source['skill_id'];                    
                }
            }            
            if (!empty($clean_gradebook)) {
                $skill_to_remove = array_diff($clean_gradebook, $skill_list);
            }            
                    
            foreach ($skill_list as $skill_id) {
                $params = array();     
                $params['gradebook_id'] = $gradebook_id;
                $params['skill_id']     = $skill_id; 
                if (!$skill_gradebook->exists_gradebook_skill($gradebook_id, $skill_id)) {                    
                    $skill_gradebook->save($params);
                }
            }
            
            if (!empty($skill_to_remove)) {
                foreach($skill_to_remove as $remove) {                    
                    $skill_item = $skill_gradebook->get_skill_info($remove, $gradebook_id);
                    $skill_gradebook->delete($skill_item['id']);
                }
            }    
            return true;
        }   
        return false;     
    }
    
    
    
    /**
     * Returns a Form validator Obj
     * @todo the form should be auto generated
     * @param   string  url
     * @param   string  action add, edit
     * @return  obj     form validator obj 
     */
    public function show_skill_form($gradebook_id, $url) {
                
        $form = new FormValidator('gradebook_add_skill', 'POST', $url);
        // Settting the form elements
        $header = get_lang('Add');        
        $form->addElement('header', '', $header);
        $id = isset($_GET['id']) ? intval($_GET['id']) : '';
        $form->addElement('hidden', 'id', $id);
        
        //$status_list = $this->get_status_list();       
        $skill = new Skill();
        $skills = $skill->get_all();
        $clean_skill_list = array();
        foreach ($skills as $skill) {
            $clean_skill_list[$skill['id']] = $skill['name'];
        }   
        $form->addElement('select', 'skill', get_lang('Skills'), $clean_skill_list, array('width'=>'450px', 'class'=>'chzn-select','multiple' => 'multiple'));
        
        $selected_skills = self::get_skills_by_gradebook($gradebook_id);
        $clean_selected_skills = array();
        if (!empty($selected_skills)) {
            foreach($selected_skills as $skill) {
                $clean_selected_skills[] = $skill['id'];
            }
        }
        
        $form->addElement('style_submit_button', 'submit', get_lang('Add'), 'class="save"');
        
        $form->setDefaults(array('skill'=>$clean_selected_skills));
        return $form;                                
    }

     
    
    
    function get_children() {
    }
    
    function get_skills_by_gradebook($gradebook_id) {
        $gradebook_id = intval($gradebook_id);
        $sql = "SELECT skill.id, skill.name FROM {$this->table_skill} skill INNER JOIN {$this->table_skill_rel_gradebook} skill_rel_gradebook
                    ON skill.id = skill_rel_gradebook.skill_id 
                 WHERE skill_rel_gradebook.gradebook_id = $gradebook_id";
        $result = Database::query($sql);         
        $result = Database::store_result($result,'ASSOC');
        return $result;
    }
    
    
    /**
     * Displays the title + grid
     */
    public function display() {
        // action links
        echo '<div class="actions" style="margin-bottom:20px">';
        echo '<a href="career_dashboard.php">'.Display::return_icon('back.png',get_lang('Back'),'','32').'</a>';        
      //  echo '<a href="'.api_get_self().'?action=add">'.Display::return_icon('new_career.png',get_lang('Add'),'','32').'</a>';                      
        echo '</div>';   
        echo Display::grid_html('gradebooks');  
    }
    
    
}