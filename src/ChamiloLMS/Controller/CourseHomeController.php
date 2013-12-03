<?php
/* For licensing terms, see /license.txt */
namespace ChamiloLMS\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CourseHomeController
 * @package ChamiloLMS\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class CourseHomeController
{
    public $language_files = array('course_home','courses');

    public function indexAction(Application $app, $cidReq, $idSession = null)
    {
        $extraJS = array();
        //@todo improve this JS includes should be added using twig
        $extraJS[] ='<script>
            $(document).ready(function() {
                $(".make_visible_and_invisible").attr("href", "javascript:void(0);");
                $(".make_visible_and_invisible > img").click(function () {

                    make_visible = "visible.gif";
                    make_invisible = "invisible.gif";
                    path_name = $(this).attr("src");
                    list_path_name = path_name.split("/");
                    image_link = list_path_name[list_path_name.length - 1];
                    tool_id = $(this).attr("id");
                    tool_info = tool_id.split("_");
                    my_tool_id = tool_info[1];

                    $.ajax({
                        contentType: "application/x-www-form-urlencoded",
                        beforeSend: function(objeto) {
                            $(".normal-message").show();
                            $("#id_confirmation_message").hide();
                        },
                        type: "GET",
                        url: "'.api_get_path(WEB_AJAX_PATH).'course_home.ajax.php?'.api_get_cidreq().'&a=set_visibility",
                        data: "id=" + my_tool_id + "&sent_http_request=1",
                        success: function(data) {
                            eval("var info=" + data);
                            new_current_tool_image = info.image;
                            new_current_view = "'.api_get_path(WEB_IMG_PATH).'" + info.view;
                            //eyes
                            $("#" + tool_id).attr("src", new_current_view);
                            //tool
                            $("#toolimage_" + my_tool_id).attr("src", new_current_tool_image);
                            //clase
                            $("#tooldesc_" + my_tool_id).attr("class", info.tclass);
                            $("#istooldesc_" + my_tool_id).attr("class", info.tclass);

                            if (image_link == "visible.gif") {
                                $("#" + tool_id).attr("alt", "'.get_lang('Activate', '').'");
                                $("#" + tool_id).attr("title", "'.get_lang('Activate', '').'");
                            } else {
                                $("#" + tool_id).attr("alt", "'.get_lang('Deactivate', '').'");
                                $("#" + tool_id).attr("title", "'.get_lang('Deactivate', '').'");
                            }
                            if (info.message == "is_active") {
                                message = "'.get_lang('ToolIsNowVisible', '').'";
                            } else {
                                message = "'.get_lang('ToolIsNowHidden', '').'";
                            }
                            $(".normal-message").hide();
                            $("#id_confirmation_message").html(message);
                            $("#id_confirmation_message").show();
                        }
                    });
                });
            });

            /* toogle for post-it in course home */
            $(function() {
                $(".thematic-postit-head").click(function() {
                    $(".thematic-postit-center").slideToggle("fast");
                });
            });

            </script>';

        $app['extraJS'] = $extraJS;

        //Needed because of this script:
        $course_code = $cidReq;
        $result = require_once api_get_path(SYS_CODE_PATH).'course_home/course_home.php';
        $app['template']->assign('content', $result['content']);
        $app['template']->assign('message', $result['message']);

        $response = $app['template']->render_layout('layout_1_col.tpl');
        return new Response($response, 200, array());
    }

    /**
     * @param Application $app
     * @param $courseCode
     * @param $fileName
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function getFileAction(Application $app, $courseCode, $fileName)
    {
        $courseInfo = api_get_course_info($courseCode);
        $sessionId = $app['request']->get('id_session');

        $docId = \DocumentManager::get_document_id($courseInfo, "/".$fileName);

        $filePath = null;

        if ($docId) {
            $isVisible = \DocumentManager::is_visible_by_id($docId, $courseInfo, $sessionId, api_get_user_id());
            $documentData = \DocumentManager::get_document_data_by_id($docId, $courseCode);
            $filePath = $documentData['absolute_path'];
            event_download($filePath);
        }

        if (!api_is_allowed_to_edit() && !$isVisible) {
            $app->abort(500);
        }
        //DocumentManager::file_send_for_download($full_file_name);
        return $app->sendFile($filePath);
    }
}
