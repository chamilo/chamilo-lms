<?php
/* For licensing terms, see /license.txt */
/**
*	This class provides methods for the notebook management.
*	Include/require it in your code to use its features.
*	@package chamilo.library
*/
/**
 * Code
 */

/**
 * @package chamilo.library
 */
class GradeModel extends Model {
    
    var $table;
    var $columns = array('id', 'name', 'description');
    
	public function __construct() {
        $this->table =  Database::get_main_table(TABLE_GRADE_MODEL);
	}    
    
    public function get_all($where_conditions = array()) {
        return Database::select('*',$this->table, array('where'=>$where_conditions,'order' =>'name ASC'));
    }
    
    public function get_count() {        
        $row = Database::select('count(*) as count', $this->table, array(),'first');
        return $row['count'];
    }    
    
    /**
     * Displays the title + grid
     */
	public function display() {
		// action links
		echo '<div class="actions" style="margin-bottom:20px">';
        echo '<a href="grade_models.php">'.Display::return_icon('back.png',get_lang('Back'),'','32').'</a>';     	
		echo '<a href="'.api_get_self().'?action=add">'.Display::return_icon('add.png',get_lang('Add'),'','32').'</a>';        				
		echo '</div>';   
        echo Display::grid_html('grade_model');  
	}
    
    /**
     * Returns a Form validator Obj
     * @todo the form should be auto generated
     * @param   string  url
     * @param   string  action add, edit
     * @return  obj     form validator obj 
     */
    public function return_form($url, $action) {
		
		$oFCKeditor = new FCKeditor('description') ;
		$oFCKeditor->ToolbarSet = 'grade_model';
		$oFCKeditor->Width		= '100%';
		$oFCKeditor->Height		= '200';
		$oFCKeditor->Value		= '';
		$oFCKeditor->CreateHtml();
		
        $form = new FormValidator('grades', 'post', $url);
        // Settting the form elements
        $header = get_lang('Add');
        
        if ($action == 'edit') {
            $header = get_lang('Modify');
        }
        
        $form->addElement('header', $header);
        $id = isset($_GET['id']) ? intval($_GET['id']) : '';
        $form->addElement('hidden', 'id', $id);
        
        $form->addElement('text', 'name', get_lang('Name'), array('size' => '70'));
        $form->add_html_editor('description', get_lang('Description'), false, false, array('ToolbarSet' => 'careers','Width' => '100%', 'Height' => '250'));	   

        $form->addElement('label', get_lang('Components'));
        
        //Get components
        $nr_items = 2;
        $max = 10;
                
        // Setting the defaults
        
        $defaults = $this->get($id);        
                
        $components = $this->get_components($defaults['id']);
        
        if ($action == 'edit') {
            if (!empty($components)) { 
                $nr_items = count($components) -1;
            }
        }
                
        $renderer = & $form->defaultRenderer();
        
        for ($i = 0; $i <= $max;  $i++) {    
            $counter = $i;
            $form->addElement('text', 'components['.$i.'][percentage]', null, array('class' => 'span1'));                                        
            $form->addElement('text', 'components['.$i.'][acronym]',    null, array('class' => 'span1'));
            $form->addElement('text', 'components['.$i.'][title]',      null, array('class' => 'span3'));        
            $form->addElement('hidden', 'components['.$i.'][id]',       null, array('class' => 'span3'));
            
            $template_percentage =
            '<div id=' . $i . ' style="display: '.(($i<=$nr_items)?'inline':'none').';" class="control-group">
            <p><!-- BEGIN required --><span class="form_required">*</span> <!-- END required -->
            <label class="control-label">{label}</label>
            <div class="controls"><!-- BEGIN error --><span class="form_error">{error}</span><br /><!-- END error -->{element} % = ';
            
            $template_acronym = '
            <!-- BEGIN required --><span class="form_required">*</span> <!-- END required -->            
            <!-- BEGIN error --><span class="form_error">{error}</span> <!-- END error -->{element} {label}';

            $template_title =
            '<!-- BEGIN error --><span class="form_error">{error}</span><br /><!-- END error -->&nbsp{element}
             <a href="javascript:plusItem(' . ($counter+1) . ')">
                <img style="display: '.(($counter>=$nr_items)?'inline':'none').';" id="plus-' . ($counter+1) . '" src="../img/icons/22/add.png" alt="'.get_lang('Add').'" title="'.get_lang('Add').'"></img>
            </a>
            <a href="javascript:minItem(' . ($counter) . ')">
                <img style="display: '.(($counter>=$nr_items)?'inline':'none').';" id="min-' . $counter . '" src="../img/delete.png" alt="'.get_lang('Delete').'" title="'.get_lang('Delete').'"></img>
            </a>            
            </div></p></div>';
            
            $renderer->setElementTemplate($template_title, 'components['.$i.'][title]');
            $renderer->setElementTemplate($template_percentage ,  'components['.$i.'][percentage]');
            $renderer->setElementTemplate($template_acronym , 'components['.$i.'][acronym]');            
        }
        $form->addElement('advanced_settings', get_lang('AllMustWeight100'));
        	            
        if ($action == 'edit') {
        	$form->addElement('style_submit_button', 'submit', get_lang('Modify'), 'class="save"');
        } else {
        	$form->addElement('style_submit_button', 'submit', get_lang('Add'), 'class="save"');
        }

        if (!empty($components)) {
            $counter = 0;
            foreach ($components as $component) {
                foreach ($component as $key => $value) {
                    $defaults['components['.$counter.']['.$key.']'] = $value;
                }
                $counter++;
            }
        }        
        
        $form->setDefaults($defaults);
    
        // Setting the rules
        $form->addRule('name', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');               
		return $form;                                
    }
    
    public function get_components($id) {       
        $obj = new GradeModelComponents();
        if (!empty($id)) {
            return $obj->get_all(array('where'=> array('grade_model_id = ?' => $id)));                
        } 
        return null;
    }
        
    public function save($params, $show_query = false) {        
	    $id = parent::save($params, $show_query);
	    if (!empty($id)) {            
            foreach ($params['components'] as $component) {                
                if (!empty($component['title']) && !empty($component['percentage']) && !empty($component['acronym'])) {
                    $obj = new GradeModelComponents();
                    $component['grade_model_id'] = $id;
                    $obj->save($component);
                }
            }                            
        }
        //event_system(LOG_CAREER_CREATE, LOG_CAREER_ID, $id, api_get_utc_datetime(), api_get_user_id());   		
   		return $id;
    }
    
    public function update($params) {
        parent::update($params);
        
        if (!empty($params['id'])) {
            foreach ($params['components'] as $component) {   
                $obj = new GradeModelComponents();
                $component['grade_model_id'] = $params['id'];
                if (empty($component['title']) && empty($component['percentage']) && empty($component['acronym'])) {
                    $obj->delete($component['id']);
                } else {
                    $obj->update($component);
                }
            }
        }        
        //$params['components']
    }
    
    public function delete($id) {
	    parent::delete($id);
	    //event_system(LOG_CAREER_DELETE, LOG_CAREER_ID, $id, api_get_utc_datetime(), api_get_user_id());
    }    
}

class GradeModelComponents extends Model {
    var $table;
    var $columns = array('id', 'title', 'percentage', 'acronym', 'grade_model_id');
    
	public function __construct() {
        $this->table =  Database::get_main_table(TABLE_GRADE_MODEL_COMPONENTS);
	}    
    
    public function save($params, $show_query = false) {        
	    $id = parent::save($params, $show_query);  
        return $id;
    }
}