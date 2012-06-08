<?php
/* For licensing terms, see /license.txt */
/**
*	Code library for HotPotatoes integration.
*	@package chamilo.exercise
* 	@author
*/

/**
*	QUESTION LIST ADMINISTRATION
*
*	This script allows to manage the question list
*	It is included from the script admin.php
*
*	@author Olivier Brouckaert
* Modified by Hubert Borderiou 21-10-2011 (Question by category)
*/

// ALLOWED_TO_INCLUDE is defined in admin.php
if (!defined('ALLOWED_TO_INCLUDE')) {
	exit();
}
// deletes a question from the exercise (not from the data base)
if ($deleteQuestion) {
	// if the question exists
	if ($objQuestionTmp = Question::read($deleteQuestion)) {
		$objQuestionTmp->delete($exerciseId);

		// if the question has been removed from the exercise
		if ($objExercise->removeFromList($deleteQuestion)) {
			$nbrQuestions--;
		}
	}
	// destruction of the Question object
	unset($objQuestionTmp);
}
?>
<style>
    .ui-state-highlight { height: 30px; line-height: 1.2em; }
    /*Fixes edition buttons*/
    .ui-accordion-icons .ui-accordion-header .edition a {
        padding-left:4px;
    }
</style>

<div id="dialog-confirm" title="<?php echo get_lang("ConfirmYourChoice"); ?>">
    <p>
        <span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;">
        </span>
        <?php echo get_lang("AreYouSureToDelete"); ?>
    </p>
</div>

<script>
$(function() {
    $( "#dialog:ui-dialog" ).dialog( "destroy" );
    $( "#dialog-confirm" ).dialog({
            autoOpen: false,
            show: "blind",
            resizable: false,
            height:150,
            modal: false
     });

    $(".opener").click(function() {
        var targetUrl = $(this).attr("href");
        $( "#dialog-confirm" ).dialog({
        	modal: true,
            buttons: {
                "<?php echo get_lang("Yes"); ?>": function() {
                    location.href = targetUrl;
                    $( this ).dialog( "close" );

                },
                "<?php echo get_lang("No"); ?>": function() {
                    $( this ).dialog( "close" );
                }
            }
        });
        $( "#dialog-confirm" ).dialog("open");
        return false;
    });

    var stop = false;
    $( "#question_list h3" ).click(function( event ) {
        if ( stop ) {
            event.stopImmediatePropagation();
            event.preventDefault();
            stop = false;
        }
    });

    var icons = {
            header: "ui-icon-circle-arrow-e",
            headerSelected: "ui-icon-circle-arrow-s"
    };


    /* We can add links in the accordion header */
    $("div > div > div > .edition > div > a").click(function() {
        //Avoid the redirecto when selecting the delete button
        if (this.id.indexOf('delete') == -1) {
            newWind = window.open(this.href,"_self");
            newWind.focus();
            return false;
        }
    });

    $( "#question_list" ).accordion({
        icons: icons,
        autoHeight: false,
        active: false, // all items closed by default
        collapsible: true,
        header: ".header_operations",
    })

    .sortable({
        cursor: "move", // works?
        update: function(event, ui) {
            var order = $(this).sortable("serialize") + "&a=update_question_order";
            $.post("<?php echo api_get_path(WEB_AJAX_PATH)?>exercise.ajax.php", order, function(reponse){
                $("#message").html(reponse);
            });
        },
        axis: "y",
        placeholder: "ui-state-highlight", //defines the yellow highlight
        handle: ".moved", //only the class "moved"
        stop: function() {
            stop = true;
        }
    });
});
</script>
<?php

echo '<div class="actionsbig">';
//we filter the type of questions we can add
Question :: display_type_menu ($objExercise->feedback_type);
echo '</div><div style="clear:both;"></div>';
echo '<div id="message"></div>';
$token = Security::get_token();
//deletes a session when using don't know question type (ugly fix)
unset($_SESSION['less_answer']);

// If we are in a test
$inATest = isset($exerciseId) && $exerciseId > 0;
if (!$inATest) {
	echo "<p class='warning-message'>".get_lang("ChoiceQuestionType")."</p>";
} else {
    // Title line
    echo "<div>";
    echo "<div style='font-weight:bold; width:50%; float:left; padding:10px 0px; text-align:center;'><span style='padding-left:50px;'>&nbsp;</span>".get_lang('Questions')."</div>";
    echo "<div style='font-weight:bold; width:4%; float:left; padding:10px 0px; text-align:center;'>".get_lang('Type')."</div>";
    echo "<div style='font-weight:bold; width:22%; float:left; padding:10px 0px; text-align:center;'>".get_lang('Category')."</div>";
    echo "<div style='font-weight:bold; width:6%; float:left; padding:10px 0px; text-align:center;'>".get_lang('Difficulty')."</div>";
    echo "<div style='font-weight:bold; width:4%; float:left; padding:10px 0px; text-align:center;'>".get_lang('Score')."</div>";
    echo "</div>";
    echo "<div style='clear:both'>&nbsp;</div>";

    echo '<div id="question_list">';
	if ($nbrQuestions) {
        $my_exercise = new Exercise();
        //forces the query to the database
        $my_exercise->read($_GET['exerciseId']);
        $questionList=$my_exercise->selectQuestionList();

        // Style for columns

        $styleQuestion = "width:50%; float:left;";
        $styleType = "width:4%; float:left; padding-top:4px; text-align:center;";
        $styleCat = "width:22%; float:left; padding-top:8px; text-align:center;";
        $styleLevel = "width:6%; float:left; padding-top:8px; text-align:center;";
        $styleScore = "width:4%; float:left; padding-top:8px; text-align:center;";

        if (is_array($questionList)) {
			foreach($questionList as $id) {
				//To avoid warning messages
				if (!is_numeric($id)) {
					continue;
				}
				$objQuestionTmp = Question :: read($id);
				$question_class = get_class($objQuestionTmp);

				$clone_link = '<a href="'.api_get_self().'?'.api_get_cidreq().'&clone_question='.$id.'">'.Display::return_icon('cd.gif',get_lang('Copy'), array(), ICON_SIZE_SMALL).'</a>';
				$edit_link  = '<a href="'.api_get_self().'?'.api_get_cidreq().'&type='.$objQuestionTmp->selectType().'&myid=1&editQuestion='.$id.'">'.Display::return_icon('edit.png',get_lang('Modify'), array(), ICON_SIZE_SMALL).'</a>';
				// this variable  $show_quiz_edition comes from admin.php blocks the exercise/quiz modifications
				if ($show_quiz_edition) {
				     $delete_link = '<a id="delete_'.$id.'" class="opener"  href="'.api_get_self().'?'.api_get_cidreq().'&exerciseId='.$exerciseId.'&deleteQuestion='.$id.'" >'.Display::return_icon('delete.png',get_lang('RemoveFromTest'), array(), ICON_SIZE_SMALL).'</a>';
				}
				$edit_link   = Display::tag('div',$edit_link,   array('style'=>'float:left; padding:0px; margin:0px'));
				$clone_link  = Display::tag('div',$clone_link,  array('style'=>'float:left; padding:0px; margin:0px'));
				$delete_link = Display::tag('div',$delete_link, array('style'=>'float:left; padding:0px; margin:0px'));
				$actions     = Display::tag('div',$edit_link.$clone_link.$delete_link, array('class'=>'edition','style'=>'width:100px; right:10px;     margin-top: 0px;     position: absolute;     top: 10%;'));

                $title = Security::remove_XSS($objQuestionTmp->selectTitle());
                $move = Display::return_icon('all_directions.png',get_lang('Move'), array('class'=>'moved', 'style'=>'margin-bottom:-0.5em;'));

                // Question name

				$questionName = Display::tag('div', '<a href="#" title = "'.$title.'">'.$move.' '.cut($title, 60).'</a>', array('style'=>$styleQuestion));

				// Question type
				list($typeImg, $typeExpl) = $objQuestionTmp->get_type_icon_html();
				$questionType = Display::tag('div', Display::return_icon($typeImg, $typeExpl, array(), 32), array('style'=>$styleType));

				// Question category
				$txtQuestionCat = Security::remove_XSS(Testcategory::getCategoryNameForQuestion($objQuestionTmp->id));
				if (empty($txtQuestionCat)) {
					$txtQuestionCat = "-";
				}
				$questionCategory = Display::tag('div', '<a href="#" style="padding:0px; margin:0px;" title="'.$txtQuestionCat.'">'.cut($txtQuestionCat, 55).'</a>', array('style'=>$styleCat));

				// Question level

				$txtQuestionLevel = $objQuestionTmp->level;
                if (empty($objQuestionTmp->level)) {
                    $txtQuestionLevel = '-';
                }
                $questionLevel = Display::tag('div', $txtQuestionLevel, array('style'=>$styleLevel));

                // Question score

                $questionScore = Display::tag('div', $objQuestionTmp->selectWeighting(), array('style'=>$styleScore));

                echo '<div id="question_id_list_'.$id.'" >';
                    echo '<div class="header_operations">';
                        echo $questionName;
                        echo $questionType;
                        echo $questionCategory;
                        echo $questionLevel;
                        echo $questionScore;
                        echo $actions;
                    echo '</div>';
                    echo '<div class="question-list-description-block">';
                        echo '<p>';
                        //echo get_lang($question_class.$label);
                        echo get_lang($question_class);
                        echo '<br />';
                        //echo get_lang('Level').': '.$objQuestionTmp->selectLevel();
                        echo '<br />';
                        showQuestion($id, false, '', '',false, true);
                        echo '</p>';
                    echo '</div>';
                echo '</div>';
                unset($objQuestionTmp);
			}
		}
	}
	if(!$nbrQuestions) {
	  	echo Display::display_warning_message(get_lang('NoQuestion'));
	}
	echo '</div>'; //question list div
}