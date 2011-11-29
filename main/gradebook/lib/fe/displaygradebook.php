<?php
/* For licensing terms, see /license.txt */
/**
 * Script
 * @package chamilo.gradebook
 */
/**
 * Class
 * @package chamilo.gradebook
 */
class DisplayGradebook
{
	/**
	* Displays the header for the result page containing the navigation tree and links
	* @param $evalobj
	* @param $selectcat
	* @param $shownavbar 1=show navigation bar
	* @param $forpdf only output for pdf file
	*/
	function display_header_result($evalobj, $selectcat, $page) {
		if (api_is_allowed_to_edit(null, true)) {
			$header = '<div class="actions">';
			
			if ($page != 'statistics') {
			    $header .= '<a href="'.Security::remove_XSS($_SESSION['gradebook_dest']).'?selectcat=' . $selectcat . '">'. Display::return_icon(('back.png'),get_lang('FolderView'),'','32').'</a>';
    			if ($evalobj->get_course_code() == null) {
                    //Disabling code when course code is null see issue #2705
    				//$header .= '<a href="gradebook_add_user.php?selecteval=' . $evalobj->get_id() . '"><img src="../img/add_user_big.gif" alt="' . get_lang('AddStudent') . '" align="absmiddle" /> ' . get_lang('AddStudent') . '</a>';
    			} elseif (!$evalobj->has_results()) {
    				$header .= '<a href="gradebook_add_result.php?selectcat=' . $selectcat . '&selecteval=' . $evalobj->get_id() . '">
    				'.Display::return_icon('evaluation_rate.png',get_lang('AddResult'),'','32') . '</a>';				
    			}
    			$header .= '<a href="' . api_get_self() . '?&selecteval=' . $evalobj->get_id() . '&import=">
    			'.Display::return_icon('import_evaluation.png',get_lang('ImportResult'),'','32') . '</a>';			
    			if ($evalobj->has_results()) {
    				$header .= '<a href="' . api_get_self() . '?&selecteval=' . $evalobj->get_id() . '&export=">'.Display::return_icon('export_evaluation.png',get_lang('ExportResult'),'','32') . '</a>';
    				$header .= '<a href="gradebook_edit_result.php?selecteval=' . $evalobj->get_id() .'">'.Display::return_icon('edit.png',get_lang('EditResult'),'','32').'</a>';
    				$header .= '<a href="' . api_get_self() . '?&selecteval=' . $evalobj->get_id() . '&deleteall=" onclick="return confirmationall();">'.Display::return_icon('delete.png',get_lang('DeleteResult'),'','32').'</a>';
    			}
    			
    			$header .= '<a href="' . api_get_self() . '?print=&selecteval=' . $evalobj->get_id() . '" target="_blank">'.Display::return_icon('printer.png',get_lang('Print'),'','32').'</a>';
			} else {
			    $header .= '<a href="gradebook_view_result.php?selecteval='.Security::remove_XSS($_GET['selecteval']).'"> '.Display::return_icon(('back.png'),get_lang('FolderView'),'','32') . '</a>';
			}
			$header .= '</div>';
		}

		if ($evalobj->is_visible() == '1') {
			$visible= get_lang('Yes');
		} else {
			$visible= get_lang('No');
		}

		$scoredisplay = ScoreDisplay :: instance();
        
        $student_score = '';
        
		if (($evalobj->has_results())){ // TODO this check needed ?
			$score = $evalobj->calc_score();
        
			if ($score != null) {
                $average= get_lang('Average') . ' :<b> ' .$scoredisplay->display_score($score, SCORE_AVERAGE) . '</b>';
                $student_score = $evalobj->calc_score(api_get_user_id());
                $student_score = Display::tag('h3', get_lang('Result').': '.$scoredisplay->display_score($student_score, SCORE_DIV_PERCENT));                            
            }				
		}
		
		if (!$evalobj->get_description() == '') {
			$description= get_lang('Description') . ' :<b> ' . $evalobj->get_description() . '</b><br>';
		}
        
		if ($evalobj->get_course_code() == null) {
			$course= get_lang('CourseIndependent');
		} else {
			$course= get_course_name_from_code($evalobj->get_course_code());
		}
    
		$evalinfo= '<table width="100%" border="0"><tr><td>';
		$evalinfo .= '<h2>'.$evalobj->get_name().'</h2>'; 
		$evalinfo .= $description;
		$evalinfo .= get_lang('Course') . ' :<b> ' . $course . '</b><br />';
		//'<br>' . get_lang('Weight') . ' :<b> ' . $evalobj->get_weight() . '</b><br>' . get_lang('Visible') . ' :<b> ' . $visible . '</b>
        $evalinfo .=  get_lang('QualificationNumeric') . ' :<b> ' . $evalobj->get_max() . '</b><br>'.$average;
        
        if (!api_is_allowed_to_edit()) {
            $evalinfo .= $student_score;
        }
        
        
        
		if (!$evalobj->has_results()) {
			$evalinfo .= '<br /><i>' . get_lang('NoResultsInEvaluation') . '</i>';
		} elseif ($scoredisplay->is_custom() && api_get_self() != '/main/gradebook/gradebook_statistics.php') {
            if (api_is_allowed_to_edit(null, true)) {
                if ($page != 'statistics') {    
                    //$evalinfo .= '<br /><br /><a href="gradebook_view_result.php?selecteval='.Security::remove_XSS($_GET['selecteval']).'"> '.Display::return_icon(('evaluation_rate.png'),get_lang('ViewResult'),'','32') . '</a>';
                }
            }
        }
        if ($page != 'statistics') {    
            if (api_is_allowed_to_edit(null, true)) {
                $evalinfo .= '<br /><a href="gradebook_statistics.php?selecteval='.Security::remove_XSS($_GET['selecteval']).'"> '.Display::return_icon(('statistics.png'),get_lang('ViewStatistics'),'','32').'</a>';
            }
        }
        $evalinfo .= '</td><td><img style="float:right; position:relative;" src="../img/tutorial.gif"></img></td></table>';
        
		echo $evalinfo;
		echo $header;

	}
	/**
	* Displays the header for the flatview page containing filters
	* @param $catobj
	* @param $showeval
	* @param $showlink
	*/
	function display_header_flatview($catobj, $showeval, $showlink,$simple_search_form) {
		$header= '<table border="0" cellpadding="5">';
		$header .= '<td style="vertical-align: top;"><a href="'.Security::remove_XSS($_SESSION['gradebook_dest']).'?selectcat=' . Security::remove_XSS($_GET['selectcat']) . '">' . Display::return_icon('gradebook.gif') . get_lang('Gradebook') . '</a></td>';
		$header .= '<td style="vertical-align: top;">' . get_lang('FilterCategory') . '</td><td style="vertical-align: top;"><form name="selector"><select name="selectcat" onchange="document.selector.submit()">';
		$cats= Category :: load();
		$tree= $cats[0]->get_tree();
		unset ($cats);
		foreach ($tree as $cat) {
			for ($i= 0; $i < $cat[2]; $i++) {
				$line .= '&mdash;';
			}
			if ($_GET['selectcat'] == $cat[0]) {
				$header .= '<option selected="selected" value=' . $cat[0] . '>' . $line . ' ' . $cat[1] . '</option>';
			} else {
				$header .= '<option value=' . $cat[0] . '>' . $line . ' ' . $cat[1] . '</option>';
			}
			$line= '';
		}
		$header .= '</td></select></form>';
		if (!$catobj->get_id() == '0') {
			$header .= '<td style="vertical-align: top;"><a href="' . api_get_self() . '?selectcat=' . $catobj->get_parent_id() . '"><img src="../img/gradebook.gif" border="0" alt="'.get_lang('Up').'" /></a></td>';
		}
		$header .= '<td style="vertical-align: top;">'.$simple_search_form->toHtml().'</td>';
		$header .= '<td style="vertical-align: top;"><a href="' . api_get_self() . '?exportpdf=&offset='.Security::remove_XSS($_GET['offset']).'&search=' . Security::remove_XSS($_GET['search']).'&selectcat=' . $catobj->get_id() . '"><img src=../img/icons/32/pdf.png alt=' . get_lang('ExportPDF') . '/> ' . get_lang('ExportPDF') . '</a>';
		$header .= '<td style="vertical-align: top;"><a href="' . api_get_self() . '?print=&selectcat=' . $catobj->get_id() . '" target="_blank"><img src="../img/icons/32/printer.png" alt=' . get_lang('Print') . '/> ' . get_lang('Print') . '</a>';
		$header .= '</td></tr></table>';
		if (!$catobj->get_id() == '0') {
			$header .= '<table border="0" cellpadding="5"><tr><td><form name="itemfilter" method="post" action="' . api_get_self() . '?selectcat=' . $catobj->get_id() . '"><input type="checkbox" name="showeval" onclick="document.itemfilter.submit()" ' . (($showeval == '1') ? 'checked' : '') . '>Show Evaluations &nbsp;';
			$header .= '<input type="checkbox" name="showlink" onclick="document.itemfilter.submit()" ' . (($showlink == '1') ? 'checked' : '') . '>'.get_lang('ShowLinks').'</form></td></tr></table>';
		}
		if (isset ($_GET['search'])) {
			$header .= '<b>'.get_lang('SearchResults').' :</b>';
		}
		echo $header;
	}

