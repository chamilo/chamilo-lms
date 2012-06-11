<?php

/**
 * Autoload Chamilo classes
 * 
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class Autoload
{

    static private $is_registered = false;

    /**
     * Register the Chamilo autoloader on the stack. 
     * Will only do it once so this method is repeatable.
     */
    static public function register()
    {
        if (self::is_registered()) {
            return false;
        }

        $f = array(new self, 'load');
        spl_autoload_register($f);
        self::$is_registered = true;
        return true;
    }

    static public function is_registered()
    {
        return self::$is_registered;
    }

    static public function &map()
    {
        static $result = false;

        if ($result) {
            return $result;
        }

        $root = dirname(__FILE__) . '/../../';
        /**
         * WARNING
         * 
         * This map is autogeneated by AutoloadClassFinder. It may be ovewrite
         * by future run.
         * 
         * If you need to manually add mappings do it at the end and clearly 
         * identifies that as manually added or better ensure your class is 
         * captured by the finder. 
         * 
         * If it comes from a different package you may want to add another autoload
         * function on the stack.
         */
        $result = array();
        $result['AbstractLink'] = '/main/gradebook/lib/be/abstractlink.class.php';
        $result['AccessToken'] = '/main/inc/lib/access_token.class.php';
        $result['Accessurleditcoursestourl'] = '/main/inc/lib/access_url_edit_courses_to_url_functions.lib.php';
        $result['Accessurleditsessionstourl'] = '/main/inc/lib/access_url_edit_sessions_to_url_functions.lib.php';
        $result['Accessurledituserstourl'] = '/main/inc/lib/access_url_edit_users_to_url_functions.lib.php';
        $result['AddCourseToSession'] = '/main/inc/lib/add_courses_to_session_functions.lib.php';
        $result['AddManySessionToCategoryFunctions'] = '/main/inc/lib/add_many_session_to_category_functions.lib.php';
        $result['Admin'] = '/main/auth/shibboleth/app/model/admin.class.php';
        $result['AdminPage'] = '/main/admin/admin_page.class.php';
        $result['AdminStore'] = '/main/auth/shibboleth/app/model/admin.class.php';
        $result['Agenda'] = '/main/calendar/agenda.lib.php';
        $result['Announcement'] = '/main/coursecopy/classes/Announcement.class.php';
        $result['AnnouncementEmail'] = '/main/announcements/announcement_email.class.php';
        $result['Answer'] = '/main/exercice/answer.class.php';
        $result['AppPlugin'] = '/main/inc/lib/plugin.lib.php';
        $result['AssetAggregatedRenderer'] = '/main/inc/lib/external_media/renderer/asset_aggregated_renderer.class.php';
        $result['AssetGoogleCalendarRenderer'] = '/main/inc/lib/external_media/renderer/lab/asset_google_calendar_renderer.class.php';
        $result['AssetGoogleDocumentRenderer'] = '/main/inc/lib/external_media/renderer/protocol/asset_google_document_renderer.class.php';
        $result['AssetGoogleDocumentViewerRenderer'] = '/main/inc/lib/external_media/renderer/protocol/asset_google_document_viewer_renderer.class.php';
        $result['AssetGoogleMapRenderer'] = '/main/inc/lib/external_media/renderer/protocol/asset_google_map_renderer.class.php';
        $result['AssetGoogleWidgetRenderer'] = '/main/inc/lib/external_media/renderer/protocol/asset_google_widget_renderer.class.php';
        $result['AssetImageRenderer'] = '/main/inc/lib/external_media/renderer/protocol/asset_image_renderer.class.php';
        $result['AssetMaharaGroupRenderer'] = '/main/inc/lib/external_media/renderer/lab/asset_mahara_group_renderer.class.php';
        $result['AssetMaharaPersonRenderer'] = '/main/inc/lib/external_media/renderer/lab/asset_mahara_person_renderer.class.php';
        $result['AssetMediaRenderer'] = '/main/inc/lib/external_media/renderer/protocol/asset_media_renderer.class.php';
        $result['AssetMediaserverRenderer'] = '/main/inc/lib/external_media/renderer/protocol/asset_mediaserver_renderer.class.php';
        $result['AssetOembedRenderer'] = '/main/inc/lib/external_media/renderer/protocol/asset_oembed_renderer.class.php';
        $result['AssetOgRenderer'] = '/main/inc/lib/external_media/renderer/protocol/asset_og_renderer.class.php';
        $result['AssetPageRenderer'] = '/main/inc/lib/external_media/renderer/protocol/asset_page_renderer.class.php';
        $result['AssetRenderer'] = '/main/inc/lib/external_media/renderer/asset_renderer.class.php';
        $result['AssetRssRenderer'] = '/main/inc/lib/external_media/renderer/protocol/asset_rss_renderer.class.php';
        $result['AssetScratchRenderer'] = '/main/inc/lib/external_media/renderer/protocol/asset_scratch_renderer.class.php';
        $result['AssetWikiRenderer'] = '/main/inc/lib/external_media/renderer/lab/asset_wiki_renderer.class.php';
        $result['AttendanceLink'] = '/main/gradebook/lib/be/attendancelink.class.php';
        $result['Auth'] = '/main/inc/lib/auth.lib.php';
        $result['Block'] = '/main/dashboard/block.class.php';
        $result['Blog'] = '/main/inc/lib/blog.lib.php';
        $result['Cache'] = '/main/inc/lib/cache.class.php';
        $result['Career'] = '/main/inc/lib/career.lib.php';
        $result['CatForm'] = '/main/gradebook/lib/fe/catform.class.php';
        $result['Category'] = '/main/gradebook/lib/be/category.class.php';
        $result['Certificate'] = '/main/inc/lib/certificate.lib.php';
        $result['Chamilo'] = '/main/inc/lib/chamilo.class.php';
        $result['ChamiloSession'] = '/main/inc/lib/chamilo_session.class.php';
        $result['Chat'] = '/main/inc/lib/chat.lib.php';
        $result['ClassManager'] = '/main/inc/lib/classmanager.lib.php';
        $result['ClosureCompiler'] = '/main/inc/lib/closure_compiler.class.php';
        $result['CodeUtilities'] = '/main/inc/lib/code_utilities.class.php';
        $result['ConditionalLogin'] = '/main/inc/lib/conditional_login.class.php';
        $result['Converter'] = '/main/inc/lib/system/text/converter.class.php';
        $result['Course'] = '/main/coursecopy/classes/Course.class.php';
        $result['CourseArchiver'] = '/main/coursecopy/classes/CourseArchiver.class.php';
        $result['CourseBuilder'] = '/main/coursecopy/classes/CourseBuilder.class.php';
        $result['CourseCopyLearnpath'] = '/main/coursecopy/classes/CourseCopyLearnpath.class.php';
        $result['CourseHome'] = '/main/inc/lib/course_home.lib.php';
        $result['CourseManager'] = '/main/inc/lib/course.lib.php';
        $result['CourseNoticeController'] = '/main/course_notice/course_notice_controller.class.php';
        $result['CourseNoticeQuery'] = '/main/course_notice/course_notice_query.class.php';
        $result['CourseNoticeRss'] = '/main/course_notice/course_notice_rss.class.php';
        $result['CourseRecycler'] = '/main/coursecopy/classes/CourseRecycler.class.php';
        $result['CourseRequestManager'] = '/main/inc/lib/course_request.lib.php';
        $result['CourseRestorer'] = '/main/coursecopy/classes/CourseRestorer.class.php';
        $result['CourseSelectForm'] = '/main/coursecopy/classes/CourseSelectForm.class.php';
        $result['CourseSession'] = '/main/coursecopy/classes/CourseSession.class.php';
        $result['CsvReader'] = '/main/inc/lib/system/io/csv_reader.class.php';
        $result['CsvWriter'] = '/main/inc/lib/system/io/csv_writer.class.php';
        $result['Curl'] = '/main/inc/lib/system/net/curl.class.php';
        $result['CurrentCourse'] = '/main/inc/lib/current_course.class.php';
        $result['CurrentUser'] = '/main/inc/lib/current_user.class.php';
        $result['CustomPages'] = '/main/inc/lib/custom_pages.class.php';
        $result['DashboardManager'] = '/main/inc/lib/dashboard.lib.php';
        $result['DataForm'] = '/main/gradebook/lib/fe/dataform.class.php';
        $result['Debug'] = '/main/inc/lib/debug.lib.php';
        $result['Diagnoser'] = '/main/inc/lib/diagnoser.lib.php';
        $result['Diagnosis'] = '/main/inc/lib/doctor/diagnosis.class.php';
        $result['Display'] = '/main/inc/lib/display.lib.php';
        $result['Document'] = '/main/coursecopy/classes/Document.class.php';
        $result['DocumentManager'] = '/main/inc/lib/document.lib.php';
        $result['DokeosIndexer'] = '/main/inc/lib/search/DokeosIndexer.class.php';
        $result['DropboxLink'] = '/main/gradebook/lib/be/dropboxlink.class.php';
        $result['DummyCourseCreator'] = '/main/coursecopy/classes/DummyCourseCreator.class.php';
        $result['Encoding'] = '/main/inc/lib/system/text/encoding.class.php';
        $result['EncodingConverter'] = '/main/inc/lib/system/text/encoding_converter.class.php';
        $result['EntityGenerator'] = '/main/inc/lib/tools/entity_generator.class.php';
        $result['EvalForm'] = '/main/gradebook/lib/fe/evalform.class.php';
        $result['EvalLink'] = '/main/gradebook/lib/be/evallink.class.php';
        $result['Evaluation'] = '/main/gradebook/lib/be/evaluation.class.php';
        $result['Event'] = '/main/coursecopy/classes/Event.class.php';
        $result['EventsMail'] = '/main/inc/lib/events_email.class.php';
        $result['EventsDispatcher'] = '/main/inc/lib/events_dispatcher.class.php';
        $result['Exercise'] = '/main/exercice/exercise.class.php';
        $result['ExerciseLink'] = '/main/gradebook/lib/be/exerciselink.class.php';
        $result['ExerciseResult'] = '/main/exercice/exercise_result.class.php';
        $result['ExerciseShowFunctions'] = '/main/inc/lib/exercise_show_functions.lib.php';
        $result['FileManager'] = '/main/inc/lib/fileManage.lib.php';
        $result['FileReader'] = '/main/inc/lib/system/io/file_reader.class.php';
        $result['FileWriter'] = '/main/inc/lib/system/io/file_writer.class.php';
        $result['FillBlanks'] = '/main/exercice/fill_blanks.class.php';
        $result['FlatViewDataGenerator'] = '/main/gradebook/lib/flatview_data_generator.class.php';
        $result['FlatViewTable'] = '/main/gradebook/lib/fe/flatviewtable.class.php';
        $result['FormValidator'] = '/main/inc/lib/formvalidator/FormValidator.class.php';
        $result['Forum'] = '/main/coursecopy/classes/Forum.class.php';
        $result['ForumCategory'] = '/main/coursecopy/classes/ForumCategory.class.php';
        $result['ForumPost'] = '/main/coursecopy/classes/ForumPost.class.php';
        $result['ForumThreadLink'] = '/main/gradebook/lib/be/forumthreadlink.class.php';
        $result['ForumTopic'] = '/main/coursecopy/classes/ForumTopic.class.php';
        $result['FreeAnswer'] = '/main/exercice/freeanswer.class.php';
        $result['GDWrapper'] = '/main/inc/lib/image.lib.php';
        $result['Glossary'] = '/main/coursecopy/classes/Glossary.class.php';
        $result['GlossaryManager'] = '/main/inc/lib/glossary.lib.php';
        $result['GradeBookResult'] = '/main/gradebook/gradebook_result.class.php';
        $result['GradeModel'] = '/main/inc/lib/grade_model.lib.php';
        $result['GradeModelComponents'] = '/main/inc/lib/grade_model.lib.php';
        $result['Gradebook'] = '/main/inc/lib/gradebook.lib.php';
        $result['GradebookDataGenerator'] = '/main/gradebook/lib/gradebook_data_generator.class.php';
        $result['GradebookItem'] = '/main/gradebook/lib/be/gradebookitem.class.php';
        $result['GradebookTable'] = '/main/gradebook/lib/fe/gradebooktable.class.php';
        $result['GroupManager'] = '/main/inc/lib/groupmanager.lib.php';
        $result['GroupPortalManager'] = '/main/inc/lib/group_portal_manager.lib.php';
        $result['HTML_Common'] = '/main/inc/lib/pear/HTML/Common.php';
        $result['HTML_QuickForm'] = '/main/inc/lib/pear/HTML/QuickForm.php';
        $result['HTML_QuickForm_Action'] = '/main/inc/lib/pear/HTML/QuickForm/Action.php';
        $result['HTML_QuickForm_Action_Back'] = '/main/inc/lib/pear/HTML/QuickForm/Action/Back.php';
        $result['HTML_QuickForm_Action_Direct'] = '/main/inc/lib/pear/HTML/QuickForm/Action/Direct.php';
        $result['HTML_QuickForm_Action_Display'] = '/main/inc/lib/pear/HTML/QuickForm/Action/Display.php';
        $result['HTML_QuickForm_Action_Jump'] = '/main/inc/lib/pear/HTML/QuickForm/Action/Jump.php';
        $result['HTML_QuickForm_Action_Next'] = '/main/inc/lib/pear/HTML/QuickForm/Action/Next.php';
        $result['HTML_QuickForm_Action_Submit'] = '/main/inc/lib/pear/HTML/QuickForm/Action/Submit.php';
        $result['HTML_QuickForm_Controller'] = '/main/inc/lib/pear/HTML/QuickForm/Controller.php';
        $result['HTML_QuickForm_Error'] = '/main/inc/lib/pear/HTML/QuickForm.php';
        $result['HTML_QuickForm_Page'] = '/main/inc/lib/pear/HTML/QuickForm/Page.php';
        $result['HTML_QuickForm_Renderer'] = '/main/inc/lib/pear/HTML/QuickForm/Renderer.php';
        $result['HTML_QuickForm_Renderer_Array'] = '/main/inc/lib/pear/HTML/QuickForm/Renderer/Array.php';
        $result['HTML_QuickForm_Renderer_ArraySmarty'] = '/main/inc/lib/pear/HTML/QuickForm/Renderer/ArraySmarty.php';
        $result['HTML_QuickForm_Renderer_Default'] = '/main/inc/lib/pear/HTML/QuickForm/Renderer/Default.php';
        $result['HTML_QuickForm_Renderer_ITDynamic'] = '/main/inc/lib/pear/HTML/QuickForm/Renderer/ITDynamic.php';
        $result['HTML_QuickForm_Renderer_ITStatic'] = '/main/inc/lib/pear/HTML/QuickForm/Renderer/ITStatic.php';
        $result['HTML_QuickForm_Renderer_Object'] = '/main/inc/lib/pear/HTML/QuickForm/Renderer/Object.php';
        $result['HTML_QuickForm_Renderer_ObjectFlexy'] = '/main/inc/lib/pear/HTML/QuickForm/Renderer/ObjectFlexy.php';
        $result['HTML_QuickForm_Renderer_QuickHtml'] = '/main/inc/lib/pear/HTML/QuickForm/Renderer/QuickHtml.php';
        $result['HTML_QuickForm_Rule'] = '/main/inc/lib/pear/HTML/QuickForm/Rule.php';
        $result['HTML_QuickForm_RuleRegistry'] = '/main/inc/lib/pear/HTML/QuickForm/RuleRegistry.php';
        $result['HTML_QuickForm_Rule_Callback'] = '/main/inc/lib/pear/HTML/QuickForm/Rule/Callback.php';
        $result['HTML_QuickForm_Rule_Compare'] = '/main/inc/lib/pear/HTML/QuickForm/Rule/Compare.php';
        $result['HTML_QuickForm_Rule_CompareDate'] = '/main/inc/lib/pear/HTML/QuickForm/Rule/CompareDate.php';
        $result['HTML_QuickForm_Rule_Email'] = '/main/inc/lib/pear/HTML/QuickForm/Rule/Email.php';
        $result['HTML_QuickForm_Rule_Range'] = '/main/inc/lib/pear/HTML/QuickForm/Rule/Range.php';
        $result['HTML_QuickForm_Rule_Regex'] = '/main/inc/lib/pear/HTML/QuickForm/Rule/Regex.php';
        $result['HTML_QuickForm_Rule_Required'] = '/main/inc/lib/pear/HTML/QuickForm/Rule/Required.php';
        $result['HTML_QuickForm_advanced_settings'] = '/main/inc/lib/pear/HTML/QuickForm/advanced_settings.php';
        $result['HTML_QuickForm_advcheckbox'] = '/main/inc/lib/pear/HTML/QuickForm/advcheckbox.php';
        $result['HTML_QuickForm_advmultiselect'] = '/main/inc/lib/pear/HTML/QuickForm/advmultiselect.php';
        $result['HTML_QuickForm_autocomplete'] = '/main/inc/lib/pear/HTML/QuickForm/autocomplete.php';
        $result['HTML_QuickForm_button'] = '/main/inc/lib/pear/HTML/QuickForm/button.php';
        $result['HTML_QuickForm_checkbox'] = '/main/inc/lib/pear/HTML/QuickForm/checkbox.php';
        $result['HTML_QuickForm_date'] = '/main/inc/lib/pear/HTML/QuickForm/date.php';
        $result['HTML_QuickForm_element'] = '/main/inc/lib/pear/HTML/QuickForm/element.php';
        $result['HTML_QuickForm_email'] = '/main/inc/lib/pear/HTML/QuickForm/email.php';
        $result['HTML_QuickForm_file'] = '/main/inc/lib/pear/HTML/QuickForm/file.php';
        $result['HTML_QuickForm_group'] = '/main/inc/lib/pear/HTML/QuickForm/group.php';
        $result['HTML_QuickForm_header'] = '/main/inc/lib/pear/HTML/QuickForm/header.php';
        $result['HTML_QuickForm_hidden'] = '/main/inc/lib/pear/HTML/QuickForm/hidden.php';
        $result['HTML_QuickForm_hiddenselect'] = '/main/inc/lib/pear/HTML/QuickForm/hiddenselect.php';
        $result['HTML_QuickForm_hierselect'] = '/main/inc/lib/pear/HTML/QuickForm/hierselect.php';
        $result['HTML_QuickForm_html'] = '/main/inc/lib/pear/HTML/QuickForm/html.php';
        $result['HTML_QuickForm_image'] = '/main/inc/lib/pear/HTML/QuickForm/image.php';
        $result['HTML_QuickForm_input'] = '/main/inc/lib/pear/HTML/QuickForm/input.php';
        $result['HTML_QuickForm_label'] = '/main/inc/lib/pear/HTML/QuickForm/label.php';
        $result['HTML_QuickForm_link'] = '/main/inc/lib/pear/HTML/QuickForm/link.php';
        $result['HTML_QuickForm_password'] = '/main/inc/lib/pear/HTML/QuickForm/password.php';
        $result['HTML_QuickForm_radio'] = '/main/inc/lib/pear/HTML/QuickForm/radio.php';
        $result['HTML_QuickForm_reset'] = '/main/inc/lib/pear/HTML/QuickForm/reset.php';
        $result['HTML_QuickForm_select'] = '/main/inc/lib/pear/HTML/QuickForm/select.php';
        $result['HTML_QuickForm_static'] = '/main/inc/lib/pear/HTML/QuickForm/static.php';
        $result['HTML_QuickForm_submit'] = '/main/inc/lib/pear/HTML/QuickForm/submit.php';
        $result['HTML_QuickForm_text'] = '/main/inc/lib/pear/HTML/QuickForm/text.php';
        $result['HTML_QuickForm_textarea'] = '/main/inc/lib/pear/HTML/QuickForm/textarea.php';
        $result['HTML_QuickForm_xbutton'] = '/main/inc/lib/pear/HTML/QuickForm/xbutton.php';
        $result['HTML_Table'] = '/main/inc/lib/pear/HTML/Table.php';
        $result['HTML_Table_Storage'] = '/main/inc/lib/pear/HTML/Table/Storage.php';
        $result['Header'] = '/main/inc/lib/system/web/header.class.php';
        $result['HotSpot'] = '/main/exercice/hotspot.class.php';
        $result['HotSpotDelineation'] = '/main/exercice/hotspot.class.php';
        $result['Html'] = '/main/inc/lib/html.class.php';
        $result['Html_Quickform_Rule_Date'] = '/main/inc/lib/pear/HTML/QuickForm/Rule/Date.php';
        $result['HttpResource'] = '/main/inc/lib/external_media/renderer/http_resource.class.php';
        $result['Image'] = '/main/inc/lib/image.lib.php';
        $result['ImageWrapper'] = '/main/inc/lib/image.lib.php';
        $result['ImagickWrapper'] = '/main/inc/lib/image.lib.php';
        $result['Import'] = '/main/inc/lib/import.lib.php';
        $result['InactiveCourseReport'] = '/main/admin/inactive_course_report.class.php';
        $result['IndexManager'] = '/main/inc/lib/userportal.lib.php';
        $result['IndexableChunk'] = '/main/inc/lib/search/IndexableChunk.class.php';
        $result['Install'] = '/main/install/install.class.php';
        $result['Javascript'] = '/main/inc/lib/javascript.class.php';
        $result['KeyAuth'] = '/main/auth/key/key_auth.class.php';
        $result['LearnpathLink'] = '/main/gradebook/lib/be/learnpathlink.class.php';
        $result['LegalManager'] = '/main/inc/lib/legal.lib.php';
        $result['LinkAddEditForm'] = '/main/gradebook/lib/fe/linkaddeditform.class.php';
        $result['LinkCategory'] = '/main/coursecopy/classes/LinkCategory.class.php';
        $result['LinkFactory'] = '/main/gradebook/lib/be/linkfactory.class.php';
        $result['LinkForm'] = '/main/gradebook/lib/fe/linkform.class.php';
        $result['Log'] = '/main/inc/lib/log.class.php';
        $result['Login'] = '/main/inc/lib/login.lib.php';
        $result['LoginRedirection'] = '/main/inc/lib/login_redirection.class.php';
        $result['Matching'] = '/main/exercice/matching.class.php';
        $result['MessageManager'] = '/main/inc/lib/message.lib.php';
        $result['Model\Course'] = '/main/inc/lib/course.class.php';
        $result['Model\Document'] = '/main/inc/lib/document.class.php';
        $result['Model\ItemProperty'] = '/main/inc/lib/item_property.class.php';
        $result['Model\StudentPublication'] = '/main/inc/lib/student_publication.class.php';
        $result['MultipleAnswer'] = '/main/exercice/multiple_answer.class.php';
        $result['MultipleAnswerCombination'] = '/main/exercice/multiple_answer_combination.class.php';
        $result['MultipleAnswerCombinationTrueFalse'] = '/main/exercice/multiple_answer_combination_true_false.class.php';
        $result['MultipleAnswerTrueFalse'] = '/main/exercice/multiple_answer_true_false.class.php';
        $result['MyHorBar'] = '/main/inc/lib/pchart/MyHorBar.class.php';
        $result['MySpace'] = '/main/mySpace/myspace.lib.php';
        $result['Nanogong'] = '/main/inc/lib/nanogong.lib.php';
        $result['NotebookManager'] = '/main/inc/lib/notebook.lib.php';
        $result['Notification'] = '/main/inc/lib/notification.lib.php';
        $result['OLE'] = '/main/inc/lib/pear/OLE/OLE.php';
        $result['OLE_ChainedBlockStream'] = '/main/inc/lib/pear/OLE/ChainedBlockStream.php';
        $result['OLE_PPS'] = '/main/inc/lib/pear/OLE/PPS.php';
        $result['OLE_PPS_File'] = '/main/inc/lib/pear/OLE/PPS/File.php';
        $result['OLE_PPS_Root'] = '/main/inc/lib/pear/OLE/PPS/Root.php';
        $result['OpenOfficeTextDocument'] = '/main/newscorm/openoffice_text_document.class.php';
        $result['OpenofficeDocument'] = '/main/newscorm/openoffice_document.class.php';
        $result['OpenofficePresentation'] = '/main/newscorm/openoffice_presentation.class.php';
        $result['OpenofficeText'] = '/main/newscorm/openoffice_text.class.php';
        $result['OralExpression'] = '/main/exercice/oral_expression.class.php';
        $result['PDF'] = '/main/inc/lib/pdf.lib.php';
        $result['PEAR'] = '/main/inc/lib/pear/PEAR.php';
        $result['PEAR5'] = '/main/inc/lib/pear/PEAR5.php';
        $result['PEAR_Error'] = '/main/inc/lib/pear/PEAR.php';
        $result['Page'] = '/main/inc/lib/page.class.php';
        $result['Pager'] = '/main/inc/lib/pear/Pager/Pager.php';
        $result['Pager_Common'] = '/main/inc/lib/pear/Pager/Common.php';
        $result['Pager_HtmlWidgets'] = '/main/inc/lib/pear/Pager/HtmlWidgets.php';
        $result['Pager_Jumping'] = '/main/inc/lib/pear/Pager/Jumping.php';
        $result['Pager_Sliding'] = '/main/inc/lib/pear/Pager/Sliding.php';
        $result['PclZip'] = '/main/inc/lib/pclzip/pclzip.lib.php';
        $result['Plugin'] = '/main/inc/lib/plugin.class.php';
        $result['Portfolio'] = '/main/inc/lib/portfolio.class.php';
        $result['PortfolioBulkAction'] = '/main/inc/lib/portfolio.class.php';
        $result['PortfolioController'] = '/main/inc/lib/portfolio.class.php';
        $result['PortfolioShare'] = '/main/inc/lib/portfolio.class.php';
        $result['Portfolio\Artefact'] = '/main/inc/lib/system/portfolio/artefact.class.php';
        $result['Portfolio\Download'] = '/main/inc/lib/system/portfolio/download.class.php';
        $result['Portfolio\Mahara'] = '/main/inc/lib/system/portfolio/mahara.class.php';
        $result['Portfolio\Portfolio'] = '/main/inc/lib/system/portfolio/portfolio.class.php';
        $result['Portfolio\User'] = '/main/inc/lib/system/portfolio/user.class.php';
        $result['Promotion'] = '/main/inc/lib/promotion.lib.php';
        $result['Question'] = '/main/exercice/question.class.php';
        $result['QuickformElement'] = '/main/inc/lib/pear/HTML/QuickForm/Renderer/Object.php';
        $result['QuickformFlexyElement'] = '/main/inc/lib/pear/HTML/QuickForm/Renderer/ObjectFlexy.php';
        $result['QuickformFlexyForm'] = '/main/inc/lib/pear/HTML/QuickForm/Renderer/ObjectFlexy.php';
        $result['QuickformForm'] = '/main/inc/lib/pear/HTML/QuickForm/Renderer/Object.php';
        $result['Quiz'] = '/main/coursecopy/classes/Quiz.class.php';
        $result['QuizQuestion'] = '/main/coursecopy/classes/QuizQuestion.class.php';
        $result['Redirect'] = '/main/inc/lib/redirect.class.php';
        $result['Request'] = '/main/inc/lib/system/web/request.class.php';
        $result['RequestServer'] = '/main/inc/lib/system/web/request_server.class.php';
        $result['Resource'] = '/main/coursecopy/classes/Resource.class.php';
        $result['Result'] = '/main/gradebook/lib/be/result.class.php';
        $result['ResultSet'] = '/main/inc/lib/result_set.class.php';
        $result['ResultTable'] = '/main/gradebook/lib/fe/resulttable.class.php';
        $result['ResultsDataGenerator'] = '/main/gradebook/lib/results_data_generator.class.php';
        $result['Rights'] = '/main/inc/lib/rights.lib.php';
        $result['Scaffolder'] = '/main/auth/shibboleth/lib/scaffolder/scaffolder.class.php';
        $result['ScoreDisplay'] = '/main/gradebook/lib/scoredisplay.class.php';
        $result['ScoreDisplayForm'] = '/main/gradebook/lib/fe/scoredisplayform.class.php';
        $result['ScormDocument'] = '/main/coursecopy/classes/ScormDocument.class.php';
        $result['Security'] = '/main/inc/lib/security.lib.php';
        $result['SessionHandler'] = '/main/inc/lib/session_handler.class.php';
        $result['SessionManager'] = '/main/inc/lib/sessionmanager.lib.php';
        $result['Shibboleth'] = '/main/auth/shibboleth/app/shibboleth.class.php';
        $result['ShibbolethConfig'] = '/main/auth/shibboleth/lib/shibboleth_config.class.php';
        $result['ShibbolethController'] = '/main/auth/shibboleth/app/controller/shibboleth_controller.class.php';
        $result['ShibbolethDisplay'] = '/main/auth/shibboleth/app/view/shibboleth_display.class.php';
        $result['ShibbolethEmailForm'] = '/main/auth/shibboleth/app/view/shibboleth_email_form.class.php';
        $result['ShibbolethSession'] = '/main/auth/shibboleth/lib/shibboleth_session.class.php';
        $result['ShibbolethStatusRequestForm'] = '/main/auth/shibboleth/app/view/shibboleth_status_request_form.class.php';
        $result['ShibbolethStore'] = '/main/auth/shibboleth/app/model/shibboleth_store.class.php';
        $result['ShibbolethUpgrade'] = '/main/auth/shibboleth/db/shibboleth_upgrade.class.php';
        $result['ShibbolethUser'] = '/main/auth/shibboleth/app/model/shibboleth_user.class.php';
        $result['Skill'] = '/main/inc/lib/skill.lib.php';
        $result['SkillProfile'] = '/main/inc/lib/skill.lib.php';
        $result['SkillRelGradebook'] = '/main/inc/lib/skill.lib.php';
        $result['SkillRelProfile'] = '/main/inc/lib/skill.lib.php';
        $result['SkillRelSkill'] = '/main/inc/lib/skill.lib.php';
        $result['SkillRelUser'] = '/main/inc/lib/skill.lib.php';
        $result['SkillVisualizer'] = '/main/inc/lib/skill.visualizer.lib.php';
        $result['SocialManager'] = '/main/inc/lib/social.lib.php';
        $result['SortableTable'] = '/main/inc/lib/sortable_table.class.php';
        $result['SortableTableFromArray'] = '/main/inc/lib/sortable_table.class.php';
        $result['SortableTableFromArrayConfig'] = '/main/inc/lib/sortable_table.class.php';
        $result['Spreadsheet_Excel_Reader'] = '/main/inc/lib/pear/excelreader/reader.php';
        $result['Spreadsheet_Excel_Writer'] = '/main/inc/lib/pear/Spreadsheet_Excel_Writer/Writer.php';
        $result['Spreadsheet_Excel_Writer_BIFFwriter'] = '/main/inc/lib/pear/Spreadsheet_Excel_Writer/Writer/BIFFwriter.php';
        $result['Spreadsheet_Excel_Writer_Format'] = '/main/inc/lib/pear/Spreadsheet_Excel_Writer/Writer/Format.php';
        $result['Spreadsheet_Excel_Writer_Parser'] = '/main/inc/lib/pear/Spreadsheet_Excel_Writer/Writer/Parser.php';
        $result['Spreadsheet_Excel_Writer_Validator'] = '/main/inc/lib/pear/Spreadsheet_Excel_Writer/Writer/Validator.php';
        $result['Spreadsheet_Excel_Writer_Workbook'] = '/main/inc/lib/pear/Spreadsheet_Excel_Writer/Writer/Workbook.php';
        $result['Spreadsheet_Excel_Writer_Worksheet'] = '/main/inc/lib/pear/Spreadsheet_Excel_Writer/Writer/Worksheet.php';
        $result['Statistics'] = '/main/admin/statistics/statistics.lib.php';
        $result['Store'] = '/main/auth/shibboleth/lib/store.class.php';
        $result['StudentPublicationLink'] = '/main/gradebook/lib/be/studentpublicationlink.class.php';
        $result['SubLanguageManager'] = '/main/admin/sub_language.class.php';
        $result['Survey'] = '/main/coursecopy/classes/Survey.class.php';
        $result['SurveyInvitation'] = '/main/coursecopy/classes/SurveyInvitation.class.php';
        $result['SurveyLink'] = '/main/gradebook/lib/be/surveylink.class.php';
        $result['SurveyQuestion'] = '/main/coursecopy/classes/SurveyQuestion.class.php';
        $result['SurveyTree'] = '/main/inc/lib/surveymanager.lib.php';
        $result['SurveyUtil'] = '/main/survey/survey.lib.php';
        $result['SystemAnnouncementManager'] = '/main/inc/lib/system_announcements.lib.php';
        $result['System\Session'] = '/main/inc/lib/system/session.class.php';
        $result['TableSort'] = '/main/inc/lib/table_sort.class.php';
        $result['Temp'] = '/main/inc/lib/system/io/temp.class.php';
        $result['Template'] = '/main/inc/lib/template.lib.php';
        $result['Text_Diff'] = '/main/inc/lib/pear/Text/Diff.php';
        $result['Text_Diff3'] = '/main/inc/lib/pear/Text/Diff3.php';
        $result['Text_Diff3_BlockBuilder'] = '/main/inc/lib/pear/Text/Diff3.php';
        $result['Text_Diff3_Op'] = '/main/inc/lib/pear/Text/Diff3.php';
        $result['Text_Diff3_Op_copy'] = '/main/inc/lib/pear/Text/Diff3.php';
        $result['Text_Diff_Engine_native'] = '/main/inc/lib/pear/Text/Diff/Engine/native.php';
        $result['Text_Diff_Engine_shell'] = '/main/inc/lib/pear/Text/Diff/Engine/shell.php';
        $result['Text_Diff_Engine_string'] = '/main/inc/lib/pear/Text/Diff/Engine/string.php';
        $result['Text_Diff_Engine_xdiff'] = '/main/inc/lib/pear/Text/Diff/Engine/xdiff.php';
        $result['Text_Diff_Mapped'] = '/main/inc/lib/pear/Text/Diff/Mapped.php';
        $result['Text_Diff_Op'] = '/main/inc/lib/pear/Text/Diff.php';
        $result['Text_Diff_Op_add'] = '/main/inc/lib/pear/Text/Diff.php';
        $result['Text_Diff_Op_change'] = '/main/inc/lib/pear/Text/Diff.php';
        $result['Text_Diff_Op_copy'] = '/main/inc/lib/pear/Text/Diff.php';
        $result['Text_Diff_Op_delete'] = '/main/inc/lib/pear/Text/Diff.php';
        $result['Text_Diff_Renderer'] = '/main/inc/lib/pear/Text/Diff/Renderer.php';
        $result['Text_Diff_Renderer_context'] = '/main/inc/lib/pear/Text/Diff/Renderer/context.php';
        $result['Text_Diff_Renderer_inline'] = '/main/inc/lib/pear/Text/Diff/Renderer/inline.php';
        $result['Text_Diff_Renderer_unified'] = '/main/inc/lib/pear/Text/Diff/Renderer/unified.php';
        $result['Text_Diff_ThreeWay'] = '/main/inc/lib/pear/Text/Diff/ThreeWay.php';
        $result['Text_Diff_ThreeWay_BlockBuilder'] = '/main/inc/lib/pear/Text/Diff/ThreeWay.php';
        $result['Text_Diff_ThreeWay_Op'] = '/main/inc/lib/pear/Text/Diff/ThreeWay.php';
        $result['Text_Diff_ThreeWay_Op_copy'] = '/main/inc/lib/pear/Text/Diff/ThreeWay.php';
        $result['Text_MappedDiff'] = '/main/inc/lib/pear/Text/Diff.php';
        $result['Timeline'] = '/main/inc/lib/timeline.lib.php';
        $result['ToolIntro'] = '/main/coursecopy/classes/ToolIntro.class.php';
        $result['Tracking'] = '/main/inc/lib/tracking.lib.php';
        $result['TrackingCourseLog'] = '/main/inc/lib/tracking.lib.php';
        $result['TrackingUserLog'] = '/main/inc/lib/tracking.lib.php';
        $result['TrackingUserLogCSV'] = '/main/inc/lib/tracking.lib.php';
        $result['UniqueAnswer'] = '/main/exercice/unique_answer.class.php';
        $result['UniqueAnswerNoOption'] = '/main/exercice/unique_answer_no_option.class.php';
        $result['Uri'] = '/main/inc/lib/uri.class.php';
        $result['UrlManager'] = '/main/inc/lib/urlmanager.lib.php';
        $result['User'] = '/main/auth/shibboleth/app/model/user.class.php';
        $result['UserApiKeyManager'] = '/main/inc/lib/user_api_key_manager.class.php';
        $result['UserDataGenerator'] = '/main/gradebook/lib/user_data_generator.class.php';
        $result['UserForm'] = '/main/gradebook/lib/fe/userform.class.php';
        $result['UserGroup'] = '/main/inc/lib/usergroup.lib.php';
        $result['UserManager'] = '/main/inc/lib/usermanager.lib.php';
        $result['UserStore'] = '/main/auth/shibboleth/app/model/user.class.php';
        $result['UserTable'] = '/main/gradebook/lib/fe/usertable.class.php';
        $result['Utf8'] = '/main/inc/lib/system/text/utf8.class.php';
        $result['Utf8Decoder'] = '/main/inc/lib/system/text/utf8_decoder.class.php';
        $result['Utf8Encoder'] = '/main/inc/lib/system/text/utf8_encoder.class.php';
        $result['Wiki'] = '/main/coursecopy/classes/wiki.class.php';
        $result['XapianIndexer'] = '/main/inc/lib/search/xapian/XapianIndexer.class.php';
        $result['Zip'] = '/main/inc/lib/zip.class.php';
        $result['ZombieManager'] = '/main/inc/lib/zombie/zombie_manager.class.php';
        $result['ZombieReport'] = '/main/inc/lib/zombie/zombie_report.class.php';
        $result['_Admin'] = '/main/auth/shibboleth/app/model/scaffold/admin.class.php';
        $result['_AdminStore'] = '/main/auth/shibboleth/app/model/scaffold/admin.class.php';
        $result['_IndexableChunk'] = '/main/inc/lib/search/IndexableChunk.class.php';
        $result['_User'] = '/main/auth/shibboleth/app/model/scaffold/user.class.php';
        $result['_UserStore'] = '/main/auth/shibboleth/app/model/scaffold/user.class.php';
        $result['aai'] = '/main/auth/shibboleth/config/aai.class.php';
        $result['aicc'] = '/main/newscorm/aicc.class.php';
        $result['aiccBlock'] = '/main/newscorm/aiccBlock.class.php';
        $result['aiccItem'] = '/main/newscorm/aiccItem.class.php';
        $result['aiccObjective'] = '/main/newscorm/aiccObjective.class.php';
        $result['aiccResource'] = '/main/newscorm/aiccResource.class.php';
        $result['api_failure'] = '/main/inc/lib/main_api.lib.php';
        $result['calendarComponent'] = '/main/inc/lib/icalcreator/iCalcreator.class.php';
        $result['ch_comment'] = '/main/survey/survey.lib.php';
        $result['ch_dropdown'] = '/main/survey/survey.lib.php';
        $result['ch_multiplechoice'] = '/main/survey/survey.lib.php';
        $result['ch_multipleresponse'] = '/main/survey/survey.lib.php';
        $result['ch_open'] = '/main/survey/survey.lib.php';
        $result['ch_pagebreak'] = '/main/survey/survey.lib.php';
        $result['ch_percentage'] = '/main/survey/survey.lib.php';
        $result['ch_personality'] = '/main/survey/survey.lib.php';
        $result['ch_score'] = '/main/survey/survey.lib.php';
        $result['ch_yesno'] = '/main/survey/survey.lib.php';
        $result['db'] = '/main/inc/lib/db.lib.php';
        $result['document_processor'] = '/main/inc/lib/search/tool_processors/document_processor.class.php';
        $result['iDatabase'] = '/main/install/i_database.class.php';
        $result['langstats'] = '/main/cron/lang/langstats.class.php';
        $result['learnpath'] = '/main/newscorm/learnpath.class.php';
        $result['learnpathItem'] = '/main/newscorm/learnpathItem.class.php';
        $result['learnpathList'] = '/main/newscorm/learnpathList.class.php';
        $result['learnpath_processor'] = '/main/inc/lib/search/tool_processors/learnpath_processor.class.php';
        $result['link_processor'] = '/main/inc/lib/search/tool_processors/link_processor.class.php';
        $result['net\HttpChannel'] = '/main/inc/lib/system/net/http_channel.class.php';
        $result['pCache'] = '/main/inc/lib/pchart/pCache.class.php';
        $result['pChart'] = '/main/inc/lib/pchart/pChart.class.php';
        $result['pData'] = '/main/inc/lib/pchart/pData.class.php';
        $result['quiz_processor'] = '/main/inc/lib/search/tool_processors/quiz_processor.class.php';
        $result['scorm'] = '/main/newscorm/scorm.class.php';
        $result['scormItem'] = '/main/newscorm/scormItem.class.php';
        $result['scormMetadata'] = '/main/newscorm/scormMetadata.class.php';
        $result['scormOrganization'] = '/main/newscorm/scormOrganization.class.php';
        $result['scormResource'] = '/main/newscorm/scormResource.class.php';
        $result['search_processor'] = '/main/inc/lib/search/tool_processors/search_processor.class.php';
        $result['sso'] = '/main/auth/sso/sso.class.php';
        $result['survey_manager'] = '/main/survey/survey.lib.php';
        $result['survey_question'] = '/main/survey/survey.lib.php';
        $result['valarm'] = '/main/inc/lib/icalcreator/iCalcreator.class.php';
        $result['vcalendar'] = '/main/inc/lib/icalcreator/iCalcreator.class.php';
        $result['vevent'] = '/main/inc/lib/icalcreator/iCalcreator.class.php';
        $result['vfreebusy'] = '/main/inc/lib/icalcreator/iCalcreator.class.php';
        $result['vjournal'] = '/main/inc/lib/icalcreator/iCalcreator.class.php';
        $result['vtimezone'] = '/main/inc/lib/icalcreator/iCalcreator.class.php';
        $result['vtodo'] = '/main/inc/lib/icalcreator/iCalcreator.class.php';
        $result['xhtdoc'] = '/main/inc/lib/xht.lib.php';
        $result['xmddoc'] = '/main/inc/lib/xmd.lib.php';

        return $result;
    }

    /**
     * Handles autoloading of classes.
     *
     * @param  string  $class_name  A class name.
     *
     * @return boolean returns true if the class has been loaded
     */
    public function load($class_name)
    {
        $root = dirname(__FILE__) . '/../../../';
        $map = &self::map();
        if (isset($map[$class_name])) {
            $path = $root . $map[$class_name];
            if (file_exists($path) && is_file($path)) {
                require_once $path;
                return true;
            }
        }
        return false;
    }

}

