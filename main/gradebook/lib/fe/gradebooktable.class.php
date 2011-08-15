<?php
/* For licensing terms, see license.txt */
/**
 * Script
 * @package chamilo.gradebook
 */
/**
 * Init
 */

require_once dirname(__FILE__).'/../../../inc/global.inc.php';
require_once dirname(__FILE__).'/../be.inc.php';

/**
 * Table to display categories, evaluations and links
 * @author Stijn Konings
 * @author Bert Steppé (refactored, optimised)
 * @package chamilo.gradebook
 */
class GradebookTable extends SortableTable
{

	private $currentcat;
	private $datagen;
	private $evals_links;

	/**
	 * Constructor
	 */
    function GradebookTable ($currentcat, $cats = array(), $evals = array(), $links = array(), $addparams = null) {

  		$status = CourseManager::get_user_in_course_status(api_get_user_id(), api_get_course_id());
    	parent :: __construct ('gradebooklist', null, null, (api_is_allowed_to_create_course()?1:0));
		$this->evals_links = array_merge($evals, $links);
		$this->currentcat = $currentcat;
		$this->datagen = new GradebookDataGenerator($cats, $evals, $links);
		if (isset($addparams)) {
			$this->set_additional_parameters($addparams);
		}
		$column= 0;
		if (api_is_allowed_to_edit(null, true)) {
			$this->set_header($column++,'','','width="25px"');
		}
		$this->set_header($column++, get_lang('Type'),'','width="35px"');
		$this->set_header($column++, get_lang('Name'));
		$this->set_header($column++, get_lang('Description'));

		if (api_is_allowed_to_edit(null, true)) {
			$this->set_header($column++, get_lang('Weight'),'','width="50px"');
		} else {
			if (empty($_GET['selectcat']) ) {
				$this->set_header($column++, get_lang('Evaluation'));
			} else {
			    $this->set_header($column++, get_lang('Weight'));
			}
		}
		if (api_is_allowed_to_edit(null, true)) {
			$this->set_header($column++, get_lang('Date'),true, 'width="100px"');
		} elseif (($status<>1)  && !api_is_allowed_to_create_course() && (!isset($_GET['selectcat']) || $_GET['selectcat']==0)) {
			$this->set_header($column++, get_lang('Date'),true, 'width="100px"');
		}
		//admins get an edit column
		if (api_is_allowed_to_edit(null, true)) {
			$this->set_header($column++, get_lang('Modify'), false, 'width="120px"');
			//actions on multiple selected documents
			$this->set_form_actions(array (
				'deleted' => get_lang('DeleteSelected'),
				'setvisible' => get_lang('SetVisible'),
				'setinvisible' => get_lang('SetInvisible')));
		} else {
	 	    if (empty($_GET['selectcat']) &&  !api_is_allowed_to_create_course()) {
			    $this->set_header($column++, get_lang('Certificates'),false);
	 	    } else {
	 	    	$evals_links = array_merge($evals, $links);
	 	    	if(count($evals_links)>0) {
             	    $this->set_header($column++, get_lang('Results'), false);
             	}
	 	    }
		}
    }


	/**
	 * Function used by SortableTable to get total number of items in the table
	 */
	function get_total_number_of_items() {
		return $this->datagen->get_total_items_count();
	}