		/**
	* Displays the header for the flatview page containing filters
	* @param $catobj
	* @param $showeval
	* @param $showlink
	*/
	function display_header_reduce_flatview($catobj, $showeval, $showlink,$simple_search_form) {
		$header = '<div class="actions">';
		$header .= '<a href="'.Security::remove_XSS($_SESSION['gradebook_dest']).'?'.api_get_cidreq().'">'. Display::return_icon('back.png',get_lang('FolderView'),'','32').'</a>';
//		$header .= '<td style="vertical-align: top;"><a href="' . api_get_self() . '?exportpdf=&offset='.Security::remove_XSS($_GET['offset']).'&search=' . Security::remove_XSS($_GET['search']).'&selectcat=' . $catobj->get_id() . '"><img src=../img/file_pdf.gif alt=' . get_lang('ExportPDF') . '/> ' . get_lang('ExportPDF') . '</a>';

		// this MUST be a GET variable not a POST
		if (isset($_GET['show'])) {
			$show=Security::remove_XSS($_GET['show']);
		} else {
			$show='';
		}
		echo '<form id="form1a" name="form1a" method="post" action="'.api_get_self().'?show='.$show.'">';
		echo '<input type="hidden" name="export_report" value="export_report">';
		echo '<input type="hidden" name="selectcat" value="'.$catobj->get_id() .'">';

		echo '<input type="hidden" name="export_format" value="csv">';
		echo '</form>';

		echo '<form id="form1b" name="form1b" method="post" action="'.api_get_self().'?show='.$show.'">';
		echo '<input type="hidden" name="export_report" value="export_report">';
		echo '<input type="hidden" name="selectcat" value="'.$catobj->get_id() .'">';
		echo '<input type="hidden" name="export_format" value="xls">';
		echo '</form>';
		echo '<form id="form1c" name="form1c" method="post" action="'.api_get_self().'?show='.$show.'">';
		echo '<input type="hidden" name="export_report" value="export_report">';
		echo '<input type="hidden" name="selectcat" value="'.$catobj->get_id() .'">';
		echo '<input type="hidden" name="export_format" value="doc">';
		echo '</form>';

		$header .= '<a  href="javascript: void(0);" onclick="javascript: document.form1a.submit();">'.Display::return_icon('export_csv.png', get_lang('ExportAsCSV'),'','32').'</a>';
		$header .= '<a " href="javascript: void(0);" onclick="javascript: document.form1b.submit();">'.Display::return_icon('export_excel.png', get_lang('ExportAsXLS'),'','32').'</a>';
		$header .= '<a " href="javascript: void(0);" onclick="javascript: document.form1c.submit();">'.Display::return_icon('export_doc.png', get_lang('ExportAsDOC'),'','32').'</a>';
		$header .= '<a href="' . api_get_self() . '?print=&selectcat=' . $catobj->get_id() . '" target="_blank">'.Display::return_icon('printer.png', get_lang('Print'),'','32').'</a>';
		$header .= '<a href="' . api_get_self() . '?exportpdf=&selectcat=' . $catobj->get_id() . '" >'.Display::return_icon('pdf.png', get_lang('ExportToPDF'),'','32').'</a>';

		//exportpdf
		//<div class="clear">
		$header .= '</div>';
		if (!$catobj->get_id() == '0') {
			//this is necessary?
			//$header .= '<table border="0" cellpadding="5"><tr><td><form name="itemfilter" method="post" action="' . api_get_self() . '?selectcat=' . $catobj->get_id() . '"><input type="checkbox" name="showeval" onclick="document.itemfilter.submit()" ' . (($showeval == '1') ? 'checked' : '') . '>Show Evaluations &nbsp;';
			//$header .= '<input type="checkbox" name="showlink" onclick="document.itemfilter.submit()" ' . (($showlink == '1') ? 'checked' : '') . '>'.get_lang('ShowLinks').'</form></td></tr></table>';
		}
		/*
		if (isset ($_GET['search'])) {
			$header .= '<b>'.get_lang('SearchResults').' :</b>';
		}*/
		echo $header;
	}	
	
