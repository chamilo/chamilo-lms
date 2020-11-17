<?php

/* For licensing terms, see /license.txt */

/**
 * Interface ImsAnswerInterface.
 */
interface ImsAnswerInterface
{
    /**
     * @param string $questionIdent
     * @param string $questionStatment
     * @param string $questionDesc
     * @param string $questionMedia
     *
     * @return string
     */
    public function imsExportResponses($questionIdent, $questionStatment, $questionDesc = '', $questionMedia = '');

    /**
     * @param $questionIdent
     *
     * @return mixed
     */
    public function imsExportResponsesDeclaration($questionIdent, Question $question = null);
}