	/**
	 * Function used by SortableTable to generate the data to display
	 */
	function get_table_data($from = 1) {
        //variables load in index.php
        global $my_score_in_gradebook, $certificate_min_score;
        $scoretotal = 0;
		// determine sorting type
		$col_adjust = (api_is_allowed_to_create_course() ? 1 : 0);
		switch ($this->column) {
			// Type
			case (0 + $col_adjust) :
				$sorting = GradebookDataGenerator :: GDG_SORT_TYPE;
				break;
			case (1 + $col_adjust) :
				$sorting = GradebookDataGenerator :: GDG_SORT_NAME;
				break;
			case (2 + $col_adjust) :
				$sorting = GradebookDataGenerator :: GDG_SORT_DESCRIPTION;
				break;
			case (3 + $col_adjust) :
				$sorting = GradebookDataGenerator :: GDG_SORT_WEIGHT;
				break;
			case (4 + $col_adjust) :
				$sorting = GradebookDataGenerator :: GDG_SORT_DATE;
				break;
		}
		if ($this->direction == 'DESC') {
			$sorting |= GradebookDataGenerator :: GDG_SORT_DESC;
		} else {
			$sorting |= GradebookDataGenerator :: GDG_SORT_ASC;
		}
		//status of user in course
	    $user_id     = api_get_user_id();
		$course_code = api_get_course_id();
		$status_user = api_get_status_of_user_in_course ($user_id,$course_code);
		$data_array  = $this->datagen->get_data($sorting, $from, $this->per_page);

		// generate the data to display
		$sortable_data = array();
		$weight_total_links = 0;

		foreach ($data_array as $data) {
            // list of items inside the gradebook (exercises, lps, fora, etc)
			$row  = array ();
			$item = $data[0];
			$id   = $item->get_id();
            //the following condition seems strange to me - YW 20110421
            //GET['selectcat'] is the main gradebook. When defined, it means we are looking at the whole stuff instead of one sub-element (or something like that?)
            
			//if (empty($_GET['selectcat']) ) { //if not particular gradebook item was selected, take the certificate score for the current item
				//$certificate_min_score = $this->build_certificate_min_score($item);
			//}
			//$_GET['selectcat'] is never empty jm 20110426
			
			//if the item is invisible, wrap it in a span with class invisible
			$invisibility_span_open  = (api_is_allowed_to_create_course() && $item->is_visible() == '0') ? '<span class="invisible">' : '';
			$invisibility_span_close = (api_is_allowed_to_create_course() && $item->is_visible() == '0') ? '</span>' : '';

			if (api_is_allowed_to_edit(null, true)) {
				$row[] = $this->build_id_column($item);
			}

			$row[] = $this->build_type_column($item);
			$row[] = $invisibility_span_open.$this->build_name_link ($item) . $invisibility_span_close;
			$row[] = $invisibility_span_open.$data[2] . $invisibility_span_close;
			if (api_is_allowed_to_edit(null, true)) {
				$row[] = $invisibility_span_open . $data[3] . $invisibility_span_close;
				$weight_total_links += intval($data[3]);
			} else {
                 // is never empty
				/*if (empty($_GET['selectcat'])) {
				    // generating the total score for a course
				    $stud_id= api_get_user_id();
					$cats_course = Category::load($id, null, null, null, null, null, false);
					$alleval_course= $cats_course[0]->get_evaluations($stud_id,true);
					$alllink_course= $cats_course[0]->get_links($stud_id,true);
					$evals_links = array_merge($alleval_course, $alllink_course);
					$item_value=0;
					$item_total=0;
					for ($count=0; $count < count($evals_links); $count++) {
    					$item = $evals_links[$count];
    					$score = $item->calc_score($stud_id);
    
    					$score_denom=($score[1]==0) ? 1 : $score[1];
    					$item_value+=$score[0]/$score_denom*$item->get_weight();
    					$item_total+=$item->get_weight();
					}
					$item_value = number_format($item_value, 2, '.', ' ');
                    $cattotal = Category :: load($id);
                    $scoretotal= $cattotal[0]->calc_score(api_get_user_id());
                    $scoretotal_display = (isset($scoretotal)? round($scoretotal[0],2).'/'.round($scoretotal[1],2).' ('.round(($scoretotal[0] / $scoretotal[1]) * 100,2) . ' %)': '-');
					$row[] = $item_value;                
				} else {*/
                    $cattotal   = Category :: load($_GET['selectcat']);
                    $scoretotal = $cattotal[0]->calc_score(api_get_user_id());                    
                    $item_value = $scoretotal[0];
                    $item_value = number_format($item_value, 2, '.', ' ');
			   		$row[] = $invisibility_span_open . $data[3] . $invisibility_span_close;
			   //}			   	
			}
    		$row[] = $invisibility_span_open.$data[4].$invisibility_span_close;

			//admins get an edit column
			if (api_is_allowed_to_edit(null, true)) {
				$cat = new Category();
				$show_message = $cat->show_message_resource_delete($item->get_course_code());
				if ($show_message===false) {
					$row[] = $this->build_edit_column($item); 
				}
			} else {
				//students get the results and certificates columns
				if (count($this->evals_links)>0 && $status_user!=1 ) {
					$value_data=isset($data[5]) ? $data[5] : null;
					if (!is_null($value_data)) {
						$row[] = $value_data;
					}
				}

				/*if (empty($_GET['selectcat'])) {
					if (isset($certificate_min_score) && $item_value >= $certificate_min_score) {
						$certificates = '<a href="'.api_get_path(WEB_CODE_PATH) .'gradebook/'.$_SESSION['gradebook_dest'].'?export_certificate=yes&cat_id='.$id.'" target="_blank">
										 <img src="'.api_get_path(WEB_CODE_PATH) . 'img/logo.gif" /></a>&nbsp;'.$scoretotal_display;

						//register gradebook certificate
						$current_user_id=api_get_user_id();
						register_user_info_about_certificate($id,$current_user_id, $my_score_in_gradebook, api_get_utc_datetime());

					} else {
						$certificates = '-';
					}
					//show certificate date
					$get_date=get_certificate_date_by_user_id($id,$current_user_id);
					if ($get_date=='' || is_null($get_date)) {
							$row[4]='-';
					} else {
							$row[4] = api_convert_and_format_date($get_date);
					}
					$row[] = $certificates;
				} else {*/				    
					if (isset($certificate_min_score) && $item_value >= $certificate_min_score) {
						//register gradebook certificate
						$current_user_id = api_get_user_id();
						register_user_info_about_certificate($_GET['selectcat'], $current_user_id, $my_score_in_gradebook, api_get_utc_datetime());
					}					
				//}
			}
			$sortable_data[] = $row;
		}

		// warning messages

		if (api_is_allowed_to_edit()) {
			if (isset($_GET['selectcat']) && $_GET['selectcat'] > 0 && $_GET['view'] <> 'presence') {
				$id_cat = intval($_GET['selectcat']);
				$category = Category :: load($id_cat);
				$weight_category = intval($this->build_weight($category[0]));
				$course_code = $this->build_course_code($category[0]);

				if ($weight_total_links > $weight_category) {
					$warning_message = get_lang('TotalWeightMustNotBeMoreThan').'&nbsp;'.$weight_category;
					Display::display_warning_message($warning_message,false);
				}
				
				if ($weight_total_links < $weight_category) {
					$warning_message = sprintf(get_lang('TotalWeightMustBeX'), $weight_category);
					Display::display_warning_message($warning_message,false);
				}

				$content_html = DocumentManager::replace_user_info_into_html(api_get_user_id(), $course_code);

				$new_content=explode('</head>',$content_html['content_html']);

				if (empty($new_content[0])) {
					$warning_message = get_lang('ThereIsNotACertificateAvailableByDefault');
					Display::display_warning_message($warning_message);
				}

			}

			if (empty($_GET['selectcat'])) {

				$categories = Category :: load();
				$weight_categories = $certificate_min_scores = $course_codes = array();

				foreach ($categories as $category) {
					$course_code_category = $this->build_course_code($category);
					if (!empty($course_code)) {
						if ($course_code_category == $course_code) {
							$weight_categories[] = intval($this->build_weight($category));
							$certificate_min_scores[] = intval($this->build_certificate_min_score($category));
							$course_codes[] = $course_code;
							break;
						}
					} else {
						$weight_categories[] = intval($this->build_weight($category));
						$certificate_min_scores[] = intval($this->build_certificate_min_score($category));
						$course_codes[] = $course_code_category;
					}
				}

				if (is_array($weight_categories) && is_array($certificate_min_scores) && is_array($course_codes)) {
					$warning_message = '';
					for ($x = 0; $x<count($weight_categories);$x++) {
						$weight_category = intval($weight_categories[$x]);
						$certificate_min_score = intval($certificate_min_scores[$x]);
						$course_code = $course_codes[$x];

						if (empty($certificate_min_score) || ($certificate_min_score > $weight_category)) {
							$warning_message .= $course_code .'&nbsp;-&nbsp;'.get_lang('CertificateMinimunScoreIsRequiredAndMustNotBeMoreThan').'&nbsp;'.$weight_category.'<br />';
						}
					}

					if (!empty($warning_message)) {
						Display::display_warning_message($warning_message,false);
					}
				}
			}
		}
		return $sortable_data;
	}