	function display_header_gradebook_per_gradebook($catobj, $showtree, $selectcat, $is_course_admin, $is_platform_admin, $simple_search_form, $show_add_qualification = true, $show_add_link = true) {

		//student
		$status = CourseManager::get_user_in_course_status(api_get_user_id(), api_get_course_id());
		$objcat = new Category();
		$course_id = Database::get_course_by_category($selectcat);
		$message_resource=$objcat->show_message_resource_delete($course_id);
		
		if (!$is_course_admin && $status<>1 && $selectcat<>0) {
			$user_id = api_get_user_id();
			$user= get_user_info_from_id($user_id);
		
			$catcourse= Category :: load($catobj->get_id());
			$scoredisplay = ScoreDisplay :: instance();
			$scorecourse = $catcourse[0]->calc_score($user_id);
		
			// generating the total score for a course
			$allevals= $catcourse[0]->get_evaluations($user_id,true);
			$alllinks= $catcourse[0]->get_links($user_id,true);
			$evals_links = array_merge($allevals, $alllinks);
			$item_value=0;
			$item_total=0;
			for ($count=0; $count < count($evals_links); $count++) {
				$item = $evals_links[$count];
				$score = $item->calc_score($user_id);
				$my_score_denom=($score[1]==0) ? 1 : $score[1];
				$item_value+=$score[0]/$my_score_denom*$item->get_weight();
				$item_total+=$item->get_weight();
				//$row[] = $scoredisplay->display_score($score,SCORE_DIV_PERCENT);
			}
			$item_value = number_format($item_value, 2, '.', ' ');
			$total_score=array($item_value,$item_total);
			$scorecourse_display = $scoredisplay->display_score($total_score, SCORE_DIV_PERCENT);
		
			$cattotal = Category :: load(0);
			$scoretotal= $cattotal[0]->calc_score(api_get_user_id());
			$scoretotal_display = (isset($scoretotal) ? $scoredisplay->display_score($scoretotal, SCORE_PERCENT) : get_lang('NoResultsAvailable'));
			$scoreinfo = get_lang('StatsStudent') . ' :<b> '.api_get_person_name($user['firstname'], $user['lastname']).'</b><br />';
			if ((!$catobj->get_id() == '0') && (!isset ($_GET['studentoverview'])) && (!isset ($_GET['search']))) {
				$scoreinfo.= '<h2>'.get_lang('Total') . ' : ' . $scorecourse_display . '</h2>';
			}		
			Display :: display_normal_message($scoreinfo, false);
		}
		// show navigation tree and buttons?
		
		$header = '<div class="actions"><table border=0>';
		if (($showtree == '1') || (isset ($_GET['studentoverview']))) {
			$header .= '<tr>';
			if (!$selectcat == '0') {
				$header .= '<td style=" "><a href="' . api_get_self() . '?selectcat=' . $catobj->get_parent_id() . '">'.Display::return_icon('back.png',get_lang('BackTo').' '.get_lang('RootCat'),'','32').'</a></td>';
			}
			$header .= '<td style=" ">' . get_lang('CurrentCategory') . '</td>' .
							'<td style=" "><form name="selector"><select name="selectcat" onchange="document.selector.submit()">';
			$cats= Category :: load();
		
			$tree= $cats[0]->get_tree();
			unset ($cats);
				
			foreach ($tree as $cat) {
				for ($i= 0; $i < $cat[2]; $i++) {
					$line .= '&mdash;';
				}
				$line=isset($line) ? $line : '';
				if (isset($_GET['selectcat']) && $_GET['selectcat'] == $cat[0]) {
					$header .= '<option selected value=' . $cat[0] . '>' . $line . ' ' . $cat[1] . '</option>';
				} else {
					$header .= '<option value=' . $cat[0] . '>' . $line . ' ' . $cat[1] . '</option>';
				}
				$line= '';
			}
			$header .= '</select></form></td>';
			if (!empty($simple_search_form) && $message_resource===false) {
				$header .= '<td style="vertical-align: top;">'.$simple_search_form->toHtml().'</td>';
			} else {
				$header .= '<td></td>';
			}
			if ($is_course_admin && $message_resource===false && $_GET['selectcat']!=0) {
				/*$header .= '<td style="vertical-align: top;"><a href="gradebook_flatview.php?'.api_get_cidreq().'&selectcat=' . $catobj->get_id() . '"><img src="../img/view_list.gif" alt="' . get_lang('FlatView') . '" /> ' . get_lang('FlatView') . '</a>';
				 if ($is_course_admin && $message_resource===false) {
				$header .= '<td style="vertical-align: top;"><a href="gradebook_scoring_system.php?'.api_get_cidreq().'&selectcat=' . $catobj->get_id() .'"><img src="../img/acces_tool.gif" alt="' . get_lang('ScoreEdit') . '" /> ' . get_lang('ScoreEdit') . '</a>';
				}*/
			} elseif (!(isset ($_GET['studentoverview']))) {
				if ( $message_resource===false ) {
					//$header .= '<td style="vertical-align: top;"><a href="'.api_get_self().'?'.api_get_cidreq().'&studentoverview=&selectcat=' . $catobj->get_id() . '"><img src="../img/view_list.gif" alt="' . get_lang('FlatView') . '" /> ' . get_lang('FlatView') . '</a>';
				}
			} else {
				$header .= '<td style="vertical-align: top;"><a href="'.api_get_self().'?'.api_get_cidreq().'&studentoverview=&exportpdf=&selectcat=' . $catobj->get_id() . '" target="_blank"><img src="../img/icons/32/.png" alt="' . get_lang('ExportPDF') . '" /> ' . get_lang('ExportPDF') . '</a>';
			}
			$header .= '</td></tr>';
		}
		$header.='</table></div>';
		
		// for course admin & platform admin add item buttons are added to the header
		$header .= '<div class="actions">';
		
		$my_category = $catobj->shows_all_information_an_category($catobj->get_id());
		$user_id     = api_get_user_id();
		$course_code = $my_category['course_code'];
		$status_user = api_get_status_of_user_in_course ($user_id,$course_code);
		
		//$header .= '<a href="gradebook_add_cat.php?'.api_get_cidreq().'&selectcat=0"><img src="../img/folder_new.gif" alt="' . get_lang('AddGradebook') . '" /></a></td>';
		
		if (api_is_allowed_to_edit(null, true)) {
			if ($selectcat == '0') {
				if ($show_add_qualification === true) {						
				}
				if ($show_add_link) {
					//$header .= '<td><a href="gradebook_add_eval.php?'.api_get_cidreq().'"><img src="../img/filenew.gif" alt="' . get_lang('NewEvaluation') . '" /> ' . get_lang('NewEvaluation') . '</a>';
				}
			} else {
				if ($show_add_qualification === true && $message_resource===false) {
					//$header .= '<a href="gradebook_add_cat.php?'.api_get_cidreq().'&selectcat=' . $catobj->get_id() . '" ><img src="../img/folder_new.gif" alt="' . get_lang('NewSubCategory') . '" align="absmiddle" /> ' . get_lang('NewSubCategory') . '</a></td>';
				}
				$my_category=$catobj->shows_all_information_an_category($catobj->get_id());
				$my_api_cidreq = api_get_cidreq();
				if ($my_api_cidreq=='') {
					$my_api_cidreq='cidReq='.$my_category['course_code'];
				}
				if ($show_add_link && !$message_resource) {
					//$header .= '<td><a href="gradebook_add_eval.php?'.$my_api_cidreq.'&selectcat=' . $catobj->get_id() . '" >'.Display::return_icon('new_evaluation.png', get_lang('NewEvaluation'),'','32').'</a>';
					$cats= Category :: load($selectcat);
					if ($cats[0]->get_course_code() != null && !$message_resource) {
						//$header .= '<td><a href="gradebook_add_link.php?'.api_get_cidreq().'&selectcat=' . $catobj->get_id() . '"><img src="../img/link.gif" alt="' . get_lang('MakeLink') . '" align="absmiddle" /> ' . get_lang('MakeLink') . '</a>';
						//$header .= '<td><a href="gradebook_add_link.php?'.$my_api_cidreq.'&selectcat=' . $catobj->get_id() . '">'.Display::return_icon('new_online_evaluation.png', get_lang('MakeLink'),'','32').'</a>';
		
					} else {
					//	$header .= '<td><a href="gradebook_add_link_select_course.php?'.$my_api_cidreq.'&selectcat=' . $catobj->get_id() . '">'.Display::return_icon('new_online_evaluation.png', get_lang('MakeLink'),'','32').'</a>';
					}
				}
		
				if (!$message_resource) {
					$myname = $catobj->shows_all_information_an_category($catobj->get_id());
		
					$my_course_id=api_get_course_id();
					$my_file= substr($_SESSION['gradebook_dest'],0,5);
		
					$header .= '<td style="vertical-align: top;"><a href="gradebook_flatview.php?'.$my_api_cidreq.'&selectcat=' . $catobj->get_id() . '">'.Display::return_icon('stats.png', get_lang('FlatView'),'','32').'</a>';
					$header .= '<td style="vertical-align: top;"><a href="gradebook_display_certificate.php?'.$my_api_cidreq.'&amp;cat_id='.(int)$_GET['selectcat'].'">'.Display::return_icon('certificate_list.png', get_lang('GradebookSeeListOfStudentsCertificates'),'','32').'</a>';
						
					$visibility_icon    = ($catobj->is_visible() == 0) ? 'invisible' : 'visible';
					$visibility_command = ($catobj->is_visible() == 0) ? 'set_visible' : 'set_invisible';
					 
					//Right icons
					$modify_icons  = '<a href="gradebook_edit_cat.php?editcat='.$catobj->get_id().'&cidReq='.$catobj->get_course_code().'">'.Display::return_icon('edit.png', get_lang('Edit'),'','32').'</a>';
					//$modify_icons .= '<a href="../document/document.php?curdirpath=/certificates&'.$my_api_cidreq.'&origin=gradebook&selectcat=' . $catobj->get_id() . '">'.
					Display::return_icon('certificate.png', get_lang('AttachCertificate'),'','32').'</a>';
		
					//$modify_icons .= '<a href="gradebook_edit_all.php?id_session='.intval($_SESSION['id_session']).'&amp;'.$my_api_cidreq.'&selectcat=' . $catobj->get_id() . '">'.Display::return_icon('percentage.png', get_lang('EditAllWeights'),'','32').'</a>';
		
					//$modify_icons .= '<a href="gradebook_scoring_system.php?'.$my_api_cidreq.'&selectcat=' . $catobj->get_id() .'">'.Display::return_icon('ranking.png', get_lang('ScoreEdit'),'','32').'</a>';
		
					//hide or delete are not options available
					//$modify_icons .= '&nbsp;<a  href="' . api_get_self() . '?visiblecat=' . $catobj->get_id() . '&amp;' . $visibility_command . '=&amp;selectcat=0 ">'.Display::return_icon($visibility_icon.'.png', get_lang('Visible'),'','32').'</a>';
					if ($catobj->get_name() != api_get_course_id()) {
						$modify_icons .= '&nbsp;<a  href="' . api_get_self() . '?deletecat=' . $catobj->get_id() . '&amp;selectcat=0&amp;cidReq='.$catobj->get_course_code().'" onclick="return confirmation();">'.Display::return_icon('delete.png', get_lang('DeleteAll'),'','32').'</a>';
					}					 
					$header .= Display::div($modify_icons, array('class'=>'right'));
						
				}
			}
		} elseif (isset ($_GET['search'])) {
			$header .= '<b>'.get_lang('SearchResults').' :</b>';
		}
		$header .= '</div>';
		echo $header;		
		
		/*
		if (api_is_allowed_to_edit(null, true)) {
			$weight = ((intval($catobj->get_weight())>0) ? $catobj->get_weight() : 0);
			$weight  = Display::tag('h3', get_lang('TotalWeight').' : '.$weight);
			$min_certification = (intval($catobj->get_certificate_min_score()>0) ? $catobj->get_certificate_min_score() : 0);
			$min_certification = Display::tag('h3', get_lang('CertificateMinScore').' : '.$min_certification);
			//@todo show description
			$description       = (($catobj->get_description() == "" || is_null($catobj->get_description())) ? '' : '<strong>'.get_lang('GradebookDescriptionLog').'</strong>'.': '.$catobj->get_description());
			Display::display_normal_message($weight.$min_certification, false);
    		if (!empty($description)) {
				echo Display::div($description, array());
			}
		}*/
	}
	/**
	 * Displays the header for the gradebook containing the navigation tree and links
	 * @param category_object $currentcat
	 * @param int $showtree '1' will show the browse tree and naviation buttons
	 * @param boolean $is_course_admin
	 * @param boolean $is_platform_admin
     * @param boolean Whether to show or not the link to add a new qualification (we hide it in case of the course-embedded tool where we have only one calification per course or session)
     * @param boolean Whether to show or not the link to add a new item inside the qualification (we hide it in case of the course-embedded tool where we have only one calification per course or session)
     * @return void Everything is printed on screen upon closing
	 */
	function display_header_gradebook($catobj, $showtree, $selectcat, $is_course_admin, $is_platform_admin, $simple_search_form, $show_add_qualification = true, $show_add_link = true) {
		//student
		$status = CourseManager::get_user_in_course_status(api_get_user_id(), api_get_course_id());
		$objcat = new Category();
		$course_id = Database::get_course_by_category($selectcat);
		$message_resource=$objcat->show_message_resource_delete($course_id);

		if (!$is_course_admin && $status<>1 && $selectcat<>0) {
			$user_id = api_get_user_id();
			$user= get_user_info_from_id($user_id);

			$catcourse	  = Category::load($catobj->get_id());
			$scoredisplay = ScoreDisplay :: instance();
			$scorecourse  = $catcourse[0]->calc_score($user_id);

			// generating the total score for a course
			$allevals= $catcourse[0]->get_evaluations($user_id,true);
			$alllinks= $catcourse[0]->get_links($user_id,true);
			$evals_links = array_merge($allevals, $alllinks);
			$item_value=0;
			$item_total=0;
            
            //@todo move these in a function            
            $sum_categories_weight_array = array();     
            if (isset($catobj) && !empty($catobj)) {            
                $categories = Category::load(null, null, null, $catobj->get_id());
                if (!empty($categories)) {
                    foreach($categories as $category) {                  
                        $sum_categories_weight_array[$category->get_id()] = $category->get_weight();
                    }
                } else {
                    $sum_categories_weight_array[$catobj->get_id()] = $catobj->get_weight();
                }
            }
                        
            $item_total_value = 0;            
           
			for ($count=0; $count < count($evals_links); $count++) {
				$item           = $evals_links[$count];
				$score          = $item->calc_score($user_id);
                $my_score_denom =($score[1]==0) ? 1 : $score[1];
				$item_value     = $score[0]/$my_score_denom * $item->get_weight();
                
                
                $sub_cat_percentage = $sum_categories_weight_array[$item->get_category_id()];
                $percentage     = round($item->get_weight()/($sub_cat_percentage) *  $sub_cat_percentage/$catobj->get_weight(), 2);
                
                //$item_value     = $percentage*$item_value;                
                
				//$item_total         += $percentage*100;
                $item_total         += $item->get_weight();                
                $item_total_value   += $item_value;
				//$row[] = $scoredisplay->display_score($score,SCORE_DIV_PERCENT);				
			}
            
			$item_value = number_format($item_total_value, 2);
			$total_score = array($item_value, $item_total);
		
			$scorecourse_display = $scoredisplay->display_score($total_score, SCORE_DIV_PERCENT);

			$cattotal = Category :: load(0);
			$scoretotal= $cattotal[0]->calc_score(api_get_user_id());
			$scoretotal_display = (isset($scoretotal) ? $scoredisplay->display_score($scoretotal, SCORE_PERCENT) : get_lang('NoResultsAvailable'));
			//$scoreinfo = get_lang('StatsStudent') . ' :<b> '.api_get_person_name($user['firstname'], $user['lastname']).'</b><br />';
			if ((!$catobj->get_id() == '0') && (!isset ($_GET['studentoverview'])) && (!isset ($_GET['search']))) {
				$scoreinfo.= '<h2>'.get_lang('Total') . ' : ' . $scorecourse_display . '</h2>';
			}
			Display :: display_normal_message($scoreinfo, false);
		}
		// show navigation tree and buttons?
		
		$header = '<div class="actions"><table border=0>';
		
		if (($showtree == '1') || (isset ($_GET['studentoverview']))) {
			$header .= '<tr>';
			if (!$selectcat == '0') {
				$header .= '<td style=" "><a href="' . api_get_self() . '?selectcat=' . $catobj->get_parent_id() . '">'.Display::return_icon('back.png',get_lang('BackTo').' '.get_lang('RootCat'),'','32').'</a></td>';
			}
			$header .= '<td>' . get_lang('CurrentCategory') . '</td>' .
					'<td><form name="selector"><select name="selectcat" onchange="document.selector.submit()">';
			$cats= Category :: load();

			$tree= $cats[0]->get_tree();
			unset ($cats);
			
			foreach ($tree as $cat) {
				for ($i= 0; $i < $cat[2]; $i++) {
					$line .= '&mdash;';
				}
				$line=isset($line) ? $line : '';
				if (isset($_GET['selectcat']) && $_GET['selectcat'] == $cat[0]) {
					$header .= '<option selected value=' . $cat[0] . '>' . $line . ' ' . $cat[1] . '</option>';
				} else {
					$header .= '<option value=' . $cat[0] . '>' . $line . ' ' . $cat[1] . '</option>';
				}
				$line= '';
			}
			$header .= '</select></form></td>';
            if (!empty($simple_search_form) && $message_resource===false) {
			    $header .= '<td style="vertical-align: top;">'.$simple_search_form->toHtml().'</td>';
            } else {
            	$header .= '<td></td>';
            }
			if ($is_course_admin && $message_resource===false && $_GET['selectcat']!=0) {
				/*$header .= '<td style="vertical-align: top;"><a href="gradebook_flatview.php?'.api_get_cidreq().'&selectcat=' . $catobj->get_id() . '"><img src="../img/view_list.gif" alt="' . get_lang('FlatView') . '" /> ' . get_lang('FlatView') . '</a>';
				if ($is_course_admin && $message_resource===false) {
					$header .= '<td style="vertical-align: top;"><a href="gradebook_scoring_system.php?'.api_get_cidreq().'&selectcat=' . $catobj->get_id() .'"><img src="../img/acces_tool.gif" alt="' . get_lang('ScoreEdit') . '" /> ' . get_lang('ScoreEdit') . '</a>';
				}*/
			} elseif (!(isset ($_GET['studentoverview']))) {
				if ( $message_resource===false ) {
					//$header .= '<td style="vertical-align: top;"><a href="'.api_get_self().'?'.api_get_cidreq().'&studentoverview=&selectcat=' . $catobj->get_id() . '"><img src="../img/view_list.gif" alt="' . get_lang('FlatView') . '" /> ' . get_lang('FlatView') . '</a>';
				}
			} else {
				$header .= '<td style="vertical-align: top;"><a href="'.api_get_self().'?'.api_get_cidreq().'&studentoverview=&exportpdf=&selectcat=' . $catobj->get_id() . '" target="_blank">
							<img src="../img/icons/32/.png" alt="' . get_lang('ExportPDF') . '" /> ' . get_lang('ExportPDF') . '</a>';
			}
			$header .= '</td></tr>';
		}
		$header.='</table></div>';

		// for course admin & platform admin add item buttons are added to the header
		$header .= '<div class="actions">';
		
		$my_category = $catobj->shows_all_information_an_category($catobj->get_id());
		$user_id     = api_get_user_id();
		$course_code = $my_category['course_code'];
		$status_user = api_get_status_of_user_in_course ($user_id,$course_code);
		
		
		if (api_is_allowed_to_edit(null, true)) {
			$header .= '<a href="gradebook_add_cat.php?'.api_get_cidreq().'&selectcat='.$catobj->get_id().'"><img src="../img/icons/32/new_folder.png" alt="' . get_lang('AddGradebook') . '" /></a></td>';			
			
			if ($selectcat == '0') {
                if ($show_add_qualification === true) {				   
                }
                if ($show_add_link) {
				    //$header .= '<td><a href="gradebook_add_eval.php?'.api_get_cidreq().'"><img src="../img/filenew.gif" alt="' . get_lang('NewEvaluation') . '" /> ' . get_lang('NewEvaluation') . '</a>';
                }
			} else {
                if ($show_add_qualification === true && $message_resource===false) {
    				//$header .= '<a href="gradebook_add_cat.php?'.api_get_cidreq().'&selectcat=' . $catobj->get_id() . '" ><img src="../img/folder_new.gif" alt="' . get_lang('NewSubCategory') . '" align="absmiddle" /> ' . get_lang('NewSubCategory') . '</a></td>';
                }
               $my_category=$catobj->shows_all_information_an_category($catobj->get_id());
				$my_api_cidreq = api_get_cidreq();
				if ($my_api_cidreq=='') {
					$my_api_cidreq='cidReq='.$my_category['course_code'];
				}
                if ($show_add_link && !$message_resource) {
    				$header .= '<td><a href="gradebook_add_eval.php?'.$my_api_cidreq.'&selectcat=' . $catobj->get_id() . '" >'.Display::return_icon('new_evaluation.png', get_lang('NewEvaluation'),'','32').'</a>';
                    $cats= Category :: load($selectcat);
                    if ($cats[0]->get_course_code() != null && !$message_resource) {
                        //$header .= '<td><a href="gradebook_add_link.php?'.api_get_cidreq().'&selectcat=' . $catobj->get_id() . '"><img src="../img/link.gif" alt="' . get_lang('MakeLink') . '" align="absmiddle" /> ' . get_lang('MakeLink') . '</a>';
                        $header .= '<td><a href="gradebook_add_link.php?'.$my_api_cidreq.'&selectcat=' . $catobj->get_id() . '">'.Display::return_icon('new_online_evaluation.png', get_lang('MakeLink'),'','32').'</a>';

                    } else {
                        $header .= '<td><a href="gradebook_add_link_select_course.php?'.$my_api_cidreq.'&selectcat=' . $catobj->get_id() . '">'.Display::return_icon('new_online_evaluation.png', get_lang('MakeLink'),'','32').'</a>';
                    }
                }

                if (!$message_resource) {
                	$myname = $catobj->shows_all_information_an_category($catobj->get_id());
                 	
                	$my_course_id=api_get_course_id();
                	$my_file= substr($_SESSION['gradebook_dest'],0,5);
                		
					$header .= '<td style="vertical-align: top;"><a href="gradebook_flatview.php?'.$my_api_cidreq.'&selectcat=' . $catobj->get_id() . '">'.Display::return_icon('stats.png', get_lang('FlatView'),'','32').'</a>';					
					$header .= '<td style="vertical-align: top;"><a href="gradebook_display_certificate.php?'.$my_api_cidreq.'&amp;cat_id='.(int)$_GET['selectcat'].'">'.Display::return_icon('certificate_list.png', get_lang('GradebookSeeListOfStudentsCertificates'),'','32').'</a>';
					
					$visibility_icon    = ($catobj->is_visible() == 0) ? 'invisible' : 'visible';
			        $visibility_command = ($catobj->is_visible() == 0) ? 'set_visible' : 'set_invisible';
			        
					//Right icons
					
            		$modify_icons  = '<a href="gradebook_edit_cat.php?editcat='.$catobj->get_id().'&amp;cidReq='.$catobj->get_course_code().'">'.Display::return_icon('edit.png', get_lang('Edit'),'','32').'</a>';
            		$modify_icons .= '<a href="../document/document.php?curdirpath=/certificates&'.$my_api_cidreq.'&origin=gradebook&selectcat=' . $catobj->get_id() . '">'.
            							Display::return_icon('certificate.png', get_lang('AttachCertificate'),'','32').'</a>';
            		
            		$modify_icons .= '<a href="gradebook_edit_all.php?id_session='.api_get_session_id().'&amp;'.$my_api_cidreq.'&selectcat=' . $catobj->get_id() . '">'.Display::return_icon('percentage.png', get_lang('EditAllWeights'),'','32').'</a>';
            		
            		$modify_icons .= '<a href="gradebook_scoring_system.php?'.$my_api_cidreq.'&selectcat=' . $catobj->get_id() .'">'.Display::return_icon('ranking.png', get_lang('ScoreEdit'),'','32').'</a>';
            		
            		//hide or delete are not options available
            		//$modify_icons .= '&nbsp;<a  href="' . api_get_self() . '?visiblecat=' . $catobj->get_id() . '&amp;' . $visibility_command . '=&amp;selectcat=0 ">'.Display::return_icon($visibility_icon.'.png', get_lang('Visible'),'','32').'</a>';
            		//$modify_icons .= '&nbsp;<a  href="' . api_get_self() . '?deletecat=' . $catobj->get_id() . '&amp;selectcat=0&amp;cidReq='.$catobj->get_course_code().'" onclick="return confirmation();">'.Display::return_icon('delete.png', get_lang('DeleteAll'),'','32').'</a>';
            			
            		$header .= Display::div($modify_icons, array('class'=>'right'));
					
                }
			}
		} elseif (isset ($_GET['search'])) {
			$header .= '<b>'.get_lang('SearchResults').' :</b>';
		}
		$header .= '</div>';
		echo $header;
		
		
		if (api_is_allowed_to_edit(null, true)) {
            $weight = ((intval($catobj->get_weight())>0) ? $catobj->get_weight() : 0);                        			
    		$weight            = Display::tag('h3', get_lang('TotalWeight').' : '.$weight);    		
    		
    		$min_certification = (intval($catobj->get_certificate_min_score()>0) ? $catobj->get_certificate_min_score() : 0);
    		$min_certification = Display::tag('h3', get_lang('CertificateMinScore').' : '.$min_certification);
    		//@todo show description
    		$description       = (($catobj->get_description() == "" || is_null($catobj->get_description())) ? '' : '<strong>'.get_lang('GradebookDescriptionLog').'</strong>'.': '.$catobj->get_description());    				
    		Display::display_normal_message($weight.$min_certification, false);
    		if (!empty($description)) {
    		    echo Display::div($description, array());
    		}
		}
	}

