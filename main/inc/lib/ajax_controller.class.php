<?php
/* For licensing terms, see /license.txt */
/**
 * Definition of the AddManySessionToCategoryFunctions class
 * @package chamilo.library
 */
/**
 * Ajax controller. Dispatch request and perform required action.
 * 
 * 
 * Usage:
 * 
 *      $controller = AjaxController::instance();
 *      $controller->run();
 * 
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Genevas
 * @license /license.txt
 */
class AjaxController extends \Controller
{
    /**
     * Returns an HTML error message for forbidden access
     * @return bool|void
     * @assert () === null
     */
    function forbidden()
    {
        $this->response(false, get_lang('YouAreNotAuthorized'));
    }

    /**
     * Returns an HTML error message for unknown action
     * @return bool|void
     * @assert () === null
     */
    public function unknown()
    {
        $this->response(false, get_lang('UnknownAction'));
    }

    /**
     * Action exists but implementation is missing.
     * @return bool|void
     * @assert () === null
     */
    public function missing()
    {
        $this->response(false, get_lang('NoImplementation'));
    }

    /**
     * Display a standard json responce.
     * 
     * @param bool $success
     * @param string $message 
     * @param object $data
     * @return bool|void
     * @assert () === null
     */
    public function response($success = false, $message = '', $data = null)
    {
        $message = trim($message);
        $response = (object) array();
        $response->success = $success;
        if ($message) {
            $response->message = Display::return_message($message, $success ? 'normal' : 'error');
        } else {
            $response->message = '';
        }
        $response->data = $data;
        $this->render_json($response);
    }

}
