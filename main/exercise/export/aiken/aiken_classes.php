<?php
/* For licensing terms, see /license.txt */
/**
 * @author Claro Team <cvs@claroline.net>
 * @author Yannick Warnier <yannick.warnier@beeznest.com> - updated ImsAnswerHotspot to match QTI norms
 * @author César Perales <cesar.perales@gmail.com> Updated function names and import files for Aiken format support
 * @package chamilo.exercise
 */

if ( count( get_included_files() ) == 1 ) die( '---' );

if (!function_exists('mime_content_type')) {

	/**
	 * @param string $filename
     * @return string
	 */
	function mime_content_type($filename) {
		return DocumentManager::file_get_mime_type((string)$filename);
	}
}

/**
 * Aiken2Question transformation class
 */
class Aiken2Question extends Question
{
    /**
     * Include the correct answer class and create answer
     */
    function setAnswer()
    {
        switch($this->type)
        {
            case MCUA :
                $answer = new AikenAnswerMultipleChoice($this->id);
            	return $answer;
            default :
                $answer = null;
                break;
        }
        return $answer;
    }
    function createAnswersForm($form)
    {
    	return true;
    }
    function processAnswersCreation($form)
    {
    	return true;
    }
}


/**
 * Class
 * @package chamilo.exercise
 */
class AikenAnswerMultipleChoice extends Answer
{
}