	function display_reduce_header_gradebook($catobj,$is_course_admin, $is_platform_admin, $simple_search_form, $show_add_qualification = true, $show_add_link = true) {
		//student
		if (!$is_course_admin) {
			$user= get_user_info_from_id(api_get_user_id());
			$catcourse= Category :: load($catobj->get_id());
			$scoredisplay = ScoreDisplay :: instance();
			$scorecourse = $catcourse[0]->calc_score(api_get_user_id());
			$scorecourse_display = (isset($scorecourse) ? $scoredisplay->display_score($scorecourse,SCORE_AVERAGE) : get_lang('NoResultsAvailable'));
			$cattotal = Category :: load(0);
			$scoretotal= $cattotal[0]->calc_score(api_get_user_id());
			$scoretotal_display = (isset($scoretotal) ? $scoredisplay->display_score($scoretotal,SCORE_PERCENT) : get_lang('NoResultsAvailable'));
			$scoreinfo = get_lang('StatsStudent') . ' :<b> '.api_get_person_name($user['firstname'], $user['lastname']).'</b><br />';
			if ((!$catobj->get_id() == '0') && (!isset ($_GET['studentoverview'])) && (!isset ($_GET['search'])))
				$scoreinfo.= '<br />'.get_lang('TotalForThisCategory') . ' : <b>' . $scorecourse_display . '</b>';
			$scoreinfo.= '<br />'.get_lang('Total') . ' : <b>' . $scoretotal_display . '</b>';
			Display :: display_normal_message($scoreinfo,false);

		}
			// show navigation tree and buttons?
			$header = '<div class="actions">';

			if ($is_course_admin) {
				$header .= '<a href="gradebook_flatview.php?'.api_get_cidreq().'&selectcat=' . $catobj->get_id() . '">'.Display::return_icon('stats.png', get_lang('FlatView'),'','32').'</a>';
				$header .= '<a href="gradebook_scoring_system.php?'.api_get_cidreq().'&selectcat=' . $catobj->get_id() .'">'.Display::return_icon('settings.png', get_lang('ScoreEdit'),'','32').'</a>';
			} elseif (!(isset ($_GET['studentoverview']))) {
				$header .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&studentoverview=&selectcat=' . $catobj->get_id() . '">'.Display::return_icon('view_list.gif', get_lang('FlatView')).' ' . get_lang('FlatView') . '</a>';
			} else {
				$header .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&studentoverview=&exportpdf=&selectcat=' . $catobj->get_id() . '" target="_blank">'.Display::return_icon('pdf.png', get_lang('ExportPDF'),'','32').'</a>';
			}
		$header.='</div>';
		echo $header;
	}


