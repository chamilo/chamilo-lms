<?php

include_once ('../../../inc/global.inc.php');
include_once ('../be.inc.php');

/**
 * Table to display categories, evaluations and links
 * @author Stijn Konings
 * @author Bert Stepp� (refactored, optimised)
 */
class GradebookTable extends SortableTable
{

	private $currentcat;
	private $datagen;


	/**
	 * Constructor
	 */
    function GradebookTable ($currentcat, $cats = array(), $evals = array(), $links = array(), $addparams = null)
    {
    	parent :: SortableTable ('gradebooklist', null, null, (api_is_allowed_to_create_course()?1:0));

		$this->currentcat = $currentcat;

		$this->datagen = new GradebookDataGenerator($cats, $evals, $links);
		
		if (isset($addparams))
			$this->set_additional_parameters($addparams);
	
		$column= 0;
		if (api_is_allowed_to_create_course())
			$this->set_header($column++, '', false);
		$this->set_header($column++, get_lang('Type'));
		$this->set_header($column++, get_lang('Name'));
		$this->set_header($column++, get_lang('Description'));
		$this->set_header($column++, get_lang('Weight'));
		$this->set_header($column++, get_lang('Date'));
		//admins get an edit column
		if (api_is_allowed_to_create_course())
		{
			$this->set_header($column++, get_lang('Modify'), false);
			//actions on multiple selected documents
			$this->set_form_actions(array (
				'delete' => get_lang('DeleteSelected'),
				'setvisible' => get_lang('SetVisible'),
				'setinvisible' => get_lang('SetInvisible')));
		}
		//students get a result column
		else
		{
			$this->set_header($column++, get_lang('Results'), false);
			$this->set_header($column++, get_lang('Certificates'), false);
		}
    }


	/**
	 * Function used by SortableTable to get total number of items in the table
	 */
	function get_total_number_of_items()
	{
		return $this->datagen->get_total_items_count();
	}


	/** 
	 * Function used by SortableTable to generate the data to display
	 */
	function get_table_data($from = 1)
	{

		// determine sorting type
		$col_adjust = (api_is_allowed_to_create_course() ? 1 : 0);
		switch ($this->column)
		{
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
		if ($this->direction == 'DESC')
			$sorting |= GradebookDataGenerator :: GDG_SORT_DESC;
		else
			$sorting |= GradebookDataGenerator :: GDG_SORT_ASC;



		$data_array = $this->datagen->get_data($sorting, $from, $this->per_page);


		// generate the data to display
		$sortable_data = array();
		foreach ($data_array as $data)
		{
			$row = array ();

			$item = $data[0];

			//if the item is invisible, wrap it in a span with class invisible
			$invisibility_span_open = (api_is_allowed_to_create_course() && $item->is_visible() == '0') ? '<span class="invisible">' : '';
			$invisibility_span_close = (api_is_allowed_to_create_course() && $item->is_visible() == '0') ? '</span>' : '';
			
			if (api_is_allowed_to_create_course())
				$row[] = $this->build_id_column ($item);
				
			$row[] = $this->build_type_column ($item);
			$row[] = $invisibility_span_open . $this->build_name_link ($item) . $invisibility_span_close;
			$row[] = $invisibility_span_open . $data[2] . $invisibility_span_close;
			$row[] = $invisibility_span_open . $data[3] . $invisibility_span_close;
			$row[] = $invisibility_span_open . $data[4] . $invisibility_span_close;

			//admins get an edit column
			if (api_is_allowed_to_create_course())
			{
				$row[] = $this->build_edit_column ($item);
			}
			//students get the results and certificates columns
			else
			{
				$row[] = $data[5];
				$row[] = $data[6];
			}
			$sortable_data[] = $row;
		}
		
		return $sortable_data;
	}




	
// Other functions

	private function build_id_column ($item)
	{
		switch ($item->get_item_type())
		{
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

	private function build_type_column ($item)
	{
		return build_type_icon_tag($item->get_icon_name());
	}

	private function build_name_link ($item)
	{
		switch ($item->get_item_type())
		{
			// category
			case 'C' :
				return '&nbsp;<a href="gradebook.php?selectcat=' . $item->get_id() . '">'
				 		. $item->get_name()
				 		. '</a>'
				 		. ($item->is_course() ? ' &nbsp;[' . $item->get_course_code() . ']' : '');
			// evaluation
			case 'E' :

				// course/platform admin can go to the view_results page
				if (api_is_allowed_to_create_course())
					return '&nbsp;'
						. '<a href="gradebook_view_result.php?selecteval=' . $item->get_id() . '">'
						. $item->get_name()
						. '</a>';
				// students can go to the statistics page (if custom display enabled)
				elseif (ScoreDisplay :: instance()->is_custom())
					return '&nbsp;'
						. '<a href="gradebook_statistics.php?selecteval=' . $item->get_id() . '">'
						. $item->get_name()
						. '</a>';
				else
					return $item->get_name();
				
			// link
			case 'L' :
				$url = $item->get_link();
				if (isset($url))
					$text = '&nbsp;<a href="' . $item->get_link() . '">'
							. $item->get_name()
							. '</a>';
				else
					$text = $item->get_name();
				$text .= '&nbsp;[' . $item->get_type_name() . ']';
				$cc = $this->currentcat->get_course_code();
				if(empty($cc))
				{
					$text .= '&nbsp;[<a href="'.api_get_path(REL_COURSE_PATH).$item->get_course_code().'/">'.$item->get_course_code().'</a>]';
				}
				return $text;
		}
	}


	private function build_edit_column ($item)
	{
		switch ($item->get_item_type())
		{
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
?>