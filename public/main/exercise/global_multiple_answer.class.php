<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Class GlobalMultipleAnswer.
 */
class GlobalMultipleAnswer extends Question
{
    public $typePicture = 'mcmagl.png';
    public $explanationLangVar = 'Global multiple answer';

    public function __construct()
    {
        parent::__construct();
        $this->type = GLOBAL_MULTIPLE_ANSWER;
        $this->isContent = $this->getIsContent();
    }

    public function createAnswersForm($form)
    {
        $nb_answers = isset($_POST['nb_answers']) ? $_POST['nb_answers'] : 4;
        $nb_answers += isset($_POST['lessAnswers']) ? -1 : (isset($_POST['moreAnswers']) ? 1 : 0);

        $obj_ex = Session::read('objExercise');

        $form->addHeader(get_lang('Answers'));
        /* Mise en variable de Affichage "Reponses" et son icone, "N�", "Vrai", "Reponse" */
        $html = '<table class="table table-striped table-hover">
                <tr>
                    <th width="10px">'.get_lang('Number').'</th>
                    <th width="10px">'.get_lang('True').'</th>
                    <th width="50%">'.get_lang('Answer').'</th>
                    <th width="50%">'.get_lang('Comment').'</th>
                </tr>
                ';
        $form->addHtml($html);

        $defaults = [];
        $correct = 0;
        $answer = false;
        if (!empty($this->id)) {
            $answer = new Answer($this->id);
            $answer->read();
            if ($answer->nbrAnswers > 0 && !$form->isSubmitted()) {
                $nb_answers = $answer->nbrAnswers;
            }
        }

        //  le nombre de r�ponses est bien enregistr� sous la forme int(nb)
        /* Ajout mise en forme nb reponse */
        $form->addElement('hidden', 'nb_answers');
        $boxes_names = [];

        if ($nb_answers < 1) {
            $nb_answers = 1;
            echo Display::return_message(get_lang('You have to create at least one answer'), 'normal');
        }

        //D�but affichage score global dans la modification d'une question
        $scoreA = 0; //par reponse
        $scoreG = 0; //Global

        /* boucle pour sauvegarder les donn�es dans le tableau defaults */
        for ($i = 1; $i <= $nb_answers; $i++) {
            /* si la reponse est de type objet */
            if (is_object($answer)) {
                $defaults['answer['.$i.']'] = $answer->answer[$i];
                $defaults['comment['.$i.']'] = $answer->comment[$i];
                $defaults['correct['.$i.']'] = $answer->correct[$i];

                // start
                $scoreA = $answer->weighting[$i];
            }
            if ($scoreA > 0) {
                $scoreG = $scoreG + $scoreA;
            }
            //------------- Fin
            //------------- Debut si un des scores par reponse est egal � 0 : la coche vaut 1 (coch�)
            if (0 == $scoreA) {
                $defaults['pts'] = 1;
            }

            $renderer = &$form->defaultRenderer();

            $renderer->setElementTemplate(
                '<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>',
                'correct['.$i.']'
            );
            $renderer->setElementTemplate(
                '<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>',
                'counter['.$i.']'
            );
            $renderer->setElementTemplate(
                '<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>',
                'answer['.$i.']'
            );
            $renderer->setElementTemplate(
                '<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>',
                'comment['.$i.']'
            );

            $answer_number = $form->addElement(
                'text',
                'counter['.$i.']',
                null,
                'value="'.$i.'"'
            );
            $answer_number->freeze();

            $form->addCheckBox('correct['.$i.']', null);
            $boxes_names[] = 'correct['.$i.']';

            $form->addHtmlEditor(
                'answer['.$i.']',
                null,
                true,
                false,
                [
                    'ToolbarSet' => 'TestProposedAnswer',
                    'Width' => '100%',
                    'Height' => '100',
                ]
            );
            $form->addRule('answer['.$i.']', get_lang('Required field'), 'required');
            $form->addHtmlEditor(
                'comment['.$i.']',
                null,
                true,
                false,
                [
                    'ToolbarSet' => 'TestProposedAnswer',
                    'Width' => '100%',
                    'Height' => '100',
                ]
            );

            $form->addElement('html', '</tr>');
        }
        //--------- Mise en variable du score global lors d'une modification de la question/r�ponse
        $defaults['weighting[1]'] = round($scoreG);
        $form->addElement('html', '</div></div></table>');
        $form->add_multiple_required_rule(
            $boxes_names,
            get_lang('Choose at least one good answer'),
            'multiple_required'
        );

        //only 1 answer the all deal ...
        $form->addElement('text', 'weighting[1]', get_lang('Score'));

        //--------- Creation coche pour ne pas prendre en compte les n�gatifs
        $form->addCheckBox('pts', '', get_lang('No negative score'));
        $form->addHtml('html', '<br />');

        // Affiche un message si le score n'est pas renseign�
        $form->addRule('weighting[1]', get_lang('Required field'), 'required');

        global $text;

        if ($obj_ex->edit_exercise_in_lp ||
            (empty($this->exerciseList) && empty($obj_ex->id))
        ) {
            // setting the save button here and not in the question class.php
            $form->addButtonDelete(get_lang('Remove answer option'), 'lessAnswers');
            $form->addButtonCreate(get_lang('Add answer option'), 'moreAnswers');
            $form->addButtonSave($text, 'submitQuestion');
        }

        $renderer->setElementTemplate('{element}&nbsp;', 'lessAnswers');
        $renderer->setElementTemplate('{element}&nbsp;', 'submitQuestion');
        $renderer->setElementTemplate('{element}', 'moreAnswers');

        $form->addElement('html', '</div></div>');

        $defaults['correct'] = $correct;

        if (!empty($this->id)) {
            $form->setDefaults($defaults);
        } else {
            if (1 == $this->isContent) {
                $form->setDefaults($defaults);
            }
        }
        $form->setConstants(['nb_answers' => $nb_answers]);
    }

