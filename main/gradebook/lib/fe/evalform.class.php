<?php
/* For licensing terms, see /license.txt */
require_once dirname(__FILE__).'/../../../inc/global.inc.php';
require_once dirname(__FILE__).'/../be.inc.php';
require_once dirname(__FILE__).'/../gradebook_functions.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'groupmanager.lib.php';
require_once api_get_path(LIBRARY_PATH) . 'formvalidator/FormValidator.class.php';
/**
 * Extends formvalidator with add&edit forms for evaluations
 * @author Stijn Konings
 * @package dokeos.gradebook
 */

$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.js" type="text/javascript" language="javascript"></script>'; //jQuery
$htmlHeadXtra[] = '<script type="text/javascript">
function setFocus(){
$("#evaluation_title").focus();
}
$(document).ready(function () {
  setFocus();
});
</script>';


class EvalForm extends FormValidator
{
	const TYPE_ADD= 1;
	const TYPE_EDIT= 2;
	const TYPE_MOVE= 3;
	const TYPE_RESULT_ADD= 4;
	const TYPE_RESULT_EDIT= 5;
	const TYPE_ALL_RESULTS_EDIT= 6;
	const TYPE_ADD_USERS_TO_EVAL= 7;

	private $evaluation_object;
	private $result_object;
	private $extra;

