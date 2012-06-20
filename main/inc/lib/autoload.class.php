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

        $result['CodeUtilities'] = '/main/inc/lib/code_utilities.class.php';
        $result['db'] = '/main/inc/lib/db.class.php';

        $result['AbstractLink'] = '/main/gradebook/lib/be/abstractlink.class.php';
        $result['AccessToken'] = '/main/inc/lib/access_token.class.php';
        $result['Accessurleditcoursestourl'] = '/main/inc/lib/access_url_edit_courses_to_url_functions.lib.php';
        $result['Accessurleditsessionstourl'] = '/main/inc/lib/access_url_edit_sessions_to_url_functions.lib.php';
        $result['Accessurledituserstourl'] = '/main/inc/lib/access_url_edit_users_to_url_functions.lib.php';
        $result['AddCourseToSession'] = '/main/inc/lib/add_courses_to_session_functions.lib.php';
        $result['AddManySessionToCategoryFunctions'] = '/main/inc/lib/add_many_session_to_category_functions.lib.php';
        $result['AdminPage'] = '/main/admin/admin_page.class.php';
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
        $result['ConditionalLogin'] = '/main/inc/lib/conditional_login.class.php';
        $result['Converter'] = '/main/inc/lib/system/text/converter.class.php';
        $result['Course'] = '/main/coursecopy/classes/Course.class.php';
        $result['CourseArchiver'] = '/main/coursecopy/classes/CourseArchiver.class.php';
        $result['CourseBuilder'] = '/main/coursecopy/classes/CourseBuilder.class.php';
        $result['CourseCopyLearnpath'] = '/main/coursecopy/classes/CourseCopyLearnpath.class.php';
        $result['CourseEntity'] = '/main/inc/lib/course_entity.class.php';
        $result['CourseEntityRepository'] = '/main/inc/lib/course_entity_repository.class.php';
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
        $result['Display'] = '/main/inc/lib/display.lib.php';
        $result['Document'] = '/main/coursecopy/classes/Document.class.php';
        $result['DocumentManager'] = '/main/inc/lib/document.lib.php';
        $result['DokeosIndexer'] = '/main/inc/lib/search/DokeosIndexer.class.php';
        $result['DropboxLink'] = '/main/gradebook/lib/be/dropboxlink.class.php';
        $result['DummyCourseCreator'] = '/main/coursecopy/classes/DummyCourseCreator.class.php';
        $result['Encoding'] = '/main/inc/lib/system/text/encoding.class.php';
        $result['EncodingConverter'] = '/main/inc/lib/system/text/encoding_converter.class.php';
        $result['Entity'] = '/main/inc/lib/entity.class.php';
        $result['EntityRepository'] = '/main/inc/lib/entity_repository.class.php';
        $result['Entity\AccessUrl'] = '/main/inc/entity/access_url.class.php';
        $result['Entity\AccessUrlRelCourse'] = '/main/inc/entity/access_url_rel_course.class.php';
        $result['Entity\AccessUrlRelSession'] = '/main/inc/entity/access_url_rel_session.class.php';
        $result['Entity\AccessUrlRelUser'] = '/main/inc/entity/access_url_rel_user.class.php';
        $result['Entity\Admin'] = '/main/inc/entity/admin.class.php';
        $result['Entity\Announcement'] = '/main/inc/entity/announcement.class.php';
        $result['Entity\AnnouncementAttachment'] = '/main/inc/entity/announcement_attachment.class.php';
        $result['Entity\AnnouncementRelGroup'] = '/main/inc/entity/announcement_rel_group.class.php';
        $result['Entity\Attendance'] = '/main/inc/entity/attendance.class.php';
        $result['Entity\AttendanceCalendar'] = '/main/inc/entity/attendance_calendar.class.php';
        $result['Entity\AttendanceResult'] = '/main/inc/entity/attendance_result.class.php';
        $result['Entity\AttendanceSheet'] = '/main/inc/entity/attendance_sheet.class.php';
        $result['Entity\AttendanceSheetLog'] = '/main/inc/entity/attendance_sheet_log.class.php';
        $result['Entity\Block'] = '/main/inc/entity/block.class.php';
        $result['Entity\Blog'] = '/main/inc/entity/blog.class.php';
        $result['Entity\BlogAttachment'] = '/main/inc/entity/blog_attachment.class.php';
        $result['Entity\BlogComment'] = '/main/inc/entity/blog_comment.class.php';
        $result['Entity\BlogPost'] = '/main/inc/entity/blog_post.class.php';
        $result['Entity\BlogRating'] = '/main/inc/entity/blog_rating.class.php';
        $result['Entity\BlogRelUser'] = '/main/inc/entity/blog_rel_user.class.php';
        $result['Entity\BlogTask'] = '/main/inc/entity/blog_task.class.php';
        $result['Entity\BlogTaskRelUser'] = '/main/inc/entity/blog_task_rel_user.class.php';
        $result['Entity\CalendarEvent'] = '/main/inc/entity/calendar_event.class.php';
        $result['Entity\CalendarEventAttachment'] = '/main/inc/entity/calendar_event_attachment.class.php';
        $result['Entity\CalendarEventRepeat'] = '/main/inc/entity/calendar_event_repeat.class.php';
        $result['Entity\CalendarEventRepeatNot'] = '/main/inc/entity/calendar_event_repeat_not.class.php';
        $result['Entity\Career'] = '/main/inc/entity/career.class.php';
        $result['Entity\Chat'] = '/main/inc/entity/chat.class.php';
        $result['Entity\ChatConnected'] = '/main/inc/entity/chat_connected.class.php';
        $result['Entity\ClassUser'] = '/main/inc/entity/class_user.class.php';
        $result['Entity\Course'] = '/main/inc/entity/course.class.php';
        $result['Entity\CourseCategory'] = '/main/inc/entity/course_category.class.php';
        $result['Entity\CourseDescription'] = '/main/inc/entity/course_description.class.php';
        $result['Entity\CourseField'] = '/main/inc/entity/course_field.class.php';
        $result['Entity\CourseFieldValues'] = '/main/inc/entity/course_field_values.class.php';
        $result['Entity\CourseModule'] = '/main/inc/entity/course_module.class.php';
        $result['Entity\CourseRelClass'] = '/main/inc/entity/course_rel_class.class.php';
        $result['Entity\CourseRelUser'] = '/main/inc/entity/course_rel_user.class.php';
        $result['Entity\CourseRequest'] = '/main/inc/entity/course_request.class.php';
        $result['Entity\CourseSetting'] = '/main/inc/entity/course_setting.class.php';
        $result['Entity\CourseType'] = '/main/inc/entity/course_type.class.php';
        $result['Entity\Document'] = '/main/inc/entity/document.class.php';
        $result['Entity\DropboxCategory'] = '/main/inc/entity/dropbox_category.class.php';
        $result['Entity\DropboxFeedback'] = '/main/inc/entity/dropbox_feedback.class.php';
        $result['Entity\DropboxFile'] = '/main/inc/entity/dropbox_file.class.php';
        $result['Entity\DropboxPerson'] = '/main/inc/entity/dropbox_person.class.php';
        $result['Entity\DropboxPost'] = '/main/inc/entity/dropbox_post.class.php';
        $result['Entity\EventEmailTemplate'] = '/main/inc/entity/event_email_template.class.php';
        $result['Entity\EventSent'] = '/main/inc/entity/event_sent.class.php';
        $result['Entity\ForumAttachment'] = '/main/inc/entity/forum_attachment.class.php';
        $result['Entity\ForumCategory'] = '/main/inc/entity/forum_category.class.php';
        $result['Entity\ForumForum'] = '/main/inc/entity/forum_forum.class.php';
        $result['Entity\ForumMailcue'] = '/main/inc/entity/forum_mailcue.class.php';
        $result['Entity\ForumNotification'] = '/main/inc/entity/forum_notification.class.php';
        $result['Entity\ForumPost'] = '/main/inc/entity/forum_post.class.php';
        $result['Entity\ForumThread'] = '/main/inc/entity/forum_thread.class.php';
        $result['Entity\ForumThreadQualify'] = '/main/inc/entity/forum_thread_qualify.class.php';
        $result['Entity\ForumThreadQualifyLog'] = '/main/inc/entity/forum_thread_qualify_log.class.php';
        $result['Entity\Glossary'] = '/main/inc/entity/glossary.class.php';
        $result['Entity\GradeComponents'] = '/main/inc/entity/grade_components.class.php';
        $result['Entity\GradeModel'] = '/main/inc/entity/grade_model.class.php';
        $result['Entity\GradebookCategory'] = '/main/inc/entity/gradebook_category.class.php';
        $result['Entity\GradebookCertificate'] = '/main/inc/entity/gradebook_certificate.class.php';
        $result['Entity\GradebookEvaluation'] = '/main/inc/entity/gradebook_evaluation.class.php';
        $result['Entity\GradebookLink'] = '/main/inc/entity/gradebook_link.class.php';
        $result['Entity\GradebookLinkevalLog'] = '/main/inc/entity/gradebook_linkeval_log.class.php';
        $result['Entity\GradebookResult'] = '/main/inc/entity/gradebook_result.class.php';
        $result['Entity\GradebookResultLog'] = '/main/inc/entity/gradebook_result_log.class.php';
        $result['Entity\GradebookScoreDisplay'] = '/main/inc/entity/gradebook_score_display.class.php';
        $result['Entity\GroupCategory'] = '/main/inc/entity/group_category.class.php';
        $result['Entity\GroupInfo'] = '/main/inc/entity/group_info.class.php';
        $result['Entity\GroupRelGroup'] = '/main/inc/entity/group_rel_group.class.php';
        $result['Entity\GroupRelTag'] = '/main/inc/entity/group_rel_tag.class.php';
        $result['Entity\GroupRelTutor'] = '/main/inc/entity/group_rel_tutor.class.php';
        $result['Entity\GroupRelUser'] = '/main/inc/entity/group_rel_user.class.php';
        $result['Entity\Groups'] = '/main/inc/entity/groups.class.php';
        $result['Entity\ItemProperty'] = '/main/inc/entity/item_property.class.php';
        $result['Entity\Language'] = '/main/inc/entity/language.class.php';
        $result['Entity\Legal'] = '/main/inc/entity/legal.class.php';
        $result['Entity\Link'] = '/main/inc/entity/link.class.php';
        $result['Entity\LinkCategory'] = '/main/inc/entity/link_category.class.php';
        $result['Entity\Lp'] = '/main/inc/entity/lp.class.php';
        $result['Entity\LpItem'] = '/main/inc/entity/lp_item.class.php';
        $result['Entity\LpItemView'] = '/main/inc/entity/lp_item_view.class.php';
        $result['Entity\LpIvInteraction'] = '/main/inc/entity/lp_iv_interaction.class.php';
        $result['Entity\LpIvObjective'] = '/main/inc/entity/lp_iv_objective.class.php';
        $result['Entity\LpView'] = '/main/inc/entity/lp_view.class.php';
        $result['Entity\Message'] = '/main/inc/entity/message.class.php';
        $result['Entity\MessageAttachment'] = '/main/inc/entity/message_attachment.class.php';
        $result['Entity\Metadata'] = '/main/inc/entity/metadata.class.php';
        $result['Entity\Notebook'] = '/main/inc/entity/notebook.class.php';
        $result['Entity\Notification'] = '/main/inc/entity/notification.class.php';
        $result['Entity\OnlineConnected'] = '/main/inc/entity/online_connected.class.php';
        $result['Entity\OnlineLink'] = '/main/inc/entity/online_link.class.php';
        $result['Entity\OpenidAssociation'] = '/main/inc/entity/openid_association.class.php';
        $result['Entity\PermissionGroup'] = '/main/inc/entity/permission_group.class.php';
        $result['Entity\PermissionTask'] = '/main/inc/entity/permission_task.class.php';
        $result['Entity\PermissionUser'] = '/main/inc/entity/permission_user.class.php';
        $result['Entity\PersonalAgenda'] = '/main/inc/entity/personal_agenda.class.php';
        $result['Entity\PersonalAgendaRepeat'] = '/main/inc/entity/personal_agenda_repeat.class.php';
        $result['Entity\PersonalAgendaRepeatNot'] = '/main/inc/entity/personal_agenda_repeat_not.class.php';
        $result['Entity\PhpSession'] = '/main/inc/entity/php_session.class.php';
        $result['Entity\Promotion'] = '/main/inc/entity/promotion.class.php';
        $result['Entity\Quiz'] = '/main/inc/entity/quiz.class.php';
        $result['Entity\QuizAnswer'] = '/main/inc/entity/quiz_answer.class.php';
        $result['Entity\QuizQuestion'] = '/main/inc/entity/quiz_question.class.php';
        $result['Entity\QuizQuestionCategory'] = '/main/inc/entity/quiz_question_category.class.php';
        $result['Entity\QuizQuestionOption'] = '/main/inc/entity/quiz_question_option.class.php';
        $result['Entity\QuizQuestionRelCategory'] = '/main/inc/entity/quiz_question_rel_category.class.php';
        $result['Entity\QuizRelQuestion'] = '/main/inc/entity/quiz_rel_question.class.php';
        $result['Entity\Repository\AccessUrlRelCourseRepository'] = '/main/inc/entity/repository/access_url_rel_course_repository.class.php';
        $result['Entity\Repository\AccessUrlRelSessionRepository'] = '/main/inc/entity/repository/access_url_rel_session_repository.class.php';
        $result['Entity\Repository\AccessUrlRelUserRepository'] = '/main/inc/entity/repository/access_url_rel_user_repository.class.php';
        $result['Entity\Repository\AccessUrlRepository'] = '/main/inc/entity/repository/access_url_repository.class.php';
        $result['Entity\Repository\AdminRepository'] = '/main/inc/entity/repository/admin_repository.class.php';
        $result['Entity\Repository\AnnouncementAttachmentRepository'] = '/main/inc/entity/repository/announcement_attachment_repository.class.php';
        $result['Entity\Repository\AnnouncementRelGroupRepository'] = '/main/inc/entity/repository/announcement_rel_group_repository.class.php';
        $result['Entity\Repository\AnnouncementRepository'] = '/main/inc/entity/repository/announcement_repository.class.php';
        $result['Entity\Repository\AttendanceCalendarRepository'] = '/main/inc/entity/repository/attendance_calendar_repository.class.php';
        $result['Entity\Repository\AttendanceRepository'] = '/main/inc/entity/repository/attendance_repository.class.php';
        $result['Entity\Repository\AttendanceResultRepository'] = '/main/inc/entity/repository/attendance_result_repository.class.php';
        $result['Entity\Repository\AttendanceSheetLogRepository'] = '/main/inc/entity/repository/attendance_sheet_log_repository.class.php';
        $result['Entity\Repository\AttendanceSheetRepository'] = '/main/inc/entity/repository/attendance_sheet_repository.class.php';
        $result['Entity\Repository\BlockRepository'] = '/main/inc/entity/repository/block_repository.class.php';
        $result['Entity\Repository\BlogAttachmentRepository'] = '/main/inc/entity/repository/blog_attachment_repository.class.php';
        $result['Entity\Repository\BlogCommentRepository'] = '/main/inc/entity/repository/blog_comment_repository.class.php';
        $result['Entity\Repository\BlogPostRepository'] = '/main/inc/entity/repository/blog_post_repository.class.php';
        $result['Entity\Repository\BlogRatingRepository'] = '/main/inc/entity/repository/blog_rating_repository.class.php';
        $result['Entity\Repository\BlogRelUserRepository'] = '/main/inc/entity/repository/blog_rel_user_repository.class.php';
        $result['Entity\Repository\BlogRepository'] = '/main/inc/entity/repository/blog_repository.class.php';
        $result['Entity\Repository\BlogTaskRelUserRepository'] = '/main/inc/entity/repository/blog_task_rel_user_repository.class.php';
        $result['Entity\Repository\BlogTaskRepository'] = '/main/inc/entity/repository/blog_task_repository.class.php';
        $result['Entity\Repository\CalendarEventAttachmentRepository'] = '/main/inc/entity/repository/calendar_event_attachment_repository.class.php';
        $result['Entity\Repository\CalendarEventRepeatNotRepository'] = '/main/inc/entity/repository/calendar_event_repeat_not_repository.class.php';
        $result['Entity\Repository\CalendarEventRepeatRepository'] = '/main/inc/entity/repository/calendar_event_repeat_repository.class.php';
        $result['Entity\Repository\CalendarEventRepository'] = '/main/inc/entity/repository/calendar_event_repository.class.php';
        $result['Entity\Repository\CareerRepository'] = '/main/inc/entity/repository/career_repository.class.php';
        $result['Entity\Repository\ChatConnectedRepository'] = '/main/inc/entity/repository/chat_connected_repository.class.php';
        $result['Entity\Repository\ChatRepository'] = '/main/inc/entity/repository/chat_repository.class.php';
        $result['Entity\Repository\ClassRepository'] = '/main/inc/entity/repository/class_repository.class.php';
        $result['Entity\Repository\ClassUserRepository'] = '/main/inc/entity/repository/class_user_repository.class.php';
        $result['Entity\Repository\CourseCategoryRepository'] = '/main/inc/entity/repository/course_category_repository.class.php';
        $result['Entity\Repository\CourseDescriptionRepository'] = '/main/inc/entity/repository/course_description_repository.class.php';
        $result['Entity\Repository\CourseFieldRepository'] = '/main/inc/entity/repository/course_field_repository.class.php';
        $result['Entity\Repository\CourseFieldValuesRepository'] = '/main/inc/entity/repository/course_field_values_repository.class.php';
        $result['Entity\Repository\CourseModuleRepository'] = '/main/inc/entity/repository/course_module_repository.class.php';
        $result['Entity\Repository\CourseRelClassRepository'] = '/main/inc/entity/repository/course_rel_class_repository.class.php';
        $result['Entity\Repository\CourseRelUserRepository'] = '/main/inc/entity/repository/course_rel_user_repository.class.php';
        $result['Entity\Repository\CourseRepository'] = '/main/inc/entity/repository/course_repository.class.php';
        $result['Entity\Repository\CourseRequestRepository'] = '/main/inc/entity/repository/course_request_repository.class.php';
        $result['Entity\Repository\CourseSettingRepository'] = '/main/inc/entity/repository/course_setting_repository.class.php';
        $result['Entity\Repository\CourseTypeRepository'] = '/main/inc/entity/repository/course_type_repository.class.php';
        $result['Entity\Repository\DocumentRepository'] = '/main/inc/entity/repository/document_repository.class.php';
        $result['Entity\Repository\DropboxCategoryRepository'] = '/main/inc/entity/repository/dropbox_category_repository.class.php';
        $result['Entity\Repository\DropboxFeedbackRepository'] = '/main/inc/entity/repository/dropbox_feedback_repository.class.php';
        $result['Entity\Repository\DropboxFileRepository'] = '/main/inc/entity/repository/dropbox_file_repository.class.php';
        $result['Entity\Repository\DropboxPersonRepository'] = '/main/inc/entity/repository/dropbox_person_repository.class.php';
        $result['Entity\Repository\DropboxPostRepository'] = '/main/inc/entity/repository/dropbox_post_repository.class.php';
        $result['Entity\Repository\EventEmailTemplateRepository'] = '/main/inc/entity/repository/event_email_template_repository.class.php';
        $result['Entity\Repository\EventSentRepository'] = '/main/inc/entity/repository/event_sent_repository.class.php';
        $result['Entity\Repository\ForumAttachmentRepository'] = '/main/inc/entity/repository/forum_attachment_repository.class.php';
        $result['Entity\Repository\ForumCategoryRepository'] = '/main/inc/entity/repository/forum_category_repository.class.php';
        $result['Entity\Repository\ForumForumRepository'] = '/main/inc/entity/repository/forum_forum_repository.class.php';
        $result['Entity\Repository\ForumMailcueRepository'] = '/main/inc/entity/repository/forum_mailcue_repository.class.php';
        $result['Entity\Repository\ForumNotificationRepository'] = '/main/inc/entity/repository/forum_notification_repository.class.php';
        $result['Entity\Repository\ForumPostRepository'] = '/main/inc/entity/repository/forum_post_repository.class.php';
        $result['Entity\Repository\ForumThreadQualifyLogRepository'] = '/main/inc/entity/repository/forum_thread_qualify_log_repository.class.php';
        $result['Entity\Repository\ForumThreadQualifyRepository'] = '/main/inc/entity/repository/forum_thread_qualify_repository.class.php';
        $result['Entity\Repository\ForumThreadRepository'] = '/main/inc/entity/repository/forum_thread_repository.class.php';
        $result['Entity\Repository\GlossaryRepository'] = '/main/inc/entity/repository/glossary_repository.class.php';
        $result['Entity\Repository\GradeComponentsRepository'] = '/main/inc/entity/repository/grade_components_repository.class.php';
        $result['Entity\Repository\GradeModelRepository'] = '/main/inc/entity/repository/grade_model_repository.class.php';
        $result['Entity\Repository\GradebookCategoryRepository'] = '/main/inc/entity/repository/gradebook_category_repository.class.php';
        $result['Entity\Repository\GradebookCertificateRepository'] = '/main/inc/entity/repository/gradebook_certificate_repository.class.php';
        $result['Entity\Repository\GradebookEvaluationRepository'] = '/main/inc/entity/repository/gradebook_evaluation_repository.class.php';
        $result['Entity\Repository\GradebookLinkRepository'] = '/main/inc/entity/repository/gradebook_link_repository.class.php';
        $result['Entity\Repository\GradebookLinkevalLogRepository'] = '/main/inc/entity/repository/gradebook_linkeval_log_repository.class.php';
        $result['Entity\Repository\GradebookResultLogRepository'] = '/main/inc/entity/repository/gradebook_result_log_repository.class.php';
        $result['Entity\Repository\GradebookResultRepository'] = '/main/inc/entity/repository/gradebook_result_repository.class.php';
        $result['Entity\Repository\GradebookScoreDisplayRepository'] = '/main/inc/entity/repository/gradebook_score_display_repository.class.php';
        $result['Entity\Repository\GroupCategoryRepository'] = '/main/inc/entity/repository/group_category_repository.class.php';
        $result['Entity\Repository\GroupInfoRepository'] = '/main/inc/entity/repository/group_info_repository.class.php';
        $result['Entity\Repository\GroupRelGroupRepository'] = '/main/inc/entity/repository/group_rel_group_repository.class.php';
        $result['Entity\Repository\GroupRelTagRepository'] = '/main/inc/entity/repository/group_rel_tag_repository.class.php';
        $result['Entity\Repository\GroupRelTutorRepository'] = '/main/inc/entity/repository/group_rel_tutor_repository.class.php';
        $result['Entity\Repository\GroupRelUserRepository'] = '/main/inc/entity/repository/group_rel_user_repository.class.php';
        $result['Entity\Repository\GroupsRepository'] = '/main/inc/entity/repository/groups_repository.class.php';
        $result['Entity\Repository\ItemPropertyRepository'] = '/main/inc/entity/repository/item_property_repository.class.php';
        $result['Entity\Repository\LanguageRepository'] = '/main/inc/entity/repository/language_repository.class.php';
        $result['Entity\Repository\LegalRepository'] = '/main/inc/entity/repository/legal_repository.class.php';
        $result['Entity\Repository\LinkCategoryRepository'] = '/main/inc/entity/repository/link_category_repository.class.php';
        $result['Entity\Repository\LinkRepository'] = '/main/inc/entity/repository/link_repository.class.php';
        $result['Entity\Repository\LpItemRepository'] = '/main/inc/entity/repository/lp_item_repository.class.php';
        $result['Entity\Repository\LpItemViewRepository'] = '/main/inc/entity/repository/lp_item_view_repository.class.php';
        $result['Entity\Repository\LpIvInteractionRepository'] = '/main/inc/entity/repository/lp_iv_interaction_repository.class.php';
        $result['Entity\Repository\LpIvObjectiveRepository'] = '/main/inc/entity/repository/lp_iv_objective_repository.class.php';
        $result['Entity\Repository\LpRepository'] = '/main/inc/entity/repository/lp_repository.class.php';
        $result['Entity\Repository\LpViewRepository'] = '/main/inc/entity/repository/lp_view_repository.class.php';
        $result['Entity\Repository\MessageAttachmentRepository'] = '/main/inc/entity/repository/message_attachment_repository.class.php';
        $result['Entity\Repository\MessageRepository'] = '/main/inc/entity/repository/message_repository.class.php';
        $result['Entity\Repository\MetadataRepository'] = '/main/inc/entity/repository/metadata_repository.class.php';
        $result['Entity\Repository\NotebookRepository'] = '/main/inc/entity/repository/notebook_repository.class.php';
        $result['Entity\Repository\NotificationRepository'] = '/main/inc/entity/repository/notification_repository.class.php';
        $result['Entity\Repository\OnlineConnectedRepository'] = '/main/inc/entity/repository/online_connected_repository.class.php';
        $result['Entity\Repository\OnlineLinkRepository'] = '/main/inc/entity/repository/online_link_repository.class.php';
        $result['Entity\Repository\OpenidAssociationRepository'] = '/main/inc/entity/repository/openid_association_repository.class.php';
        $result['Entity\Repository\PermissionGroupRepository'] = '/main/inc/entity/repository/permission_group_repository.class.php';
        $result['Entity\Repository\PermissionTaskRepository'] = '/main/inc/entity/repository/permission_task_repository.class.php';
        $result['Entity\Repository\PermissionUserRepository'] = '/main/inc/entity/repository/permission_user_repository.class.php';
        $result['Entity\Repository\PersonalAgendaRepeatNotRepository'] = '/main/inc/entity/repository/personal_agenda_repeat_not_repository.class.php';
        $result['Entity\Repository\PersonalAgendaRepeatRepository'] = '/main/inc/entity/repository/personal_agenda_repeat_repository.class.php';
        $result['Entity\Repository\PersonalAgendaRepository'] = '/main/inc/entity/repository/personal_agenda_repository.class.php';
        $result['Entity\Repository\PhpSessionRepository'] = '/main/inc/entity/repository/php_session_repository.class.php';
        $result['Entity\Repository\PromotionRepository'] = '/main/inc/entity/repository/promotion_repository.class.php';
        $result['Entity\Repository\QuizAnswerRepository'] = '/main/inc/entity/repository/quiz_answer_repository.class.php';
        $result['Entity\Repository\QuizQuestionCategoryRepository'] = '/main/inc/entity/repository/quiz_question_category_repository.class.php';
        $result['Entity\Repository\QuizQuestionOptionRepository'] = '/main/inc/entity/repository/quiz_question_option_repository.class.php';
        $result['Entity\Repository\QuizQuestionRelCategoryRepository'] = '/main/inc/entity/repository/quiz_question_rel_category_repository.class.php';
        $result['Entity\Repository\QuizQuestionRepository'] = '/main/inc/entity/repository/quiz_question_repository.class.php';
        $result['Entity\Repository\QuizRelQuestionRepository'] = '/main/inc/entity/repository/quiz_rel_question_repository.class.php';
        $result['Entity\Repository\QuizRepository'] = '/main/inc/entity/repository/quiz_repository.class.php';
        $result['Entity\Repository\ReservationCategoryRepository'] = '/main/inc/entity/repository/reservation_category_repository.class.php';
        $result['Entity\Repository\ReservationCategoryRightsRepository'] = '/main/inc/entity/repository/reservation_category_rights_repository.class.php';
        $result['Entity\Repository\ReservationItemRepository'] = '/main/inc/entity/repository/reservation_item_repository.class.php';
        $result['Entity\Repository\ReservationItemRightsRepository'] = '/main/inc/entity/repository/reservation_item_rights_repository.class.php';
        $result['Entity\Repository\ReservationMainRepository'] = '/main/inc/entity/repository/reservation_main_repository.class.php';
        $result['Entity\Repository\ReservationSubscriptionRepository'] = '/main/inc/entity/repository/reservation_subscription_repository.class.php';
        $result['Entity\Repository\ResourceRepository'] = '/main/inc/entity/repository/resource_repository.class.php';
        $result['Entity\Repository\RoleGroupRepository'] = '/main/inc/entity/repository/role_group_repository.class.php';
        $result['Entity\Repository\RolePermissionsRepository'] = '/main/inc/entity/repository/role_permissions_repository.class.php';
        $result['Entity\Repository\RoleRepository'] = '/main/inc/entity/repository/role_repository.class.php';
        $result['Entity\Repository\RoleUserRepository'] = '/main/inc/entity/repository/role_user_repository.class.php';
        $result['Entity\Repository\SearchEngineRefRepository'] = '/main/inc/entity/repository/search_engine_ref_repository.class.php';
        $result['Entity\Repository\SessionCategoryRepository'] = '/main/inc/entity/repository/session_category_repository.class.php';
        $result['Entity\Repository\SessionFieldRepository'] = '/main/inc/entity/repository/session_field_repository.class.php';
        $result['Entity\Repository\SessionFieldValuesRepository'] = '/main/inc/entity/repository/session_field_values_repository.class.php';
        $result['Entity\Repository\SessionRelCourseRelUserRepository'] = '/main/inc/entity/repository/session_rel_course_rel_user_repository.class.php';
        $result['Entity\Repository\SessionRelCourseRepository'] = '/main/inc/entity/repository/session_rel_course_repository.class.php';
        $result['Entity\Repository\SessionRelUserRepository'] = '/main/inc/entity/repository/session_rel_user_repository.class.php';
        $result['Entity\Repository\SessionRepository'] = '/main/inc/entity/repository/session_repository.class.php';
        $result['Entity\Repository\SettingsCurrentRepository'] = '/main/inc/entity/repository/settings_current_repository.class.php';
        $result['Entity\Repository\SettingsOptionsRepository'] = '/main/inc/entity/repository/settings_options_repository.class.php';
        $result['Entity\Repository\SharedSurveyQuestionOptionRepository'] = '/main/inc/entity/repository/shared_survey_question_option_repository.class.php';
        $result['Entity\Repository\SharedSurveyQuestionRepository'] = '/main/inc/entity/repository/shared_survey_question_repository.class.php';
        $result['Entity\Repository\SharedSurveyRepository'] = '/main/inc/entity/repository/shared_survey_repository.class.php';
        $result['Entity\Repository\SkillProfileRepository'] = '/main/inc/entity/repository/skill_profile_repository.class.php';
        $result['Entity\Repository\SkillRelGradebookRepository'] = '/main/inc/entity/repository/skill_rel_gradebook_repository.class.php';
        $result['Entity\Repository\SkillRelProfileRepository'] = '/main/inc/entity/repository/skill_rel_profile_repository.class.php';
        $result['Entity\Repository\SkillRelSkillRepository'] = '/main/inc/entity/repository/skill_rel_skill_repository.class.php';
        $result['Entity\Repository\SkillRelUserRepository'] = '/main/inc/entity/repository/skill_rel_user_repository.class.php';
        $result['Entity\Repository\SkillRepository'] = '/main/inc/entity/repository/skill_repository.class.php';
        $result['Entity\Repository\SpecificFieldRepository'] = '/main/inc/entity/repository/specific_field_repository.class.php';
        $result['Entity\Repository\SpecificFieldValuesRepository'] = '/main/inc/entity/repository/specific_field_values_repository.class.php';
        $result['Entity\Repository\StudentPublicationAssignmentRepository'] = '/main/inc/entity/repository/student_publication_assignment_repository.class.php';
        $result['Entity\Repository\StudentPublicationRepository'] = '/main/inc/entity/repository/student_publication_repository.class.php';
        $result['Entity\Repository\SurveyAnswerRepository'] = '/main/inc/entity/repository/survey_answer_repository.class.php';
        $result['Entity\Repository\SurveyGroupRepository'] = '/main/inc/entity/repository/survey_group_repository.class.php';
        $result['Entity\Repository\SurveyInvitationRepository'] = '/main/inc/entity/repository/survey_invitation_repository.class.php';
        $result['Entity\Repository\SurveyQuestionOptionRepository'] = '/main/inc/entity/repository/survey_question_option_repository.class.php';
        $result['Entity\Repository\SurveyQuestionRepository'] = '/main/inc/entity/repository/survey_question_repository.class.php';
        $result['Entity\Repository\SurveyRepository'] = '/main/inc/entity/repository/survey_repository.class.php';
        $result['Entity\Repository\SysAnnouncementRepository'] = '/main/inc/entity/repository/sys_announcement_repository.class.php';
        $result['Entity\Repository\SysCalendarRepository'] = '/main/inc/entity/repository/sys_calendar_repository.class.php';
        $result['Entity\Repository\SystemTemplateRepository'] = '/main/inc/entity/repository/system_template_repository.class.php';
        $result['Entity\Repository\TagRepository'] = '/main/inc/entity/repository/tag_repository.class.php';
        $result['Entity\Repository\TemplatesRepository'] = '/main/inc/entity/repository/templates_repository.class.php';
        $result['Entity\Repository\ThematicAdvanceRepository'] = '/main/inc/entity/repository/thematic_advance_repository.class.php';
        $result['Entity\Repository\ThematicPlanRepository'] = '/main/inc/entity/repository/thematic_plan_repository.class.php';
        $result['Entity\Repository\ThematicRepository'] = '/main/inc/entity/repository/thematic_repository.class.php';
        $result['Entity\Repository\ToolIntroRepository'] = '/main/inc/entity/repository/tool_intro_repository.class.php';
        $result['Entity\Repository\ToolRepository'] = '/main/inc/entity/repository/tool_repository.class.php';
        $result['Entity\Repository\TrackCBrowsersRepository'] = '/main/inc/entity/repository/track_c_browsers_repository.class.php';
        $result['Entity\Repository\TrackCCountriesRepository'] = '/main/inc/entity/repository/track_c_countries_repository.class.php';
        $result['Entity\Repository\TrackCOsRepository'] = '/main/inc/entity/repository/track_c_os_repository.class.php';
        $result['Entity\Repository\TrackCProvidersRepository'] = '/main/inc/entity/repository/track_c_providers_repository.class.php';
        $result['Entity\Repository\TrackCReferersRepository'] = '/main/inc/entity/repository/track_c_referers_repository.class.php';
        $result['Entity\Repository\TrackCourseRankingRepository'] = '/main/inc/entity/repository/track_course_ranking_repository.class.php';
        $result['Entity\Repository\TrackEAccessRepository'] = '/main/inc/entity/repository/track_e_access_repository.class.php';
        $result['Entity\Repository\TrackEAttemptCoeffRepository'] = '/main/inc/entity/repository/track_e_attempt_coeff_repository.class.php';
        $result['Entity\Repository\TrackEAttemptRecordingRepository'] = '/main/inc/entity/repository/track_e_attempt_recording_repository.class.php';
        $result['Entity\Repository\TrackEAttemptRepository'] = '/main/inc/entity/repository/track_e_attempt_repository.class.php';
        $result['Entity\Repository\TrackECourseAccessRepository'] = '/main/inc/entity/repository/track_e_course_access_repository.class.php';
        $result['Entity\Repository\TrackEDefaultRepository'] = '/main/inc/entity/repository/track_e_default_repository.class.php';
        $result['Entity\Repository\TrackEDownloadsRepository'] = '/main/inc/entity/repository/track_e_downloads_repository.class.php';
        $result['Entity\Repository\TrackEExercicesRepository'] = '/main/inc/entity/repository/track_e_exercices_repository.class.php';
        $result['Entity\Repository\TrackEHotpotatoesRepository'] = '/main/inc/entity/repository/track_e_hotpotatoes_repository.class.php';
        $result['Entity\Repository\TrackEHotspotRepository'] = '/main/inc/entity/repository/track_e_hotspot_repository.class.php';
        $result['Entity\Repository\TrackEItemPropertyRepository'] = '/main/inc/entity/repository/track_e_item_property_repository.class.php';
        $result['Entity\Repository\TrackELastaccessRepository'] = '/main/inc/entity/repository/track_e_lastaccess_repository.class.php';
        $result['Entity\Repository\TrackELinksRepository'] = '/main/inc/entity/repository/track_e_links_repository.class.php';
        $result['Entity\Repository\TrackELoginRepository'] = '/main/inc/entity/repository/track_e_login_repository.class.php';
        $result['Entity\Repository\TrackEOnlineRepository'] = '/main/inc/entity/repository/track_e_online_repository.class.php';
        $result['Entity\Repository\TrackEOpenRepository'] = '/main/inc/entity/repository/track_e_open_repository.class.php';
        $result['Entity\Repository\TrackEUploadsRepository'] = '/main/inc/entity/repository/track_e_uploads_repository.class.php';
        $result['Entity\Repository\TrackStoredValuesRepository'] = '/main/inc/entity/repository/track_stored_values_repository.class.php';
        $result['Entity\Repository\TrackStoredValuesStackRepository'] = '/main/inc/entity/repository/track_stored_values_stack_repository.class.php';
        $result['Entity\Repository\UserApiKeyRepository'] = '/main/inc/entity/repository/user_api_key_repository.class.php';
        $result['Entity\Repository\UserCourseCategoryRepository'] = '/main/inc/entity/repository/user_course_category_repository.class.php';
        $result['Entity\Repository\UserFieldOptionsRepository'] = '/main/inc/entity/repository/user_field_options_repository.class.php';
        $result['Entity\Repository\UserFieldRepository'] = '/main/inc/entity/repository/user_field_repository.class.php';
        $result['Entity\Repository\UserFieldValuesRepository'] = '/main/inc/entity/repository/user_field_values_repository.class.php';
        $result['Entity\Repository\UserFriendRelationTypeRepository'] = '/main/inc/entity/repository/user_friend_relation_type_repository.class.php';
        $result['Entity\Repository\UserRelCourseVoteRepository'] = '/main/inc/entity/repository/user_rel_course_vote_repository.class.php';
        $result['Entity\Repository\UserRelEventTypeRepository'] = '/main/inc/entity/repository/user_rel_event_type_repository.class.php';
        $result['Entity\Repository\UserRelTagRepository'] = '/main/inc/entity/repository/user_rel_tag_repository.class.php';
        $result['Entity\Repository\UserRelUserRepository'] = '/main/inc/entity/repository/user_rel_user_repository.class.php';
        $result['Entity\Repository\UserRepository'] = '/main/inc/entity/repository/user_repository.class.php';
        $result['Entity\Repository\UsergroupRelCourseRepository'] = '/main/inc/entity/repository/usergroup_rel_course_repository.class.php';
        $result['Entity\Repository\UsergroupRelQuestionRepository'] = '/main/inc/entity/repository/usergroup_rel_question_repository.class.php';
        $result['Entity\Repository\UsergroupRelSessionRepository'] = '/main/inc/entity/repository/usergroup_rel_session_repository.class.php';
        $result['Entity\Repository\UsergroupRelUserRepository'] = '/main/inc/entity/repository/usergroup_rel_user_repository.class.php';
        $result['Entity\Repository\UsergroupRepository'] = '/main/inc/entity/repository/usergroup_repository.class.php';
        $result['Entity\Repository\UserinfoContentRepository'] = '/main/inc/entity/repository/userinfo_content_repository.class.php';
        $result['Entity\Repository\UserinfoDefRepository'] = '/main/inc/entity/repository/userinfo_def_repository.class.php';
        $result['Entity\Repository\WikiConfRepository'] = '/main/inc/entity/repository/wiki_conf_repository.class.php';
        $result['Entity\Repository\WikiDiscussRepository'] = '/main/inc/entity/repository/wiki_discuss_repository.class.php';
        $result['Entity\Repository\WikiMailcueRepository'] = '/main/inc/entity/repository/wiki_mailcue_repository.class.php';
        $result['Entity\Repository\WikiRepository'] = '/main/inc/entity/repository/wiki_repository.class.php';
        $result['Entity\ReservationCategory'] = '/main/inc/entity/reservation_category.class.php';
        $result['Entity\ReservationCategoryRights'] = '/main/inc/entity/reservation_category_rights.class.php';
        $result['Entity\ReservationItem'] = '/main/inc/entity/reservation_item.class.php';
        $result['Entity\ReservationItemRights'] = '/main/inc/entity/reservation_item_rights.class.php';
        $result['Entity\ReservationMain'] = '/main/inc/entity/reservation_main.class.php';
        $result['Entity\ReservationSubscription'] = '/main/inc/entity/reservation_subscription.class.php';
        $result['Entity\Resource'] = '/main/inc/entity/resource.class.php';
        $result['Entity\Role'] = '/main/inc/entity/role.class.php';
        $result['Entity\RoleGroup'] = '/main/inc/entity/role_group.class.php';
        $result['Entity\RolePermissions'] = '/main/inc/entity/role_permissions.class.php';
        $result['Entity\RoleUser'] = '/main/inc/entity/role_user.class.php';
        $result['Entity\SearchEngineRef'] = '/main/inc/entity/search_engine_ref.class.php';
        $result['Entity\Session'] = '/main/inc/entity/session.class.php';
        $result['Entity\SessionCategory'] = '/main/inc/entity/session_category.class.php';
        $result['Entity\SessionField'] = '/main/inc/entity/session_field.class.php';
        $result['Entity\SessionFieldValues'] = '/main/inc/entity/session_field_values.class.php';
        $result['Entity\SessionRelCourse'] = '/main/inc/entity/session_rel_course.class.php';
        $result['Entity\SessionRelCourseRelUser'] = '/main/inc/entity/session_rel_course_rel_user.class.php';
        $result['Entity\SessionRelUser'] = '/main/inc/entity/session_rel_user.class.php';
        $result['Entity\SettingsCurrent'] = '/main/inc/entity/settings_current.class.php';
        $result['Entity\SettingsOptions'] = '/main/inc/entity/settings_options.class.php';
        $result['Entity\SharedSurvey'] = '/main/inc/entity/shared_survey.class.php';
        $result['Entity\SharedSurveyQuestion'] = '/main/inc/entity/shared_survey_question.class.php';
        $result['Entity\SharedSurveyQuestionOption'] = '/main/inc/entity/shared_survey_question_option.class.php';
        $result['Entity\Skill'] = '/main/inc/entity/skill.class.php';
        $result['Entity\SkillProfile'] = '/main/inc/entity/skill_profile.class.php';
        $result['Entity\SkillRelGradebook'] = '/main/inc/entity/skill_rel_gradebook.class.php';
        $result['Entity\SkillRelProfile'] = '/main/inc/entity/skill_rel_profile.class.php';
        $result['Entity\SkillRelSkill'] = '/main/inc/entity/skill_rel_skill.class.php';
        $result['Entity\SkillRelUser'] = '/main/inc/entity/skill_rel_user.class.php';
        $result['Entity\SpecificField'] = '/main/inc/entity/specific_field.class.php';
        $result['Entity\SpecificFieldValues'] = '/main/inc/entity/specific_field_values.class.php';
        $result['Entity\StudentPublication'] = '/main/inc/entity/student_publication.class.php';
        $result['Entity\StudentPublicationAssignment'] = '/main/inc/entity/student_publication_assignment.class.php';
        $result['Entity\Survey'] = '/main/inc/entity/survey.class.php';
        $result['Entity\SurveyAnswer'] = '/main/inc/entity/survey_answer.class.php';
        $result['Entity\SurveyGroup'] = '/main/inc/entity/survey_group.class.php';
        $result['Entity\SurveyInvitation'] = '/main/inc/entity/survey_invitation.class.php';
        $result['Entity\SurveyQuestion'] = '/main/inc/entity/survey_question.class.php';
        $result['Entity\SurveyQuestionOption'] = '/main/inc/entity/survey_question_option.class.php';
        $result['Entity\SysAnnouncement'] = '/main/inc/entity/sys_announcement.class.php';
        $result['Entity\SysCalendar'] = '/main/inc/entity/sys_calendar.class.php';
        $result['Entity\SystemTemplate'] = '/main/inc/entity/system_template.class.php';
        $result['Entity\Tag'] = '/main/inc/entity/tag.class.php';
        $result['Entity\Templates'] = '/main/inc/entity/templates.class.php';
        $result['Entity\Thematic'] = '/main/inc/entity/thematic.class.php';
        $result['Entity\ThematicAdvance'] = '/main/inc/entity/thematic_advance.class.php';
        $result['Entity\ThematicPlan'] = '/main/inc/entity/thematic_plan.class.php';
        $result['Entity\Tool'] = '/main/inc/entity/tool.class.php';
        $result['Entity\ToolIntro'] = '/main/inc/entity/tool_intro.class.php';
        $result['Entity\TrackCBrowsers'] = '/main/inc/entity/track_c_browsers.class.php';
        $result['Entity\TrackCCountries'] = '/main/inc/entity/track_c_countries.class.php';
        $result['Entity\TrackCOs'] = '/main/inc/entity/track_c_os.class.php';
        $result['Entity\TrackCProviders'] = '/main/inc/entity/track_c_providers.class.php';
        $result['Entity\TrackCReferers'] = '/main/inc/entity/track_c_referers.class.php';
        $result['Entity\TrackCourseRanking'] = '/main/inc/entity/track_course_ranking.class.php';
        $result['Entity\TrackEAccess'] = '/main/inc/entity/track_e_access.class.php';
        $result['Entity\TrackEAttempt'] = '/main/inc/entity/track_e_attempt.class.php';
        $result['Entity\TrackEAttemptCoeff'] = '/main/inc/entity/track_e_attempt_coeff.class.php';
        $result['Entity\TrackEAttemptRecording'] = '/main/inc/entity/track_e_attempt_recording.class.php';
        $result['Entity\TrackECourseAccess'] = '/main/inc/entity/track_e_course_access.class.php';
        $result['Entity\TrackEDefault'] = '/main/inc/entity/track_e_default.class.php';
        $result['Entity\TrackEDownloads'] = '/main/inc/entity/track_e_downloads.class.php';
        $result['Entity\TrackEExercices'] = '/main/inc/entity/track_e_exercices.class.php';
        $result['Entity\TrackEHotpotatoes'] = '/main/inc/entity/track_e_hotpotatoes.class.php';
        $result['Entity\TrackEHotspot'] = '/main/inc/entity/track_e_hotspot.class.php';
        $result['Entity\TrackEItemProperty'] = '/main/inc/entity/track_e_item_property.class.php';
        $result['Entity\TrackELastaccess'] = '/main/inc/entity/track_e_lastaccess.class.php';
        $result['Entity\TrackELinks'] = '/main/inc/entity/track_e_links.class.php';
        $result['Entity\TrackELogin'] = '/main/inc/entity/track_e_login.class.php';
        $result['Entity\TrackEOnline'] = '/main/inc/entity/track_e_online.class.php';
        $result['Entity\TrackEOpen'] = '/main/inc/entity/track_e_open.class.php';
        $result['Entity\TrackEUploads'] = '/main/inc/entity/track_e_uploads.class.php';
        $result['Entity\TrackStoredValues'] = '/main/inc/entity/track_stored_values.class.php';
        $result['Entity\TrackStoredValuesStack'] = '/main/inc/entity/track_stored_values_stack.class.php';
        $result['Entity\User'] = '/main/inc/entity/user.class.php';
        $result['Entity\UserApiKey'] = '/main/inc/entity/user_api_key.class.php';
        $result['Entity\UserCourseCategory'] = '/main/inc/entity/user_course_category.class.php';
        $result['Entity\UserField'] = '/main/inc/entity/user_field.class.php';
        $result['Entity\UserFieldOptions'] = '/main/inc/entity/user_field_options.class.php';
        $result['Entity\UserFieldValues'] = '/main/inc/entity/user_field_values.class.php';
        $result['Entity\UserFriendRelationType'] = '/main/inc/entity/user_friend_relation_type.class.php';
        $result['Entity\UserRelCourseVote'] = '/main/inc/entity/user_rel_course_vote.class.php';
        $result['Entity\UserRelEventType'] = '/main/inc/entity/user_rel_event_type.class.php';
        $result['Entity\UserRelTag'] = '/main/inc/entity/user_rel_tag.class.php';
        $result['Entity\UserRelUser'] = '/main/inc/entity/user_rel_user.class.php';
        $result['Entity\Usergroup'] = '/main/inc/entity/usergroup.class.php';
        $result['Entity\UsergroupRelCourse'] = '/main/inc/entity/usergroup_rel_course.class.php';
        $result['Entity\UsergroupRelQuestion'] = '/main/inc/entity/usergroup_rel_question.class.php';
        $result['Entity\UsergroupRelSession'] = '/main/inc/entity/usergroup_rel_session.class.php';
        $result['Entity\UsergroupRelUser'] = '/main/inc/entity/usergroup_rel_user.class.php';
        $result['Entity\UserinfoContent'] = '/main/inc/entity/userinfo_content.class.php';
        $result['Entity\UserinfoDef'] = '/main/inc/entity/userinfo_def.class.php';
        $result['Entity\Wiki'] = '/main/inc/entity/wiki.class.php';
        $result['Entity\WikiConf'] = '/main/inc/entity/wiki_conf.class.php';
        $result['Entity\WikiDiscuss'] = '/main/inc/entity/wiki_discuss.class.php';
        $result['Entity\WikiMailcue'] = '/main/inc/entity/wiki_mailcue.class.php';
        $result['EvalForm'] = '/main/gradebook/lib/fe/evalform.class.php';
        $result['EvalLink'] = '/main/gradebook/lib/be/evallink.class.php';
        $result['Evaluation'] = '/main/gradebook/lib/be/evaluation.class.php';
        $result['Event'] = '/main/coursecopy/classes/Event.class.php';
        $result['EventEmailTemplate'] = '/main/inc/lib/event_email_template.class.php';
        $result['EventsDispatcher'] = '/main/inc/lib/events_dispatcher.class.php';
        $result['EventsMail'] = '/main/inc/lib/events_email.class.php';
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
        $result['Html_Quickform_Rule_Date'] = '/main/inc/lib/pear/HTML/QuickForm/Rule/Date.php';
        $result['HttpResource'] = '/main/inc/lib/external_media/renderer/http_resource.class.php';
        $result['Image'] = '/main/inc/lib/image.lib.php';
        $result['ImageWrapper'] = '/main/inc/lib/image.lib.php';
        $result['ImagickWrapper'] = '/main/inc/lib/image.lib.php';
        $result['Import'] = '/main/inc/lib/import.lib.php';
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
        $result['Model'] = '/main/inc/lib/model.lib.php';
        $result['Model\Course'] = '/main/inc/lib/course.class.php';
        $result['Model\Document'] = '/main/inc/lib/document.class.php';
        $result['Model\ItemProperty'] = '/main/inc/lib/item_property.class.php';
        $result['Model\ItemPropertyRepository'] = '/main/inc/lib/item_property.class.php';
        $result['Model\StudentPublication'] = '/main/inc/lib/student_publication.class.php';
        $result['Modelll\Forum'] = '/main/inc/lib/forum.class.php';
        $result['Modelll\ForumAttachment'] = '/main/inc/lib/forum_attachment.class.php';
        $result['Modelll\ForumAttachmentRepository'] = '/main/inc/lib/forum_attachment.class.php';
        $result['Modelll\ForumCategory'] = '/main/inc/lib/forum_category.class.php';
        $result['Modelll\ForumCategoryRepository'] = '/main/inc/lib/forum_category.class.php';
        $result['Modelll\ForumMailcue'] = '/main/inc/lib/forum_mailcue.class.php';
        $result['Modelll\ForumMailcueRepository'] = '/main/inc/lib/forum_mailcue.class.php';
        $result['Modelll\ForumNotification'] = '/main/inc/lib/forum_notification.class.php';
        $result['Modelll\ForumNotificationRepository'] = '/main/inc/lib/forum_notification.class.php';
        $result['Modelll\ForumPost'] = '/main/inc/lib/forum_post.class.php';
        $result['Modelll\ForumPostRepository'] = '/main/inc/lib/forum_post.class.php';
        $result['Modelll\ForumRepository'] = '/main/inc/lib/forum.class.php';
        $result['Modelll\ForumThread'] = '/main/inc/lib/forum_thread.class.php';
        $result['Modelll\ForumThreadQualify'] = '/main/inc/lib/forum_thread_qualify.class.php';
        $result['Modelll\ForumThreadQualifyLog'] = '/main/inc/lib/forum_thread_qualify_log.class.php';
        $result['Modelll\ForumThreadRepository'] = '/main/inc/lib/forum_thread.class.php';
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
        $result['Response'] = '/main/inc/lib/response.class.php';
        $result['Result'] = '/main/gradebook/lib/be/result.class.php';
        $result['ResultSet'] = '/main/inc/lib/result_set.class.php';
        $result['ResultTable'] = '/main/gradebook/lib/fe/resulttable.class.php';
        $result['ResultsDataGenerator'] = '/main/gradebook/lib/results_data_generator.class.php';
        $result['Rights'] = '/main/inc/lib/rights.lib.php';
        $result['ScoreDisplay'] = '/main/gradebook/lib/scoredisplay.class.php';
        $result['ScoreDisplayForm'] = '/main/gradebook/lib/fe/scoredisplayform.class.php';
        $result['ScormDocument'] = '/main/coursecopy/classes/ScormDocument.class.php';
        $result['Security'] = '/main/inc/lib/security.lib.php';
        $result['SessionHandler'] = '/main/inc/lib/session_handler.class.php';
        $result['SessionManager'] = '/main/inc/lib/sessionmanager.lib.php';
        $result['Shibboleth\Admin'] = '/main/auth/shibboleth/app/model/admin.class.php';
        $result['Shibboleth\AdminStore'] = '/main/auth/shibboleth/app/model/admin.class.php';
        $result['Shibboleth\Model'] = '/main/auth/shibboleth/lib/model.class.php';
        $result['Shibboleth\Scaffolder'] = '/main/auth/shibboleth/lib/scaffolder/scaffolder.class.php';
        $result['Shibboleth\Shibboleth'] = '/main/auth/shibboleth/app/shibboleth.class.php';
        $result['Shibboleth\ShibbolethConfig'] = '/main/auth/shibboleth/lib/shibboleth_config.class.php';
        $result['Shibboleth\ShibbolethController'] = '/main/auth/shibboleth/app/controller/shibboleth_controller.class.php';
        $result['Shibboleth\ShibbolethDisplay'] = '/main/auth/shibboleth/app/view/shibboleth_display.class.php';
        $result['Shibboleth\ShibbolethEmailForm'] = '/main/auth/shibboleth/app/view/shibboleth_email_form.class.php';
        $result['Shibboleth\ShibbolethSession'] = '/main/auth/shibboleth/lib/shibboleth_session.class.php';
        $result['Shibboleth\ShibbolethStatusRequestForm'] = '/main/auth/shibboleth/app/view/shibboleth_status_request_form.class.php';
        $result['Shibboleth\ShibbolethStore'] = '/main/auth/shibboleth/app/model/shibboleth_store.class.php';
        $result['Shibboleth\ShibbolethUpgrade'] = '/main/auth/shibboleth/db/shibboleth_upgrade.class.php';
        $result['Shibboleth\ShibbolethUser'] = '/main/auth/shibboleth/app/model/shibboleth_user.class.php';
        $result['Shibboleth\Store'] = '/main/auth/shibboleth/lib/store.class.php';
        $result['Shibboleth\User'] = '/main/auth/shibboleth/app/model/user.class.php';
        $result['Shibboleth\UserStore'] = '/main/auth/shibboleth/app/model/user.class.php';
        $result['Shibboleth\_Admin'] = '/main/auth/shibboleth/app/model/scaffold/admin.class.php';
        $result['Shibboleth\_AdminStore'] = '/main/auth/shibboleth/app/model/scaffold/admin.class.php';
        $result['Shibboleth\_User'] = '/main/auth/shibboleth/app/model/scaffold/user.class.php';
        $result['Shibboleth\_UserStore'] = '/main/auth/shibboleth/app/model/scaffold/user.class.php';
        $result['Shibboleth\aai'] = '/main/auth/shibboleth/config/aai.class.php';
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
        $result['Tools\EntityGenerator'] = '/main/inc/lib/tools/entity_generator.class.php';
        $result['Tools\EntityRepositoryGenerator'] = '/main/inc/lib/tools/entity_repository_generator.class.php';
        $result['Tools\YamlExporter'] = '/main/inc/lib/tools/yaml_exporter.class.php';
        $result['Tracking'] = '/main/inc/lib/tracking.lib.php';
        $result['TrackingCourseLog'] = '/main/inc/lib/tracking.lib.php';
        $result['TrackingUserLog'] = '/main/inc/lib/tracking.lib.php';
        $result['TrackingUserLogCSV'] = '/main/inc/lib/tracking.lib.php';
        $result['UniqueAnswer'] = '/main/exercice/unique_answer.class.php';
        $result['UniqueAnswerNoOption'] = '/main/exercice/unique_answer_no_option.class.php';
        $result['Uri'] = '/main/inc/lib/uri.class.php';
        $result['UrlManager'] = '/main/inc/lib/urlmanager.lib.php';
        $result['UserApiKeyManager'] = '/main/inc/lib/user_api_key_manager.class.php';
        $result['UserDataGenerator'] = '/main/gradebook/lib/user_data_generator.class.php';
        $result['UserForm'] = '/main/gradebook/lib/fe/userform.class.php';
        $result['UserGroup'] = '/main/inc/lib/usergroup.lib.php';
        $result['UserManager'] = '/main/inc/lib/usermanager.lib.php';
        $result['UserTable'] = '/main/gradebook/lib/fe/usertable.class.php';
        $result['Utf8'] = '/main/inc/lib/system/text/utf8.class.php';
        $result['Utf8Decoder'] = '/main/inc/lib/system/text/utf8_decoder.class.php';
        $result['Utf8Encoder'] = '/main/inc/lib/system/text/utf8_encoder.class.php';
        $result['Wiki'] = '/main/coursecopy/classes/wiki.class.php';
        $result['XapianIndexer'] = '/main/inc/lib/search/xapian/XapianIndexer.class.php';
        $result['Zip'] = '/main/inc/lib/zip.class.php';
        $result['ZombieManager'] = '/main/inc/lib/zombie/zombie_manager.class.php';
        $result['ZombieReport'] = '/main/inc/lib/zombie/zombie_report.class.php';
        $result['_IndexableChunk'] = '/main/inc/lib/search/IndexableChunk.class.php';
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
        $result['s) on one element\xmddoc'] = '/main/inc/lib/xmd.lib.php';
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