    public function processAnswersCreation($form, $exercise)
    {
        $objAnswer = new Answer($this->id);
        $nb_answers = $form->getSubmitValue('nb_answers');

        // Score total
        $answer_score = trim($form->getSubmitValue('weighting[1]'));

        // Reponses correctes
        $nbr_corrects = 0;
        for ($i = 1; $i <= $nb_answers; $i++) {
            $goodAnswer = trim($form->getSubmitValue('correct['.$i.']'));
            if ($goodAnswer) {
                $nbr_corrects++;
            }
        }
        // Set question weighting (score total)
        $questionWeighting = $answer_score;

        // Set score per answer
        $nbr_corrects = 0 == $nbr_corrects ? 1 : $nbr_corrects;
        $answer_score = 0 == $nbr_corrects ? 0 : $answer_score;

        $answer_score /= $nbr_corrects;

        //$answer_score �quivaut � la valeur d'une bonne r�ponse
        // cr�ation variable pour r�cuperer la valeur de la coche pour la prise en compte des n�gatifs
        $test = $form->getSubmitValue('pts');

        for ($i = 1; $i <= $nb_answers; $i++) {
            $answer = trim($form->getSubmitValue('answer['.$i.']'));
            $comment = trim($form->getSubmitValue('comment['.$i.']'));
            $goodAnswer = trim($form->getSubmitValue('correct['.$i.']'));

            if ($goodAnswer) {
                $weighting = abs($answer_score);
            } else {
                if (1 == $test) {
                    $weighting = 0;
                } else {
                    $weighting = -abs($answer_score);
                }
            }

            $objAnswer->createAnswer($answer, $goodAnswer, $comment, $weighting, $i);
        }
        // saves the answers into the data base
        $objAnswer->save();

        // sets the total weighting of the question --> sert � donner le score total pendant l'examen
        $this->updateWeighting($questionWeighting);
        $this->save($exercise);
    }

    public function return_header(Exercise $exercise, $counter = null, $score = [])
    {
        $header = parent::return_header($exercise, $counter, $score);
        $header .= '<table class="'.$this->questionTableClass.'"><tr>';

        if (!in_array($exercise->results_disabled, [RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER])) {
            $header .= '<th>'.get_lang('Your choice').'</th>';
            if ($exercise->showExpectedChoiceColumn()) {
                $header .= '<th>'.get_lang('Expected choice').'</th>';
            }
        }

        $header .= '<th>'.get_lang('Answer').'</th>';
        if ($exercise->showExpectedChoice()) {
            $header .= '<thclass="text-center">'.get_lang('Status').'</th>';
        }
        $header .= '<th>'.get_lang('Comment').'</th>';
        $header .= '</tr>';

        return $header;
    }
}
