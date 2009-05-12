<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2009 Dokeos Latinoamerica SAC
	Copyright (c) 2006 Dokeos SPRL
	Copyright (c) 2006 Ghent University (UGent)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
require_once (dirname(__FILE__).'/../../../inc/global.inc.php');
require_once (dirname(__FILE__).'/../be.inc.php');
set_time_limit(0);
define ('LIMIT',10);

/**
 * Table to display flat view (all evaluations and links for all students)
 * @author Stijn Konings
 * @author Bert Stepp� (refactored, optimised)
 * @author Julio Montoya Armas Gradebook Graphics 
 */
class FlatViewTable extends SortableTable
{
	private $selectcat;
	private $datagen;
	private $limit_enabled;
	private $offset;
	
	/**
	 * Constructor
	 */
	function FlatViewTable ($selectcat, $users= array (), $evals= array (), $links= array (), $limit_enabled = false, $offset = 0, $addparams = null) {
		parent :: SortableTable ('flatviewlist', null, null, 0);
		$this->datagen = new FlatViewDataGenerator($users, $evals, $links);
		$this->selectcat = $selectcat;		
		$this->limit_enabled = $limit_enabled;
		$this->offset = $offset;		
		if (isset ($addparams)) {
			$this->set_additional_parameters($addparams);
		}			
	}	
	