    // Other functions
    
    private function build_certificate_min_score ($item) {
    	return $item->get_certificate_min_score();
    }
    
    private function build_weight ($item) {
    	return $item->get_weight();
    }
    
    private function build_course_code ($item) {
    	return $item->get_course_code();
    }

    private function build_id_column ($item) {
		switch ($item->get_item_type()) {
			// category
			case 'C' :
				return 'CATE' . $item->get_id();
			// evaluation
			case 'E' :
				return 'EVAL' . $item->get_id();
			// link
			case 'L' :
				return 'LINK' . $item->get_id();
		}
	}

	private function build_type_column ($item) {
		return build_type_icon_tag($item->get_icon_name());
	}

	private function build_name_link ($item) {

		switch ($item->get_item_type()) {
			// category
			case 'C' :
				$prms_uri='?selectcat=' . $item->get_id() . '&amp;view='.Security::remove_XSS($_GET['view']);

				if (isset($_GET['isStudentView'])) {
					if ( isset($is_student) || ( isset($_SESSION['studentview']) && $_SESSION['studentview']=='studentview') ) {
						$prms_uri=$prms_uri.'&amp;isStudentView='.Security::remove_XSS($_GET['isStudentView']);
					}
				}

				$cat=new Category();
				$show_message=$cat->show_message_resource_delete($item->get_course_code());

				return '&nbsp;<a href="'.Security::remove_XSS($_SESSION['gradebook_dest']).$prms_uri.'">'
				 		. $item->get_name()
				 		. '</a>'
				 		. ($item->is_course() ? ' &nbsp;[' . $item->get_course_code() . ']'.$show_message : '');
			// evaluation
			case 'E' :
				$cat=new Category();
				//$dblib=new Database();

				$category_id=Security::remove_XSS($_GET['selectcat']);
				$course_id=Database::get_course_by_category($category_id);
				$show_message=$cat->show_message_resource_delete($course_id);

				// course/platform admin can go to the view_results page

				if (api_is_allowed_to_create_course() && $show_message===false) {

					if ($item->get_type() == 'presence')
					{
						return '&nbsp;'
							. '<a href="gradebook_view_result.php?cidReq='.$course_id.'&amp;selecteval=' . $item->get_id() . '">'
							. $item->get_name()
							. '</a>';
						/*return '&nbsp;'
							. '<a href="gradebook_add_result.php?selectcat'.Security::remove_XSS($_GET['selectcat']).'&amp;selecteval=' . $item->get_id() . '">'
							. $item->get_name()
							. '</a>';
							*/
					}
					else
					{


						return '&nbsp;'
							. '<a href="gradebook_view_result.php?cidReq='.$course_id.'&amp;selecteval=' . $item->get_id() . '">'
							. $item->get_name()
							. '</a>&nbsp;['.get_lang('Evaluation').']';
					}
				} elseif (ScoreDisplay :: instance()->is_custom() && $show_message===false) {
					// students can go to the statistics page (if custom display enabled)
					return '&nbsp;'
						. '<a href="gradebook_statistics.php?selecteval=' . $item->get_id() . '">'
						. $item->get_name()
						. '</a>';

				} elseif ($show_message===false && !api_is_allowed_to_create_course() && !(ScoreDisplay :: instance()->is_custom())) {
					return '&nbsp;'
						. '<a href="gradebook_statistics.php?selecteval=' . $item->get_id() . '">'
						. $item->get_name()
						. '</a>';

				} else {
					return '['.get_lang('Evaluation').']&nbsp;&nbsp;'.$item->get_name().$show_message;
				}
			// link
			case 'L' :
				$cat=new Category();
				//$dblib=new Database();

				$category_id=Security::remove_XSS($_GET['selectcat']);
				$course_id=Database::get_course_by_category($category_id);
				$show_message=$cat->show_message_resource_delete($course_id);

				$url = $item->get_link();
				if (isset($url) && $show_message===false) {
					$text = '&nbsp;<a href="' . $item->get_link() . '">'
							. $item->get_name()
							. '</a>';
				} else {
					$text = $item->get_name();
				}

				$text .= '&nbsp;[' . $item->get_type_name() . ']'.$show_message;
				$cc = $this->currentcat->get_course_code();
				if (empty($cc)) {
					$text .= '&nbsp;[<a href="'.api_get_path(REL_COURSE_PATH).$item->get_course_code().'/">'.$item->get_course_code().'</a>]';
				}
				return $text;
		}
	}
	
	private function build_edit_column($item) {
		switch ($item->get_item_type()) {
			// category
			case 'C' :
				return build_edit_icons_cat($item, $this->currentcat->get_id());
			// evaluation
			case 'E' :
				return build_edit_icons_eval($item, $this->currentcat->get_id());
			// link
			case 'L' :
				return build_edit_icons_link($item, $this->currentcat->get_id());

		}
	}
}
