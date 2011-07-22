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
*/

// ALLOWED_TO_INCLUDE is defined in admin.php
if(!defined('ALLOWED_TO_INCLUDE')) {
	exit();
}
/*
// moves a question up in the list
if(isset($_GET['moveUp'])) {
	$check = Security::get_token('get');
	if ($check) {
		$objExercise->moveUp(intval($_GET['moveUp']));
		$objExercise->save();
	}
	Security::clear_token();
}

// moves a question down in the list
if(isset($_GET['moveDown'])) {
	$check = Security::get_token('get');
	if ($check) {
		$objExercise->moveDown(intval($_GET['moveDown']));
		$objExercise->save();
	}
	Security::clear_token();
}
*/
// deletes a question from the exercise (not from the data base)
if($deleteQuestion) {

	// if the question exists
	if($objQuestionTmp = Question::read($deleteQuestion)) {
		$objQuestionTmp->delete($exerciseId);

		// if the question has been removed from the exercise
		if($objExercise->removeFromList($deleteQuestion)) {
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
            buttons: {
                "<?php echo get_lang("Ok"); ?>": function() {                    
                    location.href = targetUrl;                
                    $( this ).dialog( "close" );
                    
                },
                "<?php echo get_lang("Cancel"); ?>": function() {
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
Question :: display_type_menu ($objExercise->feedbacktype);
echo '</div><div style="clear:both;">';
echo '<div id="message"></div>';
$token = Security::get_token();
//deletes a session when using don't know question type (ugly fix)
unset($_SESSION['less_answer']); 

if ($nbrQuestions) {
    $my_exercise = new Exercise();
    //forces the query to the database
    $my_exercise->read($_GET['exerciseId']);
	$questionList=$my_exercise->selectQuestionList();    
        
	if (is_array($questionList)) {      
	      
        echo '<div id="question_list">';        		
		foreach($questionList as $id) {
			//To avoid warning messages
			if (!is_numeric($id)) {
				continue;
			}	
			$objQuestionTmp = Question :: read($id);
            $question_class = get_class($objQuestionTmp);
            //$label          = $question_class->$explanationLangVar;
            
            
            $edit_link = '<a href="'.api_get_self().'?'.api_get_cidreq().'&type='.$objQuestionTmp->selectType().'&myid=1&editQuestion='.$id.'">'.Display::return_icon('edit.gif',get_lang('Modify')).'</a>';          
            // this variable  $show_quiz_edition comes from admin.php blocks the exercise/quiz modifications
            if ($show_quiz_edition) {
                 $delete_link = '<a id="delete_'.$id.'" class="opener"  href="'.api_get_self().'?'.api_get_cidreq().'&exerciseId='.$exerciseId.'&deleteQuestion='.$id.'" >'.Display::return_icon('delete.gif',get_lang('Delete')).'</a>';
                 //$delete_link = '<a id="delete_'.$id.'" class="opener" href="#">'.Display::return_icon('delete.gif',get_lang('Delete')).'</a>';                 
            }            
            $edit_link   = Display::tag('div',$edit_link,   array('style'=>'float:left;padding:0px; margin:0px'));
            $delete_link = Display::tag('div',$delete_link, array('style'=>'float:left;padding:0px; margin:0px'));
            $actions     = Display::tag('div',$edit_link.$delete_link, array('class'=>'edition','style'=>'width:70px; right:10px;     margin-top: 0px;     position: absolute;     top: 10%;'));

            echo '<div id="question_id_list_'.$id.'" >';            
                echo '<div class="header_operations">';               
                    $move = Display::return_icon('move.png',get_lang('Move'), array('class'=>'moved', 'style'=>'margin-bottom:-0.5em;'));
                    $level = '';
                    if (!empty($objQuestionTmp->level)) {
                    	$level = '('.get_lang('Difficulty').' '.$objQuestionTmp->level.')';
                    }            
        		    echo Display::tag('span','<a href="#">'.$move.' '.$objQuestionTmp->selectTitle().' '. Display::tag('span',$level.' ['.get_lang('QualificationNumeric').': '.$objQuestionTmp->selectWeighting().']', array('style'=>"right:90px; position: absolute;padding-top: 0.3em;")).'</a>', array('style'=>''));
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
        echo '</div>';
	}
}
echo '</table></div>';
if(!$nbrQuestions) {	
  	echo Display::display_warning_message(get_lang('NoQuestion'));
}