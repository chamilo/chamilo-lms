<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Class UniqueAnswer
 *
 * This class allows to instantiate an object of type UNIQUE_ANSWER
 * (MULTIPLE CHOICE, UNIQUE ANSWER),
 * extending the class question
 *
 * @author Eric Marguin
 * @author Julio Montoya
 * @package chamilo.exercise
 **/
class ReadingSpeed extends UniqueAnswer
{
    public static $typePicture = 'reading-speed.png';
    public static $explanationLangVar = 'ReadingSpeed';

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = READING_SPEED;
        $this->isContent = $this->getIsContent();
    }
}