	function display_header_user($userid) {
		$select_cat   = intval($_GET['selectcat']);
		$user_id      = $userid;
		$user         = get_user_info_from_id($user_id);

		$catcourse    = Category :: load($select_cat);
		$scoredisplay = ScoreDisplay :: instance();
		$scorecourse  = $catcourse[0]->calc_score($user_id);

		// generating the total score for a course
		$allevals= $catcourse[0]->get_evaluations($user_id,true);
		$alllinks= $catcourse[0]->get_links($user_id,true);
		$evals_links = array_merge($allevals, $alllinks);
		$item_value=0;
		$item_total=0;
		for ($count=0; $count < count($evals_links); $count++) {
			$item = $evals_links[$count];
			$score = $item->calc_score($user_id);
			$my_score_denom=($score[1]==0) ? 1 : $score[1];
			$item_value+=$score[0]/$my_score_denom*$item->get_weight();
			$item_total+=$item->get_weight();
			//$row[] = $scoredisplay->display_score($score,SCORE_DIV_PERCENT);
		}
		$item_value = number_format($item_value, 2, '.', ' ');
		$total_score=array($item_value,$item_total);
		$scorecourse_display = $scoredisplay->display_score($total_score,SCORE_DIV_PERCENT);
		//----------------------

		//$scorecourse_display = (isset($scorecourse) ? $scoredisplay->display_score($scorecourse,SCORE_AVERAGE) : get_lang('NoResultsAvailable'));
		$cattotal = Category :: load(0);
		$scoretotal= $cattotal[0]->calc_score($user_id);
		$scoretotal_display = (isset($scoretotal) ? $scoredisplay->display_score($scoretotal,SCORE_PERCENT) : get_lang('NoResultsAvailable'));

		$image_syspath = UserManager::get_user_picture_path_by_id($userid,'system',false,true);
        $image_size = getimagesize($image_syspath['dir'].$image_syspath['file']);
        //Web path
        $image_path = UserManager::get_user_picture_path_by_id($userid,'web',false,true);
        $image_file = $image_path['dir'].$image_path['file'];
		$img_attributes= 'src="' . $image_file . '?rand=' . time() . '" ' . 'alt="' . api_get_person_name($user['firstname'], $user['lastname']) . '" ';
		if ($image_size[0] > 200) {
		 //limit display width to 200px
 			$img_attributes .= 'width="200" ';
		}
		$info = '<table width="100%" border=0 cellpadding=5><tr><td width="80%">';
		$info.= get_lang('Name') . ' :  <a target="_blank" href="'.api_get_path(WEB_CODE_PATH).'social/profile.php?u=' . $userid . '"> ' . api_get_person_name($user['firstname'], $user['lastname']) . '</a><br />';
		$info.= get_lang('Email') . ' : <a href="mailto:' . $user['email'] . '">' . $user['email'] . '</a><br />';
		$info.= get_lang('TotalUser') . ' : <b>' . $scorecourse_display . '</b><br>';
		$info.= '</td><td>';
		$info.= '<img ' . $img_attributes . '/></td></tr></table>';


	//--------------
		//$scoreinfo = get_lang('StatsStudent') . ' :<b> '.api_get_person_name($user['lastname'], $user['firstname']).'</b><br />';
		//$scoreinfo.= '<br />'.get_lang('Total') . ' : <b>' . $scorecourse_display . '</b>';

		//$scoreinfo.= '<br />'.get_lang('Total') . ' : <b>' . $scoretotal_display . '</b>';
		Display :: display_normal_message($info,false);
	}
}