/**
 * Scan directorie for class declarations and returns an array made of 
 * 
 *  classname => relative path
 * 
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class AutoloadClassFinder
{

    protected $root_dir;
    protected $map = array();
    protected $duplicates = array();

    public function __construct($root_dir = null)
    {
        $root_dir = $root_dir ? $root_dir : Chamilo::root();
        $root_dir = realpath($root_dir);
        $this->root_dir = $root_dir;
    }

    public function get_map()
    {
        return $this->map;
    }

    public function get_duplicates()
    {
        return $this->duplicates;
    }

    public function run()
    {
        $this->synch(Chamilo::path('/main'));
        ksort($this->map);
    }

    public function __invoke()
    {
        $this->run();
    }

    public function __toString()
    {
        $result = array();

        $result[] = '$result = array();';
        foreach ($this->map as $name => $path) {
            $result[] = '$result[' . "'" . $name . "']" . ' = ' . "'" . $path . "';";
        }

        $result[] = "<br/>Duplicates </br>";

        foreach ($this->get_duplicates() as $key => $items) {
            foreach ($items as $value) {
                $result[] = "$key => $value";
            }
        }
        return implode("<br/>", $result);
    }

    protected function accept_file($path)
    {
        if (!is_readable($path)) {
            return false;
        }
        if (!is_file($path)) {
            return false;
        }
        if (strpos($path, '.php') === false) {
            return false;
        }
        if (strpos($path, 'autoload.class.php') !== false) {
            return false;
        }
        if (strpos($path, 'test') !== false) {
            return false;
        }
        if (strpos($path, '.class.php') !== false) {
            return true;
        }
        if (strpos($path, '.lib.php') !== false) {
            return true;
        }
        if (strpos($path, 'pear')) {
            return true;
        }
        return false;
    }

    /**
     * Synchronize the autoloader map with the current file structure.
     * 
     * Searches all files and sub directories for class declarations.
     * Creates a map of class name to (relative) file path.
     * Update the autoloader with the map declaration if $update equals true.
     * Returns a map of class name to file path.
     * 
     * @param string $current_dir   The current directory in which we search for class declarations
     */
    protected function synch($current_dir = null)
    {
        $result = array();

        $root_dir = $this->root_dir;

        $current_dir = $current_dir ? $current_dir : $root_dir;
        $current_dir = realpath($current_dir);

        //plugins are not handled by the autoloader. 
        if (basename($current_dir) == 'plugin') {
            return $result;
        }

        $files = scandir($current_dir);
        $files = array_diff($files, array('.', '..'));

        foreach ($files as $file) {
            $path = $current_dir . '/' . $file;
            if ($this->accept_file($path)) {
                $content = file_get_contents($path);
                $classes = CodeUtilities::get_classes($content);

                $namespace = CodeUtilities::get_namespace($content);
                $namespace = $namespace ? $namespace . '\\' : '';

                foreach ($classes as $class) {
                    /* a few classes have the same namespace and class name
                     * in this case we let the latest win as this may 
                     * relates to different autoloader.
                     */
                    $rel_path = realpath($path);
                    $rel_path = str_ireplace($root_dir, '', $rel_path);
                    $rel_path = str_replace('\\', '/', $rel_path);

                    $key = $namespace . $class;

                    if (isset($this->duplicates[$key])) {
                        $this->duplicates[$key][] = $rel_path;
                    } else if (isset($this->map[$key])) {
                        if (!isset($this->duplicates[$key])) {
                            $this->duplicates[$key] = array();
                        }
                        $this->duplicates[$key][] = $rel_path;
                        $this->duplicates[$key][] = $this->map[$key];
                        unset($this->map[$key]);
                    } else {
                        $this->map[$key] = $rel_path;
                    }
                }
            }
        }

        foreach ($files as $dir) {
            $path = $current_dir . '/' . $dir;
            if (is_dir($path)) {
                $this->synch($current_dir . '/' . $dir);
            }
        }
    }

}
