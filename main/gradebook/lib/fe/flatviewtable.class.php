<?php

include_once (dirname(__FILE__).'/../../../inc/global.inc.php');
include_once (dirname(__FILE__).'/../be.inc.php');

define ('LIMIT',10);

/**
 * Table to display flat view (all evaluations and links for all students)
 * @author Stijn Konings
 * @author Bert Stepp� (refactored, optimised)
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
	function FlatViewTable ($selectcat, $users= array (), $evals= array (), $links= array (), $limit_enabled = false, $offset = 0, $addparams = null)
	{
		parent :: SortableTable ('flatviewlist', null, null, 0);

		$this->datagen = new FlatViewDataGenerator($users, $evals, $links);

		$this->selectcat = $selectcat;
		$this->limit_enabled = $limit_enabled;
		$this->offset = $offset;
		
		if (isset ($addparams))
			$this->set_additional_parameters($addparams);
	}

	/**
	 * Function used by SortableTable to get total number of items in the table
	 */
	function get_total_number_of_items()
	{
		return $this->datagen->get_total_users_count();
	}

	/** 
	 * Function used by SortableTable to generate the data to display
	 */
	function get_table_data($from = 1)
	{

		// create page navigation if needed

		$totalitems = $this->datagen->get_total_items_count();
		if ($this->limit_enabled && $totalitems > LIMIT)
			$selectlimit = LIMIT;
		else
			$selectlimit = $totalitems;
		
		if ($this->limit_enabled && $totalitems > LIMIT)
		{
	      	$calcprevious = LIMIT;
			$header .= '<div class="normal-message">'
						.'<table style="width: 100%; text-align: left; margin-left: auto; margin-right: auto;" border="0" cellpadding="2">'
						.'<tbody>'
						.'<tr>';

			// previous X
	      	$header .= '<td style="width:40%;">';
	      	if ($this->offset >= LIMIT)
	      	{
	      		$header .= '<a href="'.api_get_self()
	      							.'?selectcat='.Security::remove_XSS($_GET['selectcat'])
	      							.'&offset='.(($this->offset)-LIMIT)
									.(isset($_GET['search'])?'&search='.Security::remove_XSS($_GET['search']):'').'">'
	      					.'<img src="../img/lp_leftarrow.gif" alt="'.get_lang('Previous').'/" />'
	      					.get_lang('Previous').' '.$calcprevious . ' ' . get_lang('Evaluations')
	      					.'</a>';
	      	}
	      	else
	      		$header .= '<img src="../img/lp_leftarrow.gif" alt="'.get_lang('Previous').' ' . get_lang('Evaluations').'/" />'.get_lang('Previous').' ' . get_lang('Evaluations');
	      	$header .= '</td>';

	      	// 'glue'
	      	$header .= '<td style="width:20%;"></td>';

			// next X
	      	$calcnext = (($this->offset+(2*LIMIT)) > $totalitems) ?
	      					($totalitems-(LIMIT+$this->offset)) : LIMIT;
      		$header .= '<td style="text-align: right; width: 40%;">';
      		if ($calcnext > 0)
      		{
	      		$header .= '<a href="'.api_get_self()
	      							.'?selectcat='.Security::remove_XSS($_GET['selectcat'])
	      							.'&offset='.($this->offset+LIMIT)
	      							.(isset($_GET['search'])?'&search='.Security::remove_XSS($_GET['search']):'').'">'
	      					.get_lang('Next').' '.$calcnext . ' '.get_lang('Evaluations')
	      					.'<img src="../img/lp_rightarrow.gif" alt="'.get_lang('Next').'/" />'
	      					.'</a>';
      		}
      		else
      		{
  				$header .= get_lang('Next').' '.get_lang('Evaluations').'<img src="../img/lp_rightarrow.gif" alt="'.get_lang('Next').'/" />';
	          			
      		}
      		$header .= '</td>';
	      	$header .= '</tr></tbody></table></div>';
			echo $header;
		}


		// retrieve sorting type
		$users_sorting = ($this->column == 0 ? FlatViewDataGenerator :: FVDG_SORT_LASTNAME
											 : FlatViewDataGenerator :: FVDG_SORT_FIRSTNAME);
		if ($this->direction == 'DESC')
			$users_sorting |= FlatViewDataGenerator :: FVDG_SORT_DESC;
		else
			$users_sorting |= FlatViewDataGenerator :: FVDG_SORT_ASC;



		// step 1: generate columns: evaluations and links

		$header_names = $this->datagen->get_header_names($this->offset, $selectlimit);

		$column = 0;
		$this->set_header($column++, $header_names[0]);
		$this->set_header($column++, $header_names[1]);

		while ($column < count($header_names))
		{
			$this->set_header($column, $header_names[$column], false);
			$column++;
		}


		// step 2: generate rows: students
		
		$data_array = $this->datagen->get_data($users_sorting,
										 $from, $this->per_page,
										 $this->offset, $selectlimit);

		$table_data = array();
		foreach ($data_array as $user_row)
		{
			$table_row = array ();
			$count = 0;
			$table_row[]= $this->build_name_link($user_row[$count++], $user_row[$count++]);
			$table_row[]= $user_row[$count++];
			while ($count < count($user_row))
				$table_row[] = $user_row[$count++];
			$table_data[]= $table_row;
		}
		return $table_data;
	}




	// Other functions

	private function build_name_link ($user_id, $lastname)
	{
		return '<a href="user_stats.php?userid='.$user_id.'&selectcat='.$this->selectcat->get_id().'">'.$lastname.'</a>';
	}

	
}
?>