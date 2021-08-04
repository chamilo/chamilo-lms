<?php
/* For licensing terms, see /license.txt */

/**
 * Class ScoreDisplayForm
 * Form for the score display dialog.
 *
 * @author Stijn Konings
 * @author Bert Steppé
 */
class ScoreDisplayForm extends FormValidator
{
    /**
     * @param $form_name
     * @param null $action
     */
    public function __construct($form_name, $action = null)
    {
        parent::__construct($form_name, 'post', $action);
        $displayscore = ScoreDisplay::instance();
        $customdisplays = $displayscore->get_custom_score_display_settings();

        $nr_items = ('0' != count($customdisplays)) ? count($customdisplays) : '1';
        $this->setDefaults(
            [
                'scorecolpercent' => $displayscore->get_color_split_value(),
            ]
        );

        $this->addElement('hidden', 'maxvalue', '100');
        $this->addElement('hidden', 'minvalue', '0');
        $counter = 1;

        // Setting the default values
        if (is_array($customdisplays)) {
            foreach ($customdisplays as $customdisplay) {
                $this->setDefaults(
                    [
                        'endscore['.$counter.']' => $customdisplay['score'],
                        'displaytext['.$counter.']' => $customdisplay['display'],
                    ]
                );
                $counter++;
            }
        }
        // Settings for the colored score
        $this->addElement('header', get_lang('ScoreEdit'));

        if ($displayscore->is_coloring_enabled()) {
            $this->addElement('html', '<b>'.get_lang('ScoreColor').'</b>');
            $this->addElement(
                'text',
                'scorecolpercent',
                [get_lang('Below'), get_lang('WillColorRed'), '%'],
                [
                    'size' => 5,
                    'maxlength' => 5,
                    'input-size' => 2,
                ]
            );

            if (api_get_setting('teachers_can_change_score_settings') != 'true') {
                $this->freeze('scorecolpercent');
            }

            $this->addRule('scorecolpercent', get_lang('OnlyNumbers'), 'numeric');
            $this->addRule(['scorecolpercent', 'maxvalue'], get_lang('Over100'), 'compare', '<=');
            $this->addRule(['scorecolpercent', 'minvalue'], get_lang('UnderMin'), 'compare', '>');
        }

        // Settings for the scoring system
        if ($displayscore->is_custom()) {
            $this->addElement('html', '<br /><b>'.get_lang('ScoringSystem').'</b>');
            $this->addElement('static', null, null, get_lang('ScoreInfo'));
            $this->setDefaults([
                'beginscore' => '0',
            ]);
            $this->addElement('text', 'beginscore', [get_lang('Between'), null, '%'], [
                'size' => 5,
                'maxlength' => 5,
                'disabled' => 'disabled',
                'input-size' => 2,
            ]);

            for ($counter = 1; $counter <= 20; $counter++) {
                $renderer = &$this->defaultRenderer();
                $elementTemplateTwoLabel =
                '<div id='.$counter.' style="display: '.(($counter <= $nr_items) ? 'inline' : 'none').';">    
                <!-- BEGIN required --><span class="form_required">*</span> <!-- END required -->    
                <label class="control-label">{label}</label>
                <div class="form-group">
                <label class="col-sm-2 control-label">
                </label>    
                <div class="col-sm-1">
                <!-- BEGIN error --><span class="form_error">{error}</span><br />
                <!-- END error -->&nbsp<b>'.get_lang('And').'</b>
                </div>
                    
                <div class="col-sm-2">
                {element}
                </div>    
                <div class="col-sm-1">
                =
                </div>';

                $elementTemplateTwoLabel2 = '
                <div class="col-sm-2">
                    <!-- BEGIN error --><span class="form_error">{error}</span>
                    <!-- END error -->
                    {element}
                </div>
                <div class="col-sm-1">
                    <a href="javascript:plusItem('.($counter + 1).')">
                    <img style="display: '.(($counter >= $nr_items) ? 'inline' : 'none').';" id="plus-'.($counter + 1).'" src="'.Display::returnIconPath('add.png').'" alt="'.get_lang('Add').'" title="'.get_lang('Add').'"></a>
                    <a href="javascript:minItem('.($counter).')">
                    <img style="display: '.(($counter >= $nr_items && $counter != 1) ? 'inline' : 'none').';" id="min-'.$counter.'" src="'.Display::returnIconPath('delete.png').'" alt="'.get_lang('Delete').'" title="'.get_lang('Delete').'"></a>
                </div>
                </div>
                </div>';

                $this->addElement(
                    'text',
                    'endscore['.$counter.']',
                    null,
                    [
                        'size' => 5,
                        'maxlength' => 5,
                        'id' => 'txta-'.$counter,
                        'input-size' => 2,
                    ]
                );

                $this->addElement(
                    'text',
                    'displaytext['.$counter.']',
                    null,
                    [
                        'size' => 40,
                        'maxlength' => 40,
                        'id' => 'txtb-'.$counter,
                    ]
                );
                $renderer->setElementTemplate($elementTemplateTwoLabel, 'endscore['.$counter.']');
                $renderer->setElementTemplate($elementTemplateTwoLabel2, 'displaytext['.$counter.']');
                $this->addRule('endscore['.$counter.']', get_lang('OnlyNumbers'), 'numeric');
                $this->addRule(['endscore['.$counter.']', 'maxvalue'], get_lang('Over100'), 'compare', '<=');
                $this->addRule(['endscore['.$counter.']', 'minvalue'], get_lang('UnderMin'), 'compare', '>');
            }
        }

        if ($displayscore->is_custom()) {
            $this->addButtonSave(get_lang('Ok'));
        }
    }

    public function validate()
    {
        return parent::validate();
    }
}