	/**
	 * Builds a form containing form items based on a given parameter
	 * @param int form_type 1=add, 2=edit,3=move,4=result_add
	 * @param obj cat_obj the category object
	 * @param obj res_obj the result object
	 * @param string form name
	 * @param method
	 * @param action
	 */
	function EvalForm($form_type, $evaluation_object, $result_object, $form_name, $method= 'post', $action= null, $extra1 = null, $extra2 = null)
	{
		parent :: __construct($form_name, $method, $action);

		if (isset ($evaluation_object)) {
			$this->evaluation_object= $evaluation_object;
		}
		if (isset ($result_object)) {
			$this->result_object= $result_object;
		}
		if (isset ($extra1)) {
			$this->extra = $extra1;
		}		
		if ($form_type == self :: TYPE_EDIT) {
			$this->build_editing_form();
		} elseif ($form_type == self :: TYPE_ADD) {
			$this->build_add_form();
		} elseif ($form_type == self :: TYPE_MOVE) {
			$this->build_move_form();
		} elseif ($form_type == self :: TYPE_RESULT_ADD) {
			$this->build_result_add_form();
		} elseif ($form_type == self :: TYPE_RESULT_EDIT) {
			$this->build_result_edit_form();
		} elseif ($form_type == self :: TYPE_ALL_RESULTS_EDIT) {
			$this->build_all_results_edit_form();
		} elseif ($form_type == self :: TYPE_ADD_USERS_TO_EVAL) {
			$this->build_add_user_to_eval();
		}
		$this->setDefaults();
	}
	/**
	 * This form will build a form to add users to an evaluation
	 */
	protected function build_add_user_to_eval() {
		//$this->addElement('hidden', 'formSent');
		$this->addElement('header','label',get_lang('ChooseUser'));
		$select= $this->addElement('select', 'firstLetterUser', get_lang('FirstLetter'), null, array(
			'onchange'=> 'document.add_users_to_evaluation.submit()'
		));

		$result = '';
		$select->addOption('','');
		for ($i = 65; $i <= 90; $i ++) {
			$letter = chr($i);
			if (isset($this->extra) && $this->extra == $letter) {
				$select->addOption($letter,$letter,'selected');
			} else {
				$select->addOption($letter,$letter);
			}
		}
		$select= $this->addElement('select', 'add_users', null, null, array (
			'multiple' => 'multiple',
			'size' => '15',
			'style' => 'width:250px'
		));
		foreach ($this->evaluation_object->get_not_subscribed_students() as $user) {
			if ( (!isset($this->extra)) || empty($this->extra) || api_strtoupper(api_substr($user[1],0,1)) == $this->extra ) {
				$select->addoption($user[1] . ' ' . $user[2] . ' (' . $user[3] . ')', $user[0]);
			}
		}
		$this->addElement('submit', 'submit_button', get_lang('AddUserToEval'));
//		$this->setDefaults(array (
//			'formSent' => '1'
//		));

	}
	/**
	 * This function builds a form to edit all results in an evaluation
	 */
	protected function build_all_results_edit_form() {
		//extra field for check on maxvalue
		$this->addElement('hidden', 'maxvalue', $this->evaluation_object->get_max());
		$this->addElement('hidden', 'minvalue', 0);
		$this->addElement('header','h1','<b>'.get_lang('EditResult').'</b>');
		$renderer = $this->defaultRenderer();				
			
		$renderer->setFormTemplate(
		   '<form{attributes}>
		      <table class="data_table" border="0" cellpadding="5" cellspacing="5">{content}
		      </table>		      
		   </form>'
		);
        
        if (api_is_western_name_order()){
    		$renderer->setHeaderTemplate(
    		   '<tr>
    		       <th>'.get_lang('OfficialCode').'</th>		
    		      <th>'.get_lang('UserName').'</th>
    		      <th>'.get_lang('FirstName').'</th>
    		      <th>'.get_lang('LastName').'</th>		
    		      <th>'.get_lang('Qualify').'</th>
    		   </tr>'  
    		);
        } else {
        	$renderer->setHeaderTemplate(
               '<tr>
                   <th>'.get_lang('OfficialCode').'</th>        
                  <th>'.get_lang('UserName').'</th>
                  <th>'.get_lang('LastName').'</th>
                  <th>'.get_lang('FirstName').'</th>     
                  <th>'.get_lang('Qualify').'</th>
               </tr>'  
            );
        }   
		$template_submit =  '<tr>
			       <td colspan="4" ></td>			         
			      <td >
			       {element}
			         <!-- BEGIN error --><br /><span style="color: #ff0000;font-size:10px">{error}</span><!-- END error -->
			      </td>
			   </tr>';	

		$results_and_users = array();
		foreach ($this->result_object as $result) {
			$user= get_user_info_from_id($result->get_user_id());
			$results_and_users[] = array ('result' => $result, 'user' => $user);
		}        
		usort($results_and_users, array ('EvalForm', 'sort_by_user'));

		$defaults= array ();
		
		foreach ($results_and_users as $result_and_user) {
			$user = $result_and_user['user'];
			$result = $result_and_user['result'];

			$renderer =& $this->defaultRenderer();
			$this->add_textfield('score[' . $result->get_id() . ']',
								 $this->build_stud_label($user['user_id'], $user['username'], $user['lastname'], $user['firstname']),
								 false,
								 array ('size' => 4,
										'maxlength' => 5));

			$this->addRule('score[' . $result->get_id() . ']', get_lang('OnlyNumbers'), 'numeric');
			$this->addRule(array (
			'score[' . $result->get_id() . ']', 'maxvalue'), get_lang('OverMax'), 'compare', '<=');
			$this->addRule(array (
			'score[' . $result->get_id() . ']', 'minvalue'), get_lang('UnderMin'), 'compare', '>=');
			$defaults['score[' . $result->get_id() . ']']= $result->get_score();
			
            if (api_is_western_name_order() ) {
            	$user_info = '<td align="left" >'.$user['firstname'].'</td>';
                $user_info .= '<td align="left" >'.$user['lastname'].'</td>';
            } else {
                $user_info = '<td align="left" >'.$user['lastname'].'</td>';
            	$user_info .= '<td align="left" >'.$user['firstname'].'</td>';                
            }
			
			$template =  '<tr>
		      <td align="left" >'.$user['official_code'].'</td>
		      <td align="left" >'.$user['username'].'</td>		      
		      '.$user_info.'
		       <td align="left">{element} / '.$this->evaluation_object->get_max().'
		         <!-- BEGIN error --><br /><span style="color: #ff0000;font-size:10px">{error}</span><!-- END error -->
		      </td>
		   </tr>';	
		   $renderer->setElementTemplate($template,'score[' . $result->get_id() . ']');		   
		}
		$this->setDefaults($defaults);
		$this->addElement('style_submit_button', 'submit',get_lang('EditResult'),'class="save"');
		$renderer->setElementTemplate($template_submit,'submit');
	}
	/**
	 * This function builds a form to move an item to another category
	 *
	 */
	protected function build_move_form() {
		$renderer =& $this->defaultRenderer();
		$renderer->setElementTemplate('<span>{element}</span> ');
		$this->addElement('static', null, null, '"'.$this->evaluation_object->get_name().'" ');
		$this->addElement('static', null, null, get_lang('MoveTo').' : ');
		$select= $this->addElement('select', 'move_cat', null, null);
		foreach ($this->evaluation_object->get_target_categories() as $cat) {
			for ($i= 0; $i < $cat[2]; $i++) {
				$line .= '&mdash;';
			}
			$select->addoption($line . ' ' . $cat[1], $cat[0]);
			$line= '';
		}
		$this->addElement('style_submit_button' , 'submit', get_lang('Ok'),'class="save"');
	}
	/**
	 * Builds a result form containing inputs for all students with a given course_code
	 */
	protected function build_result_add_form() {
		$tblusers= get_users_in_course($this->evaluation_object->get_course_code());
		$nr_users= 0;
		//extra field for check on maxvalue
		$this->addElement('hidden', 'maxvalue', $this->evaluation_object->get_max());
		$this->addElement('hidden', 'minvalue', 0);
		$this->addElement('header','h1','<b>'.get_lang('AddResult').'</b>');

		$renderer = $this->defaultRenderer();		
			
		$renderer->setFormTemplate(
		   '<form{attributes}>
		      <table class="data_table" border="0" cellpadding="5" cellspacing="5">{content}
		      </table>		      
		   </form>'
		);

        if (api_is_western_name_order()){
            $renderer->setHeaderTemplate(
               '<tr>
                   <th>'.get_lang('OfficialCode').'</th>        
                  <th>'.get_lang('UserName').'</th>
                  <th>'.get_lang('FirstName').'</th>
                  <th>'.get_lang('LastName').'</th>     
                  <th>'.get_lang('Qualify').'</th>
               </tr>'  
            );
        } else {
            $renderer->setHeaderTemplate(
               '<tr>
                   <th>'.get_lang('OfficialCode').'</th>        
                  <th>'.get_lang('UserName').'</th>
                  <th>'.get_lang('LastName').'</th>
                  <th>'.get_lang('FirstName').'</th>     
                  <th>'.get_lang('Qualify').'</th>
               </tr>'  
            );
        }   
         
		$template_submit =  '<tr>
			       <td colspan="4" ></td>			         
			      <td >
			       {element}
			         <!-- BEGIN error --><br /><span style="color: #ff0000;font-size:10px">{error}</span><!-- END error -->
			      </td>
			   </tr>';	
			   
		foreach ($tblusers as $user) {
			
			//user_id, user.username, lastname, firstname
			$this->add_textfield('score[' . $user[0] . ']',
								 $this->build_stud_label($user[0], $user[1], $user[2], $user[3]),
								 false,
								 array ('size' => 4,'maxlength' => 5));
			$this->addRule('score[' . $user[0] . ']', get_lang('OnlyNumbers'), 'numeric');
			$this->addRule(array (
				'score[' . $user[0] . ']',
				'maxvalue'
			), get_lang('OverMax'), 'compare', '<=');
			$this->addRule(array (
				'score[' . $user[0] . ']',
				'minvalue'
			), get_lang('UnderMin'), 'compare', '>=');
            
            
            if (api_is_western_name_order() ) {
                $user_info = '<td align="left" >'.$user[3].'</td>';
                $user_info .= '<td align="left" >'.$user[2].'</td>';
            } else {
                $user_info = '<td align="left" >'.$user[2].'</td>';
                $user_info .= '<td align="left" >'.$user[3].'</td>';                
            }           
			
			$template =  '<tr>
		      <td align="left" >'.$user[4].'</td>
		      <td align="left" >'.$user[1].'</td>
		      '.$user_info.'
		       <td align="left">{element} / '.$this->evaluation_object->get_max().'
		         <!-- BEGIN error --><br /><span style="color: #ff0000;font-size:10px">{error}</span><!-- END error -->
		      </td>
		   </tr>';	
           
           
			   

			$renderer->setElementTemplate($template, 'score[' . $user[0] . ']');
			$nr_users++;
		}
		
		$this->addElement('hidden', 'nr_users', $nr_users);
		$this->addElement('hidden', 'evaluation_id', $this->result_object->get_evaluation_id());		
		$this->addElement('style_submit_button', 'submit', get_lang('AddResult'),'class="save"');
		
		$renderer->setElementTemplate($template_submit,'submit');
	}
	/**
	 * Builds a form to edit a result
	 */
	protected function build_result_edit_form() {
		$this->setDefaults(array (
		'score' => $this->result_object->get_score(),
		'maximum' => $this->evaluation_object->get_max()
		));
		$userinfo= api_get_user_info($this->result_object->get_user_id());
		$renderer =& $this->defaultRenderer();
		$renderer->setElementTemplate('<span>{element}</span> ');
		$this->addElement('static', null, null, api_get_person_name($userinfo['lastName'], $userinfo['firstName']));
		$this->add_textfield('score', get_lang('Result'), false, array (
			'size' => '4',
			'maxlength' => '5'
		));
		$this->addElement('static', null, null,'/');

		$this->add_textfield('maximum', null, false, array (
			'size' => '4',
			'maxlength' => '5',
			'disabled' => 'disabled'
		));
		$this->addElement('style_submit_button', 'submit', get_lang('Edit'),'class="save"');
		$this->addElement('hidden', 'minvalue', 0);
		$this->addElement('hidden', 'hid_user_id', $this->result_object->get_user_id());
		$this->addElement('hidden', 'maxvalue', $this->evaluation_object->get_max());
		$this->addRule('score', get_lang('OnlyNumbers'), 'numeric',null,'client');
		$this->addRule(array (
			'score',
			'maxvalue'
		), get_lang('OverMax'), 'compare', '<=','client');
		$this->addRule(array (
			'score',
			'minvalue'
		), get_lang('UnderMin'), 'compare', '>=','client');
	}
	/**
	 * Builds a form to add an evaluation
	 */
	protected function build_add_form() {
		$this->setDefaults(array (
		'hid_user_id' => $this->evaluation_object->get_user_id(), 'hid_category_id' => $this->evaluation_object->get_category_id(), 'hid_course_code' => $this->evaluation_object->get_course_code(), 'created_at' => api_get_utc_datetime()));
		$this->build_basic_form(0);
		if ($this->evaluation_object->get_course_code() == null) {
			$this->addElement('checkbox', 'adduser', get_lang('AddUserToEval'));
		} else {
			$this->addElement('checkbox', 'addresult', get_lang('AddResult'));
		}
		$this->addElement('style_submit_button', 'submit', get_lang('AddAssessment'),'class="add"');
	}
	/**
	 * Builds a form to edit an evaluation
	 */
	protected function build_editing_form() {
		$this->setDefaults(array (
		'hid_id' => $this->evaluation_object->get_id(), 'name' => $this->evaluation_object->get_name(), 'description' => $this->evaluation_object->get_description(), 'hid_user_id' => $this->evaluation_object->get_user_id(), 'hid_course_code' => $this->evaluation_object->get_course_code(), 'hid_category_id' => $this->evaluation_object->get_category_id(), 'created_at' => api_get_utc_datetime($this->evaluation_object->get_date()), 'weight' => $this->evaluation_object->get_weight(), 'max' => $this->evaluation_object->get_max(), 'visible' => $this->evaluation_object->is_visible()));
		$id_current=isset($this->id)?$this->id :null;
		$this->addElement('hidden', 'hid_id',$id_current);
		$this->build_basic_form(1);
		$this->addElement('style_submit_button', 'submit', get_lang('ModifyEvaluation'),'class="save"');
	}
	/**
	 * Builds a basic form that is used in add and edit
	 */
	private function build_basic_form($edit= 0) {
		$form_title = get_lang('NewEvaluation');
		if ($_GET['editeval']==1)
		{
			$form_title = get_lang('EditEvaluation');
		}

		$this->addElement('header', '', $form_title);
		$this->addElement('hidden', 'zero', 0);
		$this->addElement('hidden', 'hid_user_id');
		$this->addElement('hidden', 'hid_category_id');
		$this->addElement('hidden', 'hid_course_code');
		$this->add_textfield('name', get_lang('EvaluationName'), true, array (
			'size' => '54',
			'maxlength' => '50',
			'id' => 'evaluation_title'
		));
		$this->add_textfield('weight', get_lang('Weight'), true, array (
			'size' => '4',
			'maxlength' => '5'
		));
		if ($edit) {
			if (!$this->evaluation_object->has_results()) {
				$this->add_textfield('max', get_lang('QualificationNumeric'), true, array (
					'size' => '4',
					'maxlength' => '5'
				));
			} else {
				$this->add_textfield('max', get_lang('QualificationNumeric'), false, array (
					'size' => '4',
					'maxlength' => '5',
					'disabled' => 'disabled'
				));
				$this->addElement('static','label','','<small>'.get_lang('CannotChangeTheMaxNote').'</small>');
			}
		} else {
			$this->add_textfield('max', get_lang('QualificationNumeric'), true, array (
				'size' => '4',
				'maxlength' => '5'
			));
		}
		$this->addElement('textarea', 'description', get_lang('Description'), array (
			'rows' => '3',
			'cols' => '34'
		));
		$this->addElement('checkbox', 'visible', get_lang('Visible'));
		$this->addRule('weight', get_lang('OnlyNumbers'), 'numeric');
		$this->addRule(array ('weight', 'zero'), get_lang('NegativeValue'), 'compare', '>=');
		$this->addRule('max', get_lang('OnlyNumbers'), 'numeric');
		$this->addRule(array ('max', 'zero'), get_lang('NegativeValue'), 'compare', '>=');
	}
	function display() {
		parent :: display();
	}
	function setDefaults($defaults= array ()) {
		parent :: setDefaults($defaults);
	}


	private function build_stud_label ($id, $username,$lastname, $firstname) {
		$opendocurl_start = '';
		$opendocurl_end = '';
		// evaluation's origin is a link
		if ($this->evaluation_object->get_category_id() < 0) {
			$link = LinkFactory :: get_evaluation_link ($this->evaluation_object->get_id());
			$doc_url = $link->get_view_url($id);
			if ($doc_url != null) {
				$opendocurl_start .= '<a href="'. $doc_url . '" target="_blank">';
				$opendocurl_end = '</a>';
			}
		}
		return $opendocurl_start . api_get_person_name($firstname, $lastname) .' ('.$username.')'. $opendocurl_end;
	}

	function sort_by_user ($item1, $item2) {
		$user1 = $item1['user'];
		$user2 = $item2['user'];
		if (api_sort_by_first_name()) {
			$result = api_strcmp($user1['firstname'], $user2['firstname']);
			if ($result == 0) {
				return api_strcmp($user1['lastname'], $user2['lastname']);
			}
		} else {
			$result = api_strcmp($user1['lastname'], $user2['lastname']);
			if ($result == 0) {
				return api_strcmp($user1['firstname'], $user2['firstname']);
			}
		}
		return $result;
	}
}
