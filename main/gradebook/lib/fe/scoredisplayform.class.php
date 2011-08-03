<?php
/* For licensing terms, see /license.txt */
/**
 * Script
 * @package chamilo.gradebook
 */
/**
 * Init
 */
require_once dirname(__FILE__).'/../../../inc/global.inc.php';
require_once dirname(__FILE__).'/../gradebook_functions.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'groupmanager.lib.php';
require_once api_get_path(LIBRARY_PATH) . 'formvalidator/FormValidator.class.php';
/**
 * Form for the score display dialog
 * @author Stijn Konings
 * @author Bert Steppé
 * @package chamilo.gradebook
 */
class ScoreDisplayForm extends FormValidator
{
	function ScoreDisplayForm($form_name, $action= null) {
		parent :: __construct($form_name, 'post', $action);
		$displayscore= ScoreDisplay :: instance();
		$customdisplays= $displayscore->get_custom_score_display_settings();
		$nr_items =(count($customdisplays)!='0')?count($customdisplays):'1';
		$this->setDefaults(array (
		'enablescorecolor' => $displayscore->is_coloring_enabled(),
		'scorecolpercent' => $displayscore->get_color_split_value(),
		'enablescore' => $displayscore->is_custom(),
		'includeupperlimit' => $displayscore->is_upperlimit_included()
		));
		$this->addElement('hidden', 'maxvalue', '100');
		$this->addElement('hidden', 'minvalue', '0');
		$counter= 1;

		//setting the default values

		if(is_array($customdisplays)) {
			foreach ($customdisplays as $customdisplay) {
				$this->setDefaults(array (
					'endscore[' . $counter . ']' => $customdisplay['score'],
					'displaytext[' . $counter . ']' => $customdisplay['display']
				));
				$counter++;
			}
		}
		$scorecol= array ();

		//settings for the colored score
		$this->addElement('header', '', get_lang('ScoreEdit'));
		$this->addElement('html', '<b>' . get_lang('ScoreColor') . '</b>');
		$renderer = $this->defaultRenderer();
		$elementTemplateColor = '<div class="row">
			<div class="label">
			<!-- BEGIN required --><span class="form_required">*</span> <!-- END required -->{label}
			</div>
			<div class="formw">
			<!-- BEGIN error --><span class="form_error">{error}</span><br /><!-- END error -->	'.get_lang('Below').'&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp{element} % '.get_lang('WillColorRed').'
			</div>
			</div>';

		$this->AddElement('checkbox', 'enablescorecolor', null, get_lang('EnableScoreColor'), null);
		$this->AddElement('text', 'scorecolpercent', null, array (
			'size' => 5,
			'maxlength' => 5
		));
		$renderer->setElementTemplate($elementTemplateColor,'scorecolpercent');
		$this->addRule('scorecolpercent', get_lang('OnlyNumbers'), 'numeric');
		$this->addRule(array('scorecolpercent','maxvalue'), get_lang('Over100'), 'compare', '<=');
		$this->addRule(array('scorecolpercent','minvalue'), get_lang('UnderMin'), 'compare', '>');

		//settings for the scoring system

		$this->addElement('html', '<br /><b>' . get_lang('ScoringSystem') . '</b>');
		$this->addElement('checkbox', 'enablescore', null, get_lang('EnableScoringSystem'), null);

		if ($displayscore->is_custom()) {
			$this->addElement('checkbox', 'includeupperlimit', null, get_lang('IncludeUpperLimit'), null);
			$this->addElement('static', null, null, get_lang('ScoreInfo'));
			$scorenull[]= & $this->CreateElement('static', null, null, get_lang('Between'));
			$this->setDefaults(array (
				'beginscore' => '0'
			));
			$scorenull[]= & $this->CreateElement('text', 'beginscore', null, array (
				'size' => 5,
				'maxlength' => 5,
				'disabled' => 'disabled'
			));
			$scorenull[]= & $this->CreateElement('static', null, null, ' %');
			$this->addGroup($scorenull, '', '', ' ');
			for ($counter= 1; $counter <= 20; $counter++) {

				$renderer =& $this->defaultRenderer();
				$elementTemplateTwoLabel =
				'<div id=' . $counter . ' style="display: '.(($counter<=$nr_items)?'inline':'none').';" class="row">
				<p><!-- BEGIN required --><span class="form_required">*</span> <!-- END required -->{label}
				<div class="formw"><!-- BEGIN error --><span class="form_error">{error}</span><br /><!-- END error -->	<br /><b>'.get_lang('And').'</b>&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp{element} % =';

				$elementTemplateTwoLabel2 =
				'<!-- BEGIN error --><span class="form_error">{error}</span><br /><!-- END error -->&nbsp{element}
				<a href="javascript:minItem(' . ($counter) . ')"><img style="display: '.(($counter>=$nr_items && $counter!=1)?'inline':'none').';" id="min-' . $counter . '" src="../img/delete.png" alt="'.get_lang('Delete').'" title="'.get_lang('Delete').'"></img></a>
				<a href="javascript:plusItem(' . ($counter+1) . ')"><img style="display: '.(($counter>=$nr_items)?'inline':'none').';" id="plus-' . ($counter+1) . '" src="../img/icons/16/add.png" alt="'.get_lang('Add').'" title="'.get_lang('Add').'"></img></a>
				</div></p></div>';

				$scorebetw= array ();
				$this->AddElement('text', 'endscore[' . $counter . ']', null, array (
					'size' => 5,
					'maxlength' => 5,
					'id' => 'txta-'.$counter
				));
				$this->AddElement('text', 'displaytext[' . $counter . ']', null,array (
					'size' => 40,
					'maxlength' => 40,
					'id' => 'txtb-'.$counter
				));
				$renderer->setElementTemplate($elementTemplateTwoLabel,'endscore[' . $counter . ']');
				$renderer->setElementTemplate($elementTemplateTwoLabel2,'displaytext[' . $counter . ']');
				$this->addRule('endscore[' . $counter . ']', get_lang('OnlyNumbers'), 'numeric');
				$this->addRule(array ('endscore[' . $counter . ']', 'maxvalue'), get_lang('Over100'), 'compare', '<=');
				$this->addRule(array ('endscore[' . $counter . ']', 'minvalue'), get_lang('UnderMin'), 'compare', '>');
			}
		}
		$this->setDefaults(array (
		'enablescore' => $displayscore->is_custom(), 'includeupperlimit' => $displayscore->is_upperlimit_included()));
		$this->addElement('style_submit_button', 'submit', get_lang('Ok'),'class="save"');
	}
	function validate() {
		return parent :: validate();
	}
}