	/**
	 * Display the graph of the total results of all students
	 * */
	function display_graph()  
	{
		include_once(api_get_path(LIBRARY_PATH).'pchart/pData.class.php');
		include_once(api_get_path(LIBRARY_PATH).'pchart/pChart.class.php');
		include_once(api_get_path(LIBRARY_PATH).'pchart/pCache.class.php');
		
		//echo '<pre>';
		$header_name = $this->datagen->get_header_names();
		$total_users = $this->datagen->get_total_users_count();
		$img_file = '';
		
		if ($this->datagen->get_total_items_count()>0 && $total_users > 0 ) {				
			array_shift($header_name); 
			array_shift($header_name);
			array_pop($header_name);			
			$user_results = ($this->datagen->get_data_to_graph());				
			$pre_result = $new_result = array();
			$DataSet = new pData;
			//filling the Dataset			
			foreach($user_results as $result) {
				for($i=0; $i< count($header_name); $i++) {
					$pre_result[$i+3]+=$result[$i+1];				
				}			
			}

			$i=1;
			$show_draw = false;
			if ($total_users>0) {
				foreach($pre_result as $res) {			 
					$total =  $res / ($total_users*100);
					if ($total != 0)
						$show_draw  = true; 					
					$DataSet->AddPoint($total,"Serie".$i);					
					$DataSet->SetSerieName($header_name[$i-1],"Serie".$i);
					// Dataset definition   		  
					$DataSet->AddAllSeries();  
					$DataSet->SetAbsciseLabelSerie(); 
					$i++; 	
				}
			}			
			//print_r($pre_result); print_r($header_name);			
				
			// Cache definition   
			$Cache = new pCache();
			// the graph id
			$gradebook_id = Security::remove_XSS($_GET['selectcat']);			
			$graph_id = api_get_user_id().'AverageResultsVsResource'.$gradebook_id.api_get_course_id();
			$data = $DataSet->GetData();
		
			
			if ($show_draw) {				
				if ($Cache->IsInCache($graph_id, $DataSet->GetData())) {
				//if (0) {
					//if we already created the img
					//echo 'in cache';
					$img_file = $Cache->GetHash($graph_id,$DataSet->GetData());			
				} else  {		  
					// if the image does not exist in the archive/ folder
					
					// Initialise the graph  
					$Test = new pChart(760,360); 
				
					//which schema of color will be used
					$quant_resources = count($data[0])-1;					
					// Adding the color schemma
					if ($quant_resources < 8) {
						$Test->loadColorPalette(api_get_path(LIBRARY_PATH)."pchart/palette/reduced.txt");	
					} else {
						$Test->loadColorPalette(api_get_path(LIBRARY_PATH)."pchart/palette/default.txt");
					}					
					
					// set font of the axes 
					$Test->setFontProperties(api_get_path(LIBRARY_PATH)."pchart/fonts/tahoma.ttf",8);  
					$Test->setGraphArea(50,30,610,300);
					  
					$Test->drawFilledRoundedRectangle(7,7,780,330,5,240,240,240);  
					//$Test->drawRoundedRectangle(5,5,790,330,5,230,230,230);
					
					//background color area & stripe or not					
					$Test->drawGraphArea(255,255,255,TRUE);		
					$Test->drawScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_START0,150,150,150,TRUE,0,1, FALSE);
					
					//background grid     
					$Test->drawGrid(4,TRUE,230,230,230,50);  
					 
					// Draw the 0 line  
					//$Test->setFontProperties(api_get_path(LIBRARY_PATH)."pchart/fonts/tahoma.ttf",6);  
					//$Test->drawTreshold(0,143,55,72,TRUE,TRUE);  
					 
					// Draw the bar graph  
					$Test->drawBarGraph($DataSet->GetData(),$DataSet->GetDataDescription(),TRUE);  
					 
					//Set legend properties: width, height and text color and font
					$Test->setFontProperties(api_get_path(LIBRARY_PATH)."pchart/fonts/tahoma.ttf",9);  
					$Test->drawLegend(620, 70,$DataSet->GetDataDescription(),255,255,255);
					
					//Set title properties  
					$Test->setFontProperties(api_get_path(LIBRARY_PATH)."pchart/fonts/tahoma.ttf",10);  
					$Test->drawTitle(50,22,get_lang('AverageResultsVsResource'),50,50,80,620);
					
					//------------------						
					//echo 'not in cache';			
					$Cache->WriteToCache($graph_id,$DataSet->GetData(),$Test);						
					ob_start();
					$Test->Stroke();
					ob_end_clean();				
					$img_file = $Cache->GetHash($graph_id,$DataSet->GetData());
				}
			}		
		}
		return api_get_path(WEB_ARCHIVE_PATH).$img_file;
		//return '<div id="imageloaded" style="float:center;"><img src="'.api_get_path(WEB_ARCHIVE_PATH).$img_file_generated_name.'" ></div>';
	}
	
	function display_graph_by_resource() 
	{
		include_once(api_get_path(LIBRARY_PATH).'pchart/pData.class.php');
		include_once(api_get_path(LIBRARY_PATH).'pchart/pChart.class.php');
		include_once(api_get_path(LIBRARY_PATH).'pchart/pCache.class.php');		
		
		$header_name = $this->datagen->get_header_names();
		$total_users = $this->datagen->get_total_users_count();
		$img_file = '';

		if ($this->datagen->get_total_items_count()>0 && $total_users > 0 ) {						
			array_shift($header_name); 
			array_shift($header_name);
			
			$displayscore= ScoreDisplay :: instance();
			$customdisplays = $displayscore->get_custom_score_display_settings();
			if (is_array($customdisplays) && count(($customdisplays))) { 
				
				$user_results = ($this->datagen->get_data_to_graph2());	
				$pre_result = $new_result = array();			
				$DataSet = new pData;
				//filling the Dataset
				foreach($user_results as $result) {
					//print_r($result);	
					for($i=0; $i< count($header_name); $i++) {									
						$pre_result[$i+3][]=$result[$i+1];
						$pre_result_pie[$i+3][] = $result[$i+1][0]; 				
					}			
				}	
				/*$display_list = array();
				foreach( $customdisplays as $display) {
					$display_list[] = $display['display'];
				}	*/		
				
				$i=0;			
				$show_draw = false;
				$resource_list = array();			 
				$pre_result2 = array();
				//print_r($pre_result); exit;

				foreach($pre_result as $key=>$res_array) {
					rsort($res_array);			
					$pre_result2[] = $res_array;
				}			
				//print_r($pre_result2); 
						
				if ($total_users>0) {
					foreach($pre_result2 as $key=>$res_array) {					
						//$resource_list 			 
						//$total =  $res / ($total_users*100);				
						// mayor a menor					
						$key_list = array();
						foreach($res_array as $user_result) {												
							$resource_list[$key][$user_result[1]]+=1;
							$key_list[] = $user_result[1];						
						}
						//@todo when a display custom does not exist the order of the color does not match					
						//filling all the answer that are not responded with 0
						foreach($customdisplays as $display) {						
							if (!in_array($display['display'], $key_list))
								$resource_list[$key][$display['display']]=0;
						}
						$i++; 	
					}
				}			
				//print_r($customdisplays);		
				//print_r($resource_list); exit;
				$i = 1;
				$j = 0;
				// here-----------------------------------
				//print_r($resource_list);
				foreach($resource_list as $key=>$resource) {
					$new_resource_list = $new_resource_list_name = array();				
					$DataSet = new pData;		 		
					foreach($resource as $name=>$cant) {					
						//$new_resource_list[]=$cant;
						//$new_resource_list_name[]=$name;					
						$DataSet->AddPoint($cant,"Serie".$j);
						$DataSet->SetSerieName(strip_tags($name),"Serie".$j);
						$j++;
					}									  			
					//print_r($pre_result); print_r($header_name);			
					// Dataset definition   		  
					$DataSet->AddAllSeries(); 
					$DataSet->SetAbsciseLabelSerie('');
					$DataSet->SetXAxisName(get_lang('GradebookSkillsRanking')); 
					$DataSet->SetYAxisName(get_lang('Students')); 
					$show_draw = true;
					// Cache definition   
					$Cache = new pCache();
					// the graph id
					$gradebook_id = Security::remove_XSS($_GET['selectcat']);			
					$graph_id = api_get_user_id().'ByResource'.$gradebook_id.api_get_course_id();
										
					if ($show_draw) {			
						if ($Cache->IsInCache($graph_id, $DataSet->GetData())) {
						//if (0) {
							//if we already created the img we get the img file id 
							//echo 'in cache';
							$img_file = $Cache->GetHash($graph_id,$DataSet->GetData());			
						} else  {	  
							// if the image does not exist in the archive/ folder						
							// Initialise the graph						
							$chart_size_w= 480;
							$chart_size_h= 250;  
							
							$Test = new pChart($chart_size_w,$chart_size_h); 
						
							// Adding the color schemma
							$Test->loadColorPalette(api_get_path(LIBRARY_PATH)."pchart/palette/hard_blue.txt");
							
							// set font of the axes 
							$Test->setFontProperties(api_get_path(LIBRARY_PATH)."pchart/fonts/tahoma.ttf",8);
							$area_graph_w = $chart_size_w-130;  
							$Test->setGraphArea(50,30,$area_graph_w ,$chart_size_h-50);
							  
							$Test->drawFilledRoundedRectangle(5,5,$chart_size_w-1,$chart_size_h-20,5,240,240,240);  
							//$Test->drawRoundedRectangle(5,5,790,330,5,230,230,230);
							
							//background color area & stripe or not					
							$Test->drawGraphArea(255,255,255,TRUE);
							//print_r($DataSet->GetData());						
							$Test->drawScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_START0,150,150,150,TRUE,0,1, FALSE);
							
							//background grid     
							$Test->drawGrid(4,TRUE,230,230,230,50);  
							 
							// Draw the 0 line  
							//$Test->setFontProperties(api_get_path(LIBRARY_PATH)."pchart/fonts/tahoma.ttf",6);  
							//$Test->drawTreshold(0,143,55,72,TRUE,TRUE);  
							 
							// Draw the bar graph  
							$Test->drawBarGraph($DataSet->GetData(),$DataSet->GetDataDescription(),TRUE);  
							 
							//Set legend properties: width, height and text color and font
							$Test->setFontProperties(api_get_path(LIBRARY_PATH)."pchart/fonts/tahoma.ttf",9);  
							$Test->drawLegend($area_graph_w+10, 70,$DataSet->GetDataDescription(),255,255,255);
							
							//Set title properties  
							$Test->setFontProperties(api_get_path(LIBRARY_PATH)."pchart/fonts/tahoma.ttf",10);  
							$Test->drawTitle(50,22,$header_name[$i-1],50,50,80,$chart_size_w-50);
							
							//------------------						
							//echo 'not in cache';			
							$Cache->WriteToCache($graph_id,$DataSet->GetData(),$Test);						
							ob_start();
							$Test->Stroke();
							ob_end_clean();				
							$img_file = $Cache->GetHash($graph_id,$DataSet->GetData());
						}
						echo '<img src="'.api_get_path(WEB_ARCHIVE_PATH).$img_file.'" >';					
						if ($i % 2 == 0 && $i!=0) {
							echo '<br>';
						}
						$i++;					
					}
				} //end foreach
			} else {
				echo get_lang('ToViewGraphScoreRuleMustBeEnabled');
			}
			// Pie charts
			/*	
			$show_draw = false;
			$resource_list = array();						
			//print_r($pre_result_pie);			
						
			if ($total_users>0) {
				foreach($pre_result_pie as $key=>$res_array) {					
					//$resource_list					
					foreach($res_array as $user_result) {					
						$total+=  $user_result / ($total_users*100);						
					}	
					echo $total;
					//echo $total =  $res / ($total_users*100);
					echo '<br>';					 
					//$DataSet->AddPoint($total,"Serie".$i);
					//$DataSet->SetSerieName($header_name[$i-1],"Serie".$i);			  
					 	
				}
			}		
			//here-------------- 
			foreach($resource_list as $key=>$resource) {
				$new_resource_list = $new_resource_list_name = array();
			
				foreach($resource as $name=>$cant) {					
					$new_resource_list[]=$cant;
					$new_resource_list_name[]=$name;
				}
				//Pie chart 				
				$DataSet = new pData;  
				$DataSet->AddPoint($new_resource_list,"Serie1");  
				$DataSet->AddPoint($new_resource_list_name,"Serie2");  
				$DataSet->AddAllSeries();  
				$DataSet->SetAbsciseLabelSerie("Serie2");
				
				$Test = new pChart(400,300);  
				$Test->loadColorPalette(api_get_path(LIBRARY_PATH)."pchart/palette/soft_tones.txt");
				// background  
				//$Test->drawFilledRoundedRectangle(7,7,293,193,5,240,240,240);
				// border color  
				$Test->drawRoundedRectangle(5,5,295,195,5,230,230,230);  
				   
			    // This will draw a shadow under the pie chart  
				//$Test->drawFilledCircle(122,102,70,200,200,200);
								   
				 //Draw the pie chart  
				$Test->setFontProperties(api_get_path(LIBRARY_PATH)."pchart/fonts/tahoma.ttf",8);
				
				$Test->drawBarGraph($DataSet->GetData(),$DataSet->GetDataDescription(),TRUE);
				$tmp_path = api_get_path(SYS_ARCHIVE_PATH);
					
				$Test->drawBasicPieGraph($DataSet->GetData(),$DataSet->GetDataDescription(),120,100,70,PIE_PERCENTAGE,255,255,218);  
				$Test->drawPieLegend(230,15,$DataSet->GetData(),$DataSet->GetDataDescription(),250,250,250);
				$user_id = api_get_user_id();
				$img_file_generated_name = $key.uniqid('').'gradebook.png';			
				$Test->Render($tmp_path.$img_file_generated_name);
				chmod($tmp_path.$img_file_generated_name, 0777);
			
				if ($i % 2 == 0 && $i!= 0) {
					echo '<br>';
				}			
				echo '<img src="'.api_get_path(WEB_ARCHIVE_PATH).$img_file_generated_name.'">';								
			}
			*/
		}
	}
	/**
	 * Function used by SortableTable to get total number of items in the table
	 */
	function get_total_number_of_items () {
		return $this->datagen->get_total_users_count();
	}

	/** 
	 * Function used by SortableTable to generate the data to display
	 */
	function get_table_data ($from = 1) {

		// create page navigation if needed

		$totalitems = $this->datagen->get_total_items_count();
		if ($this->limit_enabled && $totalitems > LIMIT) {
			$selectlimit = LIMIT;
		} else {
			$selectlimit = $totalitems;
		}
		if ($this->limit_enabled && $totalitems > LIMIT) {
	      	$calcprevious = LIMIT;
			$header .= '<div class="normal-message">'
						.'<table style="width: 100%; text-align: left; margin-left: auto; margin-right: auto;" border="0" cellpadding="2">'
						.'<tbody>'
						.'<tr>';

			// previous X
	      	$header .= '<td style="width:40%;">';
	      	if ($this->offset >= LIMIT) {
	      		$header .= '<a href="'.api_get_self()
	      							.'?selectcat='.Security::remove_XSS($_GET['selectcat'])
	      							.'&offset='.(($this->offset)-LIMIT)
									.(isset($_GET['search'])?'&search='.Security::remove_XSS($_GET['search']):'').'">'
	      					.'<img src="../img/lp_leftarrow.gif" alt="'.get_lang('Previous').'/" />'
	      					.get_lang('Previous').' '.$calcprevious . ' ' . get_lang('Evaluations')
	      					.'</a>';
	      	} else {
	      		$header .= '<img src="../img/lp_leftarrow.gif" alt="'.get_lang('Previous').' ' . get_lang('Evaluations').'/" />'.get_lang('Previous').' ' . get_lang('Evaluations');
	      	}	
	      	$header .= '</td>';
	      	// 'glue'
	      	$header .= '<td style="width:20%;"></td>';
			// next X
	      	$calcnext = (($this->offset+(2*LIMIT)) > $totalitems) ?
	      					($totalitems-(LIMIT+$this->offset)) : LIMIT;
      		$header .= '<td style="text-align: right; width: 40%;">';
      		if ($calcnext > 0) {
	      		$header .= '<a href="'.api_get_self()
	      							.'?selectcat='.Security::remove_XSS($_GET['selectcat'])
	      							.'&offset='.($this->offset+LIMIT)
	      							.(isset($_GET['search'])?'&search='.Security::remove_XSS($_GET['search']):'').'">'
	      					.get_lang('Next').' '.$calcnext . ' '.get_lang('Evaluations')
	      					.'<img src="../img/lp_rightarrow.gif" alt="'.get_lang('Next').'/" />'
	      					.'</a>';
      		} else {
  				$header .= get_lang('Next').' '.get_lang('Evaluations').'<img src="../img/lp_rightarrow.gif" alt="'.get_lang('Next').'/" />';
	          			
      		}
      		$header .= '</td>';
	      	$header .= '</tr></tbody></table></div>';
			echo $header;
		}


		// retrieve sorting type
		$users_sorting = ($this->column == 0 ? FlatViewDataGenerator :: FVDG_SORT_LASTNAME
											 : FlatViewDataGenerator :: FVDG_SORT_FIRSTNAME);
		if ($this->direction == 'DESC') {
			$users_sorting |= FlatViewDataGenerator :: FVDG_SORT_DESC;
		} else {
			$users_sorting |= FlatViewDataGenerator :: FVDG_SORT_ASC;
		}
		// step 1: generate columns: evaluations and links

		$header_names = $this->datagen->get_header_names($this->offset, $selectlimit);

		$column = 0;
		$this->set_header($column++, $header_names[0]);
		$this->set_header($column++, $header_names[1]);

		while ($column < count($header_names)) {
			$this->set_header($column, $header_names[$column], false);
			$column++;
		}
		
		//$this->set_header($column++, get_lang('Total'));

		// step 2: generate rows: students
		
		$data_array = $this->datagen->get_data($users_sorting,
										 $from, $this->per_page,
										 $this->offset, $selectlimit);

		$table_data = array();
		foreach ($data_array as $user_row) {
			$table_row = array ();
			$count = 0;
			$table_row[]= $this->build_name_link($user_row[$count++], $user_row[$count++]);
			$table_row[]= $user_row[$count++];
			while ($count < count($user_row)) {
				$table_row[] = $user_row[$count++];	
			}
			$table_data[]= $table_row;
		}
		return $table_data;
	}




	// Other functions

	private function build_name_link ($user_id, $lastname) {
		return '<a href="user_stats.php?userid='.$user_id.'&selectcat='.$this->selectcat->get_id().'">'.$lastname.'</a>';
	}
}