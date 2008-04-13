<?php
/*
 * Created on 25-apr-07
 *
 * @author Stijn Konings
 * 
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
	function display_header_result($evalobj, $selectcat, $shownavbar)
	{
		if ($shownavbar == '1')
		{
			$header= '<table border=0 cellpadding=5><tr><td>';
			$header .= '<a href=gradebook.php?selectcat=' . $selectcat . '><img src=../img/lp_leftarrow.gif alt=' . get_lang('BackToOverview') . ' align=absmiddle/> ' . get_lang('BackToOverview') . '</a></td>';
			if ($evalobj->get_course_code() == null)
			{
				$header .= '<td><a href=gradebook_add_user.php?selecteval=' . $evalobj->get_id() . '><img src=../img/add_user_big.gif alt=' . get_lang('AddStudent') . ' align=absmiddle/> ' . get_lang('AddStudent') . '</a></td>';
			}
			elseif (!$evalobj->has_results())
			{
				$header .= '<td><a href=gradebook_add_result.php?selectcat=' . $selectcat . '&selecteval=' . $evalobj->get_id() . '><img src=../img/filenew.gif alt=' . get_lang('AddResult') . ' align=absmiddle/> ' . get_lang('AddResult') . '</a></td>';
			}
			$header .= '<td><a href=' . api_get_self() . '?&selecteval=' . $evalobj->get_id() . '&import=><img src="../img/calendar_down.gif" border="0" alt="" />' . ' ' . get_lang('ImportResult') . '</a></td>';
			if ($evalobj->has_results())
			{
				$header .= '<td><a href=' . api_get_self() . '?&selecteval=' . $evalobj->get_id() . '&export=><img src="../img/calendar_up.gif" border="0" alt="" />' . ' ' . get_lang('ExportResult') . '</a></td>';
				$header .= '<td><a href=gradebook_edit_result.php?selecteval=' . $evalobj->get_id() .'><img src=../img/works.gif alt=' . get_lang('EditResult') . ' align=absmiddle/> ' . get_lang('EditResult') . '</a></td>';
				$header .= '<td><a href=' . api_get_self() . '?&selecteval=' . $evalobj->get_id() . '&deleteall= onclick="return confirmationall();"><img src="../img/delete.gif" border="0" alt="" />' . ' ' . get_lang('DeleteResult') . '</a></td>';
			}
			$header .= '<td><a href=' . api_get_self() . '?print=&selecteval=' . $evalobj->get_id() . ' target="_blank"><img src=../img/printmgr.gif alt=' . get_lang('Print') . '/> ' . get_lang('Print') . '</a>';
			$header .= '</td></tr></table>';
		}
		if ($evalobj->is_visible() == '1')
		{
			$visible= get_lang('Yes');
		} else
		{
			$visible= get_lang('No');
		}
		
		$scoredisplay = ScoreDisplay :: instance(); 
		if (($evalobj->has_results())) // TODO this check needed ?
		{
			
			$score= $evalobj->calc_score();
			if ($score != null)
				$average= get_lang('Average') . ' :<b> ' .$scoredisplay->display_score($score,SCORE_AVERAGE) . '</b>';
		}
		if (!$evalobj->get_description() == '')
		{
			$description= get_lang('Description') . ' :<b> ' . $evalobj->get_description() . '</b><br>';
		}
		if ($evalobj->get_course_code() == null)
			$course= get_lang('CourseIndependent');
		else
			$course= get_course_name_from_code($evalobj->get_course_code());
		$evalinfo= '<table width=100% border=0><tr><td>';
		$evalinfo .= get_lang('EvaluationName') . ' :<b> ' . $evalobj->get_name() . ' </b>(' . date('j/n/Y g:i', $evalobj->get_date()) . ')<br>' . get_lang('Course') . ' :<b> ' . $course . '</b><br>' . get_lang('Weight') . ' :<b> ' . $evalobj->get_weight() . '</b><br>' . get_lang('Max') . ' :<b> ' . $evalobj->get_max() . '</b><br>' . $description . get_lang('Visible') . ' :<b> ' . $visible . '</b><br>' . $average;
		if (!$evalobj->has_results())
			$evalinfo .= '<br><i>' . get_lang('NoResultsInEvaluation') . '</i>';
		elseif ($scoredisplay->is_custom() && api_get_self() != '/dokeos/main/gradebook/gradebook_statistics.php')
			$evalinfo .= '<br><br><a href="gradebook_statistics.php?selecteval='.Security::remove_XSS($_GET['selecteval']).'"> '. get_lang('ViewStatistics') . '</a>';
		$evalinfo .= '</td><td align=right><img src="../img/tutorial.gif"></img></td></table>';
		Display :: display_normal_message($evalinfo,false);
		echo $header;

	}
	/**
	* Displays the header for the flatview page containing filters
	* @param $catobj
	* @param $showeval
	* @param $showlink
	*/
	function display_header_flatview($catobj, $showeval, $showlink,$simple_search_form)
	{
		$header= '<table border=0 cellpadding=5>';
		$header .= '<td style="vertical-align: top;"><a href=gradebook.php?selectcat=' . Security::remove_XSS($_GET['selectcat']) . '><< ' . get_lang('BackToOverview') . '</a></td>';
		$header .= '<td style="vertical-align: top;">' . get_lang('FilterCategory') . '</td><td style="vertical-align: top;"><form name=selector><select name=selectcat onchange="document.selector.submit()">';
		$cats= Category :: load();
		$tree= $cats[0]->get_tree();
		unset ($cats);
		foreach ($tree as $cat)
		{
			for ($i= 0; $i < $cat[2]; $i++)
			{
				$line .= '&mdash;';
			}
			if ($_GET['selectcat'] == $cat[0])
			{
				$header .= '<option selected value=' . $cat[0] . '>' . $line . ' ' . $cat[1] . '</option>';
			} else
			{
				$header .= '<option value=' . $cat[0] . '>' . $line . ' ' . $cat[1] . '</option>';
			}
			$line= '';
		}
		$header .= '</td></select></form>';
		if (!$catobj->get_id() == '0')
			$header .= '<td style="vertical-align: top;"><a href=' . api_get_self() . '?selectcat=' . $catobj->get_parent_id() . '><img src="../img/folder_up.gif" border="0" alt="'.get_lang('Up').'" /></a></td>';
		$header .= '<td style="vertical-align: top;">'.$simple_search_form->toHtml().'</td>';
		$header .= '<td style="vertical-align: top;"><a href="' . api_get_self() . '?exportpdf=&offset='.Security::remove_XSS($_GET['offset']).'&search=' . Security::remove_XSS($_GET['search']).'&selectcat=' . $catobj->get_id() . '"><img src=../img/calendar_up.gif alt=' . get_lang('ExportPDF') . '/> ' . get_lang('ExportPDF') . '</a>';
		$header .= '<td style="vertical-align: top;"><a href="' . api_get_self() . '?print=&selectcat=' . $catobj->get_id() . '" target="_blank"><img src="../img/printmgr.gif" alt=' . get_lang('Print') . '/> ' . get_lang('Print') . '</a>';
		$header .= '</td></tr></table>';
		if (!$catobj->get_id() == '0')
		{
			$header .= '<table border=0 cellpadding=5><tr><td><form name=itemfilter method=post action=' . api_get_self() . '?selectcat=' . $catobj->get_id() . '><input type="checkbox" name=showeval onclick="document.itemfilter.submit()" ' . (($showeval == '1') ? 'checked' : '') . '>Show Evaluations &nbsp;';
			$header .= '<input type="checkbox" name=showlink onclick="document.itemfilter.submit()" ' . (($showlink == '1') ? 'checked' : '') . '>Show Links</form></td></tr></table>';
		}
		if (isset ($_GET['search']))
			$header .= '<b>'.get_lang('SearchResults').' :</b>';
		echo $header;
	}
	/**
	 * Displays the header for the gradebook containing the navigation tree and links
	 * @param category_object $currentcat
	 * @param int $showtree '1' will show the browse tree and naviation buttons
	 * @param boolean $is_course_admin
	 * @param boolean $is_platform_admin
	 */
	function display_header_gradebook($catobj, $showtree, $selectcat, $is_course_admin, $is_platform_admin,$simple_search_form)
	{
		//student
		if (!$is_course_admin)
		{
			$user= get_user_info_from_id(api_get_user_id());
			$catcourse= Category :: load($catobj->get_id());
			$scoredisplay = ScoreDisplay :: instance();
			$scorecourse = $catcourse[0]->calc_score(api_get_user_id());
			$scorecourse_display = (isset($scorecourse) ? $scoredisplay->display_score($scorecourse,SCORE_AVERAGE) : get_lang('NoResultsAvailable'));
			$cattotal = Category :: load(0);
			$scoretotal= $cattotal[0]->calc_score(api_get_user_id());
			$scoretotal_display = (isset($scoretotal) ? $scoredisplay->display_score($scoretotal,SCORE_PERCENT) : get_lang('NoResultsAvailable'));
			$scoreinfo = get_lang('StatsStudent') . ' :<b> '.$user['lastname'].' '.$user['firstname'].'</b><br />';
			if ((!$catobj->get_id() == '0') && (!isset ($_GET['studentoverview'])) && (!isset ($_GET['search'])))
				$scoreinfo.= '<br />'.get_lang('TotalForThisCategory') . ' : <b>' . $scorecourse_display . '</b>';
			$scoreinfo.= '<br />'.get_lang('Total') . ' : <b>' . $scoretotal_display . '</b>';
			Display :: display_normal_message($scoreinfo,false);
		}
		// show navigation tree and buttons?
		$header .= '<table border=0 cellpadding=5>';
		if (($showtree == '1') || (isset ($_GET['studentoverview'])))
		{
			
			$header .= '<tr><td style="vertical-align: top;">' . get_lang('CurrentCategory') . '</td><td style="vertical-align: top;"><form name=selector><select name=selectcat onchange="document.selector.submit()">';
			$cats= Category :: load();
			$tree= $cats[0]->get_tree();
			unset ($cats);
			foreach ($tree as $cat)
			{
				for ($i= 0; $i < $cat[2]; $i++)
				{
					$line .= '&mdash;';
				}
				if ($_GET['selectcat'] == $cat[0])
				{
					$header .= '<option selected value=' . $cat[0] . '>' . $line . ' ' . $cat[1] . '</option>';
				} else
				{
					$header .= '<option value=' . $cat[0] . '>' . $line . ' ' . $cat[1] . '</option>';
				}
				$line= '';
			}
			$header .= '</select></form></td>';
			if (!$selectcat == '0')
			{
				$header .= '<td style="vertical-align: top;"><a href=' . api_get_self() . '?selectcat=' . $catobj->get_parent_id() . '><img src="../img/folder_up.gif" border="0" alt="" /></a></td>';
			}
			$header .= '<td style="vertical-align: top;">'.$simple_search_form->toHtml().'</td>';
			if ($is_course_admin)
			{
				$header .= '<td style="vertical-align: top;"><a href=gradebook_flatview.php?selectcat=' . $catobj->get_id() . '><img src=../img/stats_access.gif alt=' . get_lang('FlatView') . '/> ' . get_lang('FlatView') . '</a>';
				if ($is_platform_admin)
					$header .= '<td style="vertical-align: top;"><a href="gradebook_scoring_system.php?selectcat=' . $catobj->get_id() .'"><img src=../img/acces_tool.gif alt=' . get_lang('ScoreEdit') . '/> ' . get_lang('ScoreEdit') . '</a>';
			}
			elseif (!(isset ($_GET['studentoverview'])))
			{
				$header .= '<td style="vertical-align: top;"><a href="'.api_get_self().'?studentoverview=&selectcat=' . $catobj->get_id() . '"><img src=../img/stats_access.gif alt=' . get_lang('FlatView') . '/> ' . get_lang('FlatView') . '</a>';
			}
			else
			{
				$header .= '<td style="vertical-align: top;"><a href="'.api_get_self().'?studentoverview=&exportpdf=&selectcat=' . $catobj->get_id() . '" target="_blank"><img src=../img/calendar_up.gif alt=' . get_lang('ExportPDF') . '/> ' . get_lang('ExportPDF') . '</a>';
			}
			$header .= '</td></tr>';
		}
		$header.='</table>';
		
		// for course admin & platform admin add item buttons are added to the header
		$header .= '<table border=0 cellpadding=0><tr><td>';
		if (($is_course_admin) && (!isset ($_GET['search'])))
		{
			if ($selectcat == '0')
			{
				$header .= '<a href=gradebook_add_cat.php?selectcat=0><img src=../img/folder_new.gif alt=' . get_lang('NewCategory') . '/> ' . get_lang('NewCategory') . '</a></td>';
				$header .= '<td><a href=gradebook_add_eval.php><img src=../img/filenew.gif alt=' . get_lang('NewEvaluation') . '/> ' . get_lang('NewEvaluation') . '</a>';
			} else
			{
				$header .= '<a href=gradebook_add_cat.php?selectcat=' . $catobj->get_id() . '><img src=../img/folder_new.gif alt=' . get_lang('NewSubCategory') . ' align=absmiddle/> ' . get_lang('NewSubCategory') . '</a></td>';
				$header .= '<td><a href=gradebook_add_eval.php?selectcat=' . $catobj->get_id() . '><img src=../img/filenew.gif alt=' . get_lang('NewEvaluation') . ' align=absmiddle/> ' . get_lang('NewEvaluation') . '</a>';
				$cats= Category :: load($selectcat);
				if ($cats[0]->get_course_code() != null)
				{
					$header .= '<td><a href=gradebook_add_link.php?&selectcat=' . $catobj->get_id() . '><img src=../img/link.gif alt=' . get_lang('MakeLink') . ' align=absmiddle/> ' . get_lang('MakeLink') . '</a>';
				}
				else
				{
					$header .= '<td><a href=gradebook_add_link_select_course.php?&selectcat=' . $catobj->get_id() . '><img src=../img/link.gif alt=' . get_lang('MakeLink') . ' align=absmiddle/> ' . get_lang('MakeLink') . '</a>';
				}
			}
		}
		elseif (isset ($_GET['search']))
		{
			$header .= '<b>'.get_lang('SearchResults').' :</b>';
		}

		$header .= '</td></tr></table>';
		echo $header;
	}
	
	function display_header_user($userid)
	{
		$user= get_user_info_from_id($userid);
		$image= $user['picture_uri'];
		$image_file= ($image != '' ? api_get_path(WEB_CODE_PATH) . "upload/users/$image" : api_get_path(WEB_CODE_PATH) . 'img/unknown.jpg');
		$image_size= @ getimagesize($image_file);
		$img_attributes= 'src="' . $image_file . '?rand=' . time() . '" ' . 'alt="' . $user['lastname'] . ' ' . $user['firstname'] . '" ';
		if ($image_size[0] > 200) //limit display width to 200px
			$img_attributes .= 'width="200" ';
		$cattotal= Category :: load(0);
		$info = '<table width="100%" border=0 cellpadding=5><tr><td width="80%">';
		$info.= get_lang('Name') . ' : <b>' . $user['lastname'] . ' ' . $user['firstname'] . '</b> ( <a href="user_info.php?userid=' . $userid . '&selecteval=' . Security::remove_XSS($_GET['selecteval']) . '">' . get_lang('MoreInfo') . '...</a> )<br>';
		$info.= get_lang('Email') . ' : <b><a href="mailto:' . $user['email'] . '">' . $user['email'] . '</a></b><br><br>';
		$scoredisplay = ScoreDisplay :: instance(); 
		$score_stud= $cattotal[0]->calc_score($userid);
		$score_stud_display = (isset($score_stud) ? $scoredisplay->display_score($score_stud,SCORE_PERCENT) : get_lang('NoResultsAvailable') );
		$score_avg= $cattotal[0]->calc_score();
		$score_avg_display = (isset($score_avg) ? $scoredisplay->display_score($score_avg,SCORE_AVERAGE) : get_lang('NoResultsAvailable') );
		$info.= get_lang('TotalUser') . ' : <b>' . $score_stud_display . '</b><br>';
		$info.= get_lang('AverageTotal') . ' : <b>' . $score_avg_display . '</b>';
		$info.= '</td><td>';
		$info.= '<img ' . $img_attributes . '/></td></tr></table>';
		echo Display :: display_normal_message($info,false);
	}
}
?>
