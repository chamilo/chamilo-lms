<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20251202103000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Update collation for specific columns in all missing tables to utf8mb4';
    }

    public function up(Schema $schema): void
    {

        // Change collation for access_url table
        $this->addSql('ALTER TABLE access_url CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE access_url CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE access_url CHANGE email email varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE access_url CHANGE url url varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for agenda_reminder table
        $this->addSql('ALTER TABLE agenda_reminder CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE agenda_reminder CHANGE date_interval date_interval varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for block table
        $this->addSql('ALTER TABLE block CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE block CHANGE controller controller varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE block CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE block CHANGE path path varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE block CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        // Change collation for branch_sync table
        $this->addSql('ALTER TABLE branch_sync CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE branch_sync CHANGE admin_mail admin_mail varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE branch_sync CHANGE admin_name admin_name varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE branch_sync CHANGE admin_phone admin_phone varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE branch_sync CHANGE branch_ip branch_ip varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE branch_sync CHANGE branch_type branch_type varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE branch_sync CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE branch_sync CHANGE last_sync_type last_sync_type varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE branch_sync CHANGE ssl_pub_key ssl_pub_key varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE branch_sync CHANGE title title varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE branch_sync CHANGE unique_id unique_id varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for branch_transaction table
        $this->addSql('ALTER TABLE branch_transaction CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE branch_transaction CHANGE action action varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE branch_transaction CHANGE dest_id dest_id varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE branch_transaction CHANGE external_info external_info varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE branch_transaction CHANGE item_id item_id varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE branch_transaction CHANGE origin origin varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        // Change collation for branch_transaction table
        $this->addSql('ALTER TABLE branch_transaction_status CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE branch_transaction_status CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for career table
        $this->addSql('ALTER TABLE career CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE career CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE career CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for chat table
        $this->addSql('ALTER TABLE chat CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE chat CHANGE message message longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for chat_video table
        $this->addSql('ALTER TABLE chat_video CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE chat_video CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for course table
        $this->addSql('ALTER TABLE course CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE course CHANGE code code varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE course CHANGE course_language course_language varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE course CHANGE department_name department_name varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course CHANGE department_url department_url varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course CHANGE directory directory varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course CHANGE introduction introduction longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course CHANGE legal legal longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course CHANGE registration_code registration_code varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course CHANGE title title varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course CHANGE tutor_name tutor_name varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course CHANGE video_url video_url varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE course CHANGE visual_code visual_code varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        // Change collation for course_category table
        $this->addSql('ALTER TABLE course_category CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE course_category CHANGE auth_cat_child auth_cat_child varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course_category CHANGE auth_course_child auth_course_child varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course_category CHANGE code code varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE course_category CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course_category CHANGE title title longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for course_rel_class table
        $this->addSql('ALTER TABLE course_rel_class CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE course_rel_class CHANGE course_code course_code varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for course_request table
        $this->addSql('ALTER TABLE course_request CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE course_request CHANGE category_code category_code varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course_request CHANGE code code varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE course_request CHANGE course_language course_language varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE course_request CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course_request CHANGE objetives objetives longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course_request CHANGE target_audience target_audience longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course_request CHANGE title title varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE course_request CHANGE tutor_name tutor_name varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course_request CHANGE visual_code visual_code varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        // Change collation for course_type table
        $this->addSql('ALTER TABLE course_type CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE course_type CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course_type CHANGE props props longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course_type CHANGE title title varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE course_type CHANGE translation_var translation_var varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        // Change collation for c_announcement_attachment table
        $this->addSql('ALTER TABLE c_announcement_attachment CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_announcement_attachment CHANGE comment comment longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_announcement_attachment CHANGE filename filename varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_announcement_attachment CHANGE path path varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_attendance table
        $this->addSql('ALTER TABLE c_attendance CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_attendance CHANGE attendance_qualify_title attendance_qualify_title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_attendance CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_attendance CHANGE title title longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_attendance_sheet table
        $this->addSql('ALTER TABLE c_attendance_sheet CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_attendance_sheet CHANGE signature signature longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        // Change collation for c_attendance_sheet_log table
        $this->addSql('ALTER TABLE c_attendance_sheet_log CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_attendance_sheet_log CHANGE lastedit_type lastedit_type varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_blog table
        $this->addSql('ALTER TABLE c_blog CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_blog CHANGE blog_subtitle blog_subtitle varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_blog CHANGE title title longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_blog_attachment table
        $this->addSql('ALTER TABLE c_blog_attachment CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_blog_attachment CHANGE comment comment longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_blog_attachment CHANGE filename filename varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_blog_attachment CHANGE path path varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_blog_comment table
        $this->addSql('ALTER TABLE c_blog_comment CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_blog_comment CHANGE comment comment longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_blog_comment CHANGE title title varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_blog_post table
        $this->addSql('ALTER TABLE c_blog_post CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_blog_post CHANGE full_text full_text longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_blog_post CHANGE title title varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_blog_rating table
        $this->addSql('ALTER TABLE c_blog_rating CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_blog_rating CHANGE rating_type rating_type varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_blog_task table
        $this->addSql('ALTER TABLE c_blog_task CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_blog_task CHANGE color color varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_blog_task CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_blog_task CHANGE title title varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_calendar_event table
        $this->addSql('ALTER TABLE c_calendar_event CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_calendar_event CHANGE color color varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_calendar_event CHANGE comment comment longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_calendar_event CHANGE content content longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_calendar_event CHANGE invitation_type invitation_type varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_calendar_event CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_calendar_event_attachment table
        $this->addSql('ALTER TABLE c_calendar_event_attachment CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_calendar_event_attachment CHANGE comment comment longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_calendar_event_attachment CHANGE filename filename varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_calendar_event_repeat table
        $this->addSql('ALTER TABLE c_calendar_event_repeat CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_calendar_event_repeat CHANGE cal_days cal_days varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_calendar_event_repeat CHANGE cal_type cal_type varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        // Change collation for c_course_description table
        $this->addSql('ALTER TABLE c_course_description CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_course_description CHANGE content content longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_course_description CHANGE title title longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        // Change collation for c_course_setting table
        $this->addSql('ALTER TABLE c_course_setting CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_course_setting CHANGE category category varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_course_setting CHANGE comment comment varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_course_setting CHANGE subkey subkey varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_course_setting CHANGE subkeytext subkeytext varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_course_setting CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_course_setting CHANGE type type varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_course_setting CHANGE value value longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_course_setting CHANGE variable variable varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_document table
        $this->addSql('ALTER TABLE c_document CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_document CHANGE comment comment longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_document CHANGE filetype filetype varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_document CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_dropbox_category table
        $this->addSql('ALTER TABLE c_dropbox_category CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_dropbox_category CHANGE title title longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_dropbox_feedback table
        $this->addSql('ALTER TABLE c_dropbox_feedback CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_dropbox_feedback CHANGE feedback feedback longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_dropbox_file table
        $this->addSql('ALTER TABLE c_dropbox_file CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_dropbox_file CHANGE author author varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_dropbox_file CHANGE description description varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_dropbox_file CHANGE filename filename varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_dropbox_file CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_dropbox_post table
        $this->addSql('ALTER TABLE c_dropbox_post CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_dropbox_post CHANGE feedback feedback longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        // Change collation for c_forum_attachment table
        $this->addSql('ALTER TABLE c_forum_attachment CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_forum_attachment CHANGE comment comment longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_forum_attachment CHANGE filename filename varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_forum_attachment CHANGE path path varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_forum_category table
        $this->addSql('ALTER TABLE c_forum_category CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_forum_category CHANGE cat_comment cat_comment longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_forum_category CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_forum_forum table
        $this->addSql('ALTER TABLE c_forum_forum CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_forum_forum CHANGE approval_direct_post approval_direct_post varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_forum_forum CHANGE default_view default_view varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_forum_forum CHANGE forum_comment forum_comment longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_forum_forum CHANGE forum_group_public_private forum_group_public_private varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_forum_forum CHANGE forum_image forum_image varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_forum_forum CHANGE forum_of_group forum_of_group varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_forum_forum CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_forum_post table
        $this->addSql('ALTER TABLE c_forum_post CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_forum_post CHANGE post_text post_text longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_forum_post CHANGE title title varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_forum_thread table
        $this->addSql('ALTER TABLE c_forum_thread CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_forum_thread CHANGE thread_title_qualify thread_title_qualify varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_forum_thread CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_glossary table
        $this->addSql('ALTER TABLE c_glossary CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_glossary CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_glossary CHANGE title title longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_group_category table
        $this->addSql('ALTER TABLE c_group_category CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_group_category CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci  NULL');
        $this->addSql('ALTER TABLE c_group_category CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_group_info table
        $this->addSql('ALTER TABLE c_group_info CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_group_info CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_group_info CHANGE title title varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_group_rel_user table
        $this->addSql('ALTER TABLE c_group_rel_user CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_group_rel_user CHANGE role role varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_link table
        $this->addSql('ALTER TABLE c_link CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_link CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_link CHANGE target target varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_link CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_link CHANGE url url longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_link_category table
        $this->addSql('ALTER TABLE c_link_category CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_link_category CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_link_category CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_lp table
        $this->addSql('ALTER TABLE c_lp CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_lp CHANGE author author longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp CHANGE content_license content_license longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp CHANGE content_local content_local varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp CHANGE content_maker content_maker longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp CHANGE default_encoding default_encoding varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp CHANGE default_view_mod default_view_mod varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_lp CHANGE js_lib js_lib longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp CHANGE path path longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp CHANGE ref ref longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_lp CHANGE theme theme varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_lp_category table
        $this->addSql('ALTER TABLE c_lp_category CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_lp_category CHANGE title title longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_lp_item table
        $this->addSql('ALTER TABLE c_lp_item CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_lp_item CHANGE audio audio varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_lp_item CHANGE description description varchar(511) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_lp_item CHANGE item_type item_type varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp_item CHANGE launch_data launch_data longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp_item CHANGE max_time_allowed max_time_allowed varchar(13) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_lp_item CHANGE parameters parameters longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_lp_item CHANGE path path longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp_item CHANGE prerequisite prerequisite longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_lp_item CHANGE ref ref longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp_item CHANGE terms terms longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_lp_item CHANGE title title varchar(511) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_lp_item_view table
        $this->addSql('ALTER TABLE c_lp_item_view CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_lp_item_view CHANGE core_exit core_exit varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp_item_view CHANGE lesson_location lesson_location longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_lp_item_view CHANGE max_score max_score varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_lp_item_view CHANGE status status varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp_item_view CHANGE suspend_data suspend_data longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        // Change collation for c_lp_iv_interaction table
        $this->addSql('ALTER TABLE c_lp_iv_interaction CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_lp_iv_interaction CHANGE completion_time completion_time varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp_iv_interaction CHANGE correct_responses correct_responses longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp_iv_interaction CHANGE interaction_id interaction_id varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp_iv_interaction CHANGE interaction_type interaction_type varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp_iv_interaction CHANGE latency latency varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp_iv_interaction CHANGE result result varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp_iv_interaction CHANGE student_response student_response longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_lp_iv_objective table
        $this->addSql('ALTER TABLE c_lp_iv_objective CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_lp_iv_objective CHANGE objective_id objective_id varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp_iv_objective CHANGE status status varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_notebook table
        $this->addSql('ALTER TABLE c_notebook CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_notebook CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_notebook CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_quiz table
        $this->addSql('ALTER TABLE c_quiz CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_quiz CHANGE access_condition access_condition longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz CHANGE notifications notifications varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz CHANGE page_result_configuration page_result_configuration longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_quiz CHANGE sound sound varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz CHANGE text_when_finished text_when_finished longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz CHANGE text_when_finished_failure text_when_finished_failure longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz CHANGE title title longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_quiz_answer table
        $this->addSql('ALTER TABLE c_quiz_answer CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_quiz_answer CHANGE answer answer longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_quiz_answer CHANGE answer_code answer_code varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz_answer CHANGE comment comment longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz_answer CHANGE hotspot_coordinates hotspot_coordinates longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz_answer CHANGE hotspot_type hotspot_type varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
	// Change collation for c_quiz_category table
        $this->addSql('ALTER TABLE c_quiz_category CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_quiz_category CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz_category CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_quiz_question table
        $this->addSql('ALTER TABLE c_quiz_question CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_quiz_question CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz_question CHANGE extra extra varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz_question CHANGE feedback feedback longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz_question CHANGE picture picture varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz_question CHANGE question question longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_quiz_question CHANGE question_code question_code varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        // Change collation for c_quiz_question_category table
        $this->addSql('ALTER TABLE c_quiz_question_category CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_quiz_question_category CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz_question_category CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_quiz_question_option table
        $this->addSql('ALTER TABLE c_quiz_question_option CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_quiz_question_option CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_quiz_rel_question table
        $this->addSql('ALTER TABLE c_quiz_rel_question CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_quiz_rel_question CHANGE destination destination longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        // Change collation for c_student_publication table
        $this->addSql('ALTER TABLE c_student_publication CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_student_publication CHANGE author author varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_student_publication CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_student_publication CHANGE extensions extensions longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_student_publication CHANGE filetype filetype varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_student_publication CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_student_publication_comment table
        $this->addSql('ALTER TABLE c_student_publication_comment CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_student_publication_comment CHANGE comment comment longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_student_publication_comment CHANGE file file varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        // Change collation for c_survey table
        $this->addSql('ALTER TABLE c_survey CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_survey CHANGE access_condition access_condition longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_survey CHANGE anonymous anonymous varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_survey CHANGE code code varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_survey CHANGE form_fields form_fields longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_survey CHANGE intro intro longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_survey CHANGE invite_mail invite_mail longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_survey CHANGE is_shared is_shared varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_survey CHANGE lang lang varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_survey CHANGE mail_subject mail_subject varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_survey CHANGE reminder_mail reminder_mail longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_survey CHANGE subtitle subtitle longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_survey CHANGE surveythanks surveythanks longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_survey CHANGE survey_version survey_version varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_survey CHANGE template template varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_survey CHANGE title title longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_survey_answer table
        $this->addSql('ALTER TABLE c_survey_answer CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_survey_answer CHANGE option_id option_id longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_survey_answer CHANGE user user varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_survey_invitation table
        $this->addSql('ALTER TABLE c_survey_invitation CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_survey_invitation CHANGE invitation_code invitation_code varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_survey_question table
        $this->addSql('ALTER TABLE c_survey_question CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_survey_question CHANGE display display varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_survey_question CHANGE survey_question survey_question longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_survey_question CHANGE survey_question_comment survey_question_comment longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_survey_question CHANGE type type varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_survey_question_option table
        $this->addSql('ALTER TABLE c_survey_question_option CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_survey_question_option CHANGE option_text option_text longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_thematic table
        $this->addSql('ALTER TABLE c_thematic CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_thematic CHANGE content content longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_thematic CHANGE title title longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_thematic_advance table
        $this->addSql('ALTER TABLE c_thematic_advance CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_thematic_advance CHANGE content content longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        // Change collation for c_thematic_plan table
        $this->addSql('ALTER TABLE c_thematic_plan CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_thematic_plan CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_thematic_plan CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_tool table
        $this->addSql('ALTER TABLE c_tool CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_tool CHANGE title title longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_tool_intro table
        $this->addSql('ALTER TABLE c_tool_intro CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_tool_intro CHANGE intro_text intro_text longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_wiki table
        $this->addSql('ALTER TABLE c_wiki CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_wiki CHANGE comment comment longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_wiki CHANGE content content longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_wiki CHANGE linksto linksto longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_wiki CHANGE progress progress longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_wiki CHANGE reflink reflink varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_wiki CHANGE tag tag longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_wiki CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_wiki CHANGE user_ip user_ip varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_wiki_conf table
        $this->addSql('ALTER TABLE c_wiki_conf CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_wiki_conf CHANGE feedback1 feedback1 longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_wiki_conf CHANGE feedback2 feedback2 longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_wiki_conf CHANGE feedback3 feedback3 longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_wiki_conf CHANGE fprogress1 fprogress1 varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_wiki_conf CHANGE fprogress2 fprogress2 varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_wiki_conf CHANGE fprogress3 fprogress3 varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_wiki_conf CHANGE task task longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for c_wiki_discuss table
        $this->addSql('ALTER TABLE c_wiki_discuss CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_wiki_discuss CHANGE comment comment longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_wiki_discuss CHANGE p_score p_score varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        // Change collation for c_wiki_mailcue table
        $this->addSql('ALTER TABLE c_wiki_mailcue CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE c_wiki_mailcue CHANGE type type longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for extra_field table
        $this->addSql('ALTER TABLE extra_field CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE extra_field CHANGE default_value default_value longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE extra_field CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE extra_field CHANGE display_text display_text varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE extra_field CHANGE helper_text helper_text longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE extra_field CHANGE variable variable varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for extra_field_options table
        $this->addSql('ALTER TABLE extra_field_options CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE extra_field_options CHANGE display_text display_text varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE extra_field_options CHANGE option_value option_value longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE extra_field_options CHANGE priority priority varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE extra_field_options CHANGE priority_message priority_message varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        // Change collation for extra_field_saved_search table
        $this->addSql('ALTER TABLE extra_field_saved_search CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE extra_field_saved_search CHANGE value value longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        // Change collation for extra_field_values table
        $this->addSql('ALTER TABLE extra_field_values CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE extra_field_values CHANGE comment comment longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE extra_field_values CHANGE field_value field_value longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        // Change collation for ext_log_entries table
        $this->addSql('ALTER TABLE ext_log_entries CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE ext_log_entries CHANGE action action varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE ext_log_entries CHANGE data data longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE ext_log_entries CHANGE object_class object_class varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE ext_log_entries CHANGE object_id object_id varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE ext_log_entries CHANGE username username varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        // Change collation for fos_group table
        $this->addSql('ALTER TABLE fos_group CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE fos_group CHANGE code code varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE fos_group CHANGE roles roles longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE fos_group CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for gradebook_category table
        $this->addSql('ALTER TABLE gradebook_category CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE gradebook_category CHANGE depends depends longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE gradebook_category CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE gradebook_category CHANGE title title longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for gradebook_certificate table
        $this->addSql('ALTER TABLE gradebook_certificate CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE gradebook_certificate CHANGE path_certificate path_certificate longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        // Change collation for gradebook_evaluation table
        $this->addSql('ALTER TABLE gradebook_evaluation CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE gradebook_evaluation CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE gradebook_evaluation CHANGE title title longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE gradebook_evaluation CHANGE type type varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE gradebook_evaluation CHANGE user_score_list user_score_list longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        // Change collation for gradebook_link table
        $this->addSql('ALTER TABLE gradebook_link CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE gradebook_link CHANGE user_score_list user_score_list longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        // Change collation for gradebook_linkeval_log table
        $this->addSql('ALTER TABLE gradebook_linkeval_log CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE gradebook_linkeval_log CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE gradebook_linkeval_log CHANGE title title longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE gradebook_linkeval_log CHANGE type type varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for gradebook_score_display table
        $this->addSql('ALTER TABLE gradebook_score_display CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE gradebook_score_display CHANGE display display varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for grade_components table
        $this->addSql('ALTER TABLE grade_components CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE grade_components CHANGE acronym acronym varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE grade_components CHANGE percentage percentage varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE grade_components CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for grade_model table
        $this->addSql('ALTER TABLE grade_model CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE grade_model CHANGE default_external_eval_prefix default_external_eval_prefix varchar(140) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE grade_model CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE grade_model CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for language table
        $this->addSql('ALTER TABLE language CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE language CHANGE english_name english_name varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE language CHANGE isocode isocode varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE language CHANGE original_name original_name varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        // Change collation for legal table
        $this->addSql('ALTER TABLE legal CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE legal CHANGE changes changes longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE legal CHANGE content content longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        // Change collation for message_attachment table
        $this->addSql('ALTER TABLE message_attachment CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE message_attachment CHANGE comment comment longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE message_attachment CHANGE filename filename varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE message_attachment CHANGE path path varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for portfolio table
        $this->addSql('ALTER TABLE portfolio CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE portfolio CHANGE content content longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE portfolio CHANGE title title longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for portfolio_category table
        $this->addSql('ALTER TABLE portfolio_category CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE portfolio_category CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE portfolio_category CHANGE title title longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for promotion table
        $this->addSql('ALTER TABLE promotion CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE promotion CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE promotion CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for room table
        $this->addSql('ALTER TABLE room CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE room CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE room CHANGE geolocation geolocation varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE room CHANGE ip ip varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE room CHANGE ip_mask ip_mask varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE room CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for scheduled_announcements table
        $this->addSql('ALTER TABLE scheduled_announcements CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE scheduled_announcements CHANGE message message longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE scheduled_announcements CHANGE subject subject varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for search_engine_ref table
        $this->addSql('ALTER TABLE search_engine_ref CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE search_engine_ref CHANGE tool_id tool_id varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for sequence table
        $this->addSql('ALTER TABLE sequence CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE sequence CHANGE graph graph longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE sequence CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for sequence_condition table
        $this->addSql('ALTER TABLE sequence_condition CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE sequence_condition CHANGE act_false act_false varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE sequence_condition CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE sequence_condition CHANGE mat_op mat_op varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for sequence_method table
        $this->addSql('ALTER TABLE sequence_method CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE sequence_method CHANGE act_false act_false varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE sequence_method CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE sequence_method CHANGE formula formula longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE sequence_method CHANGE met_type met_type varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for sequence_row_entity table
        $this->addSql('ALTER TABLE sequence_row_entity CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE sequence_row_entity CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for sequence_rule table
        $this->addSql('ALTER TABLE sequence_rule CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE sequence_rule CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for sequence_type_entity table
        $this->addSql('ALTER TABLE sequence_type_entity CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE sequence_type_entity CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE sequence_type_entity CHANGE ent_table ent_table varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE sequence_type_entity CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for sequence_variable table
        $this->addSql('ALTER TABLE sequence_variable CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE sequence_variable CHANGE default_val default_val varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE sequence_variable CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE sequence_variable CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        // Change collation for session table
        $this->addSql('ALTER TABLE session CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE session CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE session CHANGE title title varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for session_category table
        $this->addSql('ALTER TABLE session_category CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE session_category CHANGE title title varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for settings table
        $this->addSql('ALTER TABLE settings CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE settings CHANGE category category varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE settings CHANGE comment comment longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE settings CHANGE scope scope varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE settings CHANGE selected_value selected_value longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE settings CHANGE subkey subkey varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE settings CHANGE subkeytext subkeytext varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE settings CHANGE title title longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE settings CHANGE type type varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE settings CHANGE variable variable varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for settings_options table
        $this->addSql('ALTER TABLE settings_options CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE settings_options CHANGE display_text display_text varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE settings_options CHANGE value value varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE settings_options CHANGE variable variable varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for skill table
        $this->addSql('ALTER TABLE skill CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE skill CHANGE criteria criteria longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE skill CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE skill CHANGE icon icon varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE skill CHANGE short_code short_code varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE skill CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for skill_level table
        $this->addSql('ALTER TABLE skill_level CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE skill_level CHANGE short_title short_title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE skill_level CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for skill_level_profile table
        $this->addSql('ALTER TABLE skill_level_profile CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE skill_level_profile CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for skill_profile table
        $this->addSql('ALTER TABLE skill_profile CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE skill_profile CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE skill_profile CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for skill_rel_gradebook table
        $this->addSql('ALTER TABLE skill_rel_gradebook CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE skill_rel_gradebook CHANGE type type varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for skill_rel_item table
        $this->addSql('ALTER TABLE skill_rel_item CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE skill_rel_item CHANGE obtain_conditions obtain_conditions varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        // Change collation for skill_rel_user table
        $this->addSql('ALTER TABLE skill_rel_user CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE skill_rel_user CHANGE argumentation argumentation longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for skill_rel_user_comment table
        $this->addSql('ALTER TABLE skill_rel_user_comment CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE skill_rel_user_comment CHANGE feedback_text feedback_text longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for specific_field table
        $this->addSql('ALTER TABLE specific_field CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE specific_field CHANGE code code varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE specific_field CHANGE title title varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for specific_field_values table
        $this->addSql('ALTER TABLE specific_field_values CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE specific_field_values CHANGE course_code course_code varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE specific_field_values CHANGE tool_id tool_id varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE specific_field_values CHANGE value value varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for system_template table
        $this->addSql('ALTER TABLE system_template CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE system_template CHANGE comment comment longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE system_template CHANGE content content longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE system_template CHANGE language language varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE system_template CHANGE title title varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for sys_announcement table
        $this->addSql('ALTER TABLE sys_announcement CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE sys_announcement CHANGE content content longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE sys_announcement CHANGE lang lang varchar(70) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE sys_announcement CHANGE roles roles longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE sys_announcement CHANGE title title varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for tag table
        $this->addSql('ALTER TABLE tag CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE tag CHANGE tag tag varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for templates table
        $this->addSql('ALTER TABLE templates CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE templates CHANGE description description varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE templates CHANGE title title varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for ticket_category table
        $this->addSql('ALTER TABLE ticket_category CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE ticket_category CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket_category CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for ticket_message table
        $this->addSql('ALTER TABLE ticket_message CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
        $this->addSql('ALTER TABLE ticket_message CHANGE subject subject VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;');
        $this->addSql('ALTER TABLE ticket_message CHANGE message message LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;');
        $this->addSql('ALTER TABLE ticket_message CHANGE status status VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;');
        $this->addSql('ALTER TABLE ticket_message CHANGE ip_address ip_address VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;');
        // Change collation for ticket_message_attachments table
        $this->addSql('ALTER TABLE ticket_message_attachments CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE ticket_message_attachments CHANGE filename filename longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE ticket_message_attachments CHANGE path path varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for ticket_priority table
        $this->addSql('ALTER TABLE ticket_priority CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE ticket_priority CHANGE code code varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE ticket_priority CHANGE color color varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE ticket_priority CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket_priority CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE ticket_priority CHANGE urgency urgency varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for ticket_project table
        $this->addSql('ALTER TABLE ticket_project CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
        $this->addSql('ALTER TABLE ticket_project CHANGE title title VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;');
        $this->addSql('ALTER TABLE ticket_project CHANGE description description LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;');
        $this->addSql('ALTER TABLE ticket_project CHANGE email email VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;');
        // Change collation for ticket_status table
        $this->addSql('ALTER TABLE ticket_status CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE ticket_status CHANGE code code varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE ticket_status CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket_status CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for ticket_ticket table
        $this->addSql('ALTER TABLE ticket_ticket CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE ticket_ticket CHANGE code code VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;');
        $this->addSql('ALTER TABLE ticket_ticket CHANGE subject subject VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;');
        $this->addSql('ALTER TABLE ticket_ticket CHANGE message message LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;');
        $this->addSql('ALTER TABLE ticket_ticket CHANGE personal_email personal_email VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;');
        $this->addSql('ALTER TABLE ticket_ticket CHANGE keyword keyword VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;');
        $this->addSql('ALTER TABLE ticket_ticket CHANGE source source VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;');
        // Change collation for tool table
        $this->addSql('ALTER TABLE tool CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE tool CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for tool_resource_right table
        $this->addSql('ALTER TABLE tool_resource_right CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE tool_resource_right CHANGE role role varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for track_e_access table
        $this->addSql('ALTER TABLE track_e_access CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE track_e_access CHANGE access_tool access_tool varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE track_e_access CHANGE user_ip user_ip varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for track_e_attempt table
        $this->addSql('ALTER TABLE track_e_attempt CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE track_e_attempt CHANGE answer answer longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE track_e_attempt CHANGE filename filename varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE track_e_attempt CHANGE teacher_comment teacher_comment longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for track_e_course_access table
        $this->addSql('ALTER TABLE track_e_course_access CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE track_e_course_access CHANGE user_ip user_ip varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for track_e_default table
        $this->addSql('ALTER TABLE track_e_default CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE track_e_default CHANGE default_event_type default_event_type varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE track_e_default CHANGE default_value default_value longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE track_e_default CHANGE default_value_type default_value_type varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for track_e_downloads table
        $this->addSql('ALTER TABLE track_e_downloads CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE track_e_downloads CHANGE down_doc_path down_doc_path varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        // Change collation for track_e_exercises table
        $this->addSql('ALTER TABLE track_e_exercises CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE track_e_exercises CHANGE blocked_categories blocked_categories longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE track_e_exercises CHANGE data_tracking data_tracking longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE track_e_exercises CHANGE questions_to_check questions_to_check longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE track_e_exercises CHANGE status status varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE track_e_exercises CHANGE user_ip user_ip varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for track_e_hotpotatoes table
        $this->addSql('ALTER TABLE track_e_hotpotatoes CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE track_e_hotpotatoes CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for track_e_hotspot table
        $this->addSql('ALTER TABLE track_e_hotspot CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE track_e_hotspot CHANGE hotspot_coordinate hotspot_coordinate longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for track_e_lastaccess table
        $this->addSql('ALTER TABLE track_e_lastaccess CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE track_e_lastaccess CHANGE access_tool access_tool varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        // Change collation for track_e_login table
        $this->addSql('ALTER TABLE track_e_login CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE track_e_login CHANGE user_ip user_ip varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for track_e_online table
        $this->addSql('ALTER TABLE track_e_online CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE track_e_online CHANGE user_ip user_ip varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for user table
        $this->addSql('ALTER TABLE user CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE user CHANGE address address varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE api_token api_token varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE biography biography longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE competences competences longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE confirmation_token confirmation_token varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE diplomas diplomas longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE email email varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE email_canonical email_canonical varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE firstname firstname varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE gender gender varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE lastname lastname varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE locale locale varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE mfa_backup_codes mfa_backup_codes longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE mfa_secret mfa_secret varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE mfa_service mfa_service varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE official_code official_code varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE openarea openarea longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE openid openid varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE password password varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE phone phone varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE picture_uri picture_uri varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE productions productions varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE roles roles longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE salt salt varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE teach teach longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE theme theme varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE timezone timezone varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE username username varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE username_canonical username_canonical varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE website website varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        // Change collation for usergroup table
        $this->addSql('ALTER TABLE usergroup CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE usergroup CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE usergroup CHANGE picture picture varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE usergroup CHANGE title title varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE usergroup CHANGE url url varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE usergroup CHANGE visibility visibility varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for user_api_key table
        $this->addSql('ALTER TABLE user_api_key CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE user_api_key CHANGE api_end_point api_end_point longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user_api_key CHANGE api_key api_key varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE user_api_key CHANGE api_service api_service varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE user_api_key CHANGE description description longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL');
        // Change collation for user_course_category table
        $this->addSql('ALTER TABLE user_course_category CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE user_course_category CHANGE title title longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        // Change collation for user_friend_relation_type table
        $this->addSql('ALTER TABLE user_friend_relation_type CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE user_friend_relation_type CHANGE title title varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');

    }

    public function down(Schema $schema): void
    {
        // Revert collation for c_announcement table
        $this->addSql('ALTER TABLE c_announcement CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_announcement CHANGE title title TEXT CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');


        // Revert collation for access_url table
        $this->addSql('ALTER TABLE access_url CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE access_url CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE access_url CHANGE email email varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE access_url CHANGE url url varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for agenda_reminder table
        $this->addSql('ALTER TABLE agenda_reminder CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE agenda_reminder CHANGE date_interval date_interval varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for block table
        $this->addSql('ALTER TABLE block CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE block CHANGE controller controller varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE block CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE block CHANGE path path varchar(190) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE block CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        // Revert collation for branch_sync table
        $this->addSql('ALTER TABLE branch_sync CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE branch_sync CHANGE admin_mail admin_mail varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE branch_sync CHANGE admin_name admin_name varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE branch_sync CHANGE admin_phone admin_phone varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE branch_sync CHANGE branch_ip branch_ip varchar(40) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE branch_sync CHANGE branch_type branch_type varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE branch_sync CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE branch_sync CHANGE last_sync_type last_sync_type varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE branch_sync CHANGE ssl_pub_key ssl_pub_key varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE branch_sync CHANGE title title varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE branch_sync CHANGE unique_id unique_id varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for branch_transaction table
        $this->addSql('ALTER TABLE branch_transaction CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE branch_transaction CHANGE action action varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE branch_transaction CHANGE dest_id dest_id varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE branch_transaction CHANGE external_info external_info varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE branch_transaction CHANGE item_id item_id varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE branch_transaction CHANGE origin origin varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        // Revert collation for branch_transaction table
        $this->addSql('ALTER TABLE branch_transaction_status CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE branch_transaction_status CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for career table
        $this->addSql('ALTER TABLE career CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE career CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE career CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for chat table
        $this->addSql('ALTER TABLE chat CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE chat CHANGE message message longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for chat_video table
        $this->addSql('ALTER TABLE chat_video CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE chat_video CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for course table
        $this->addSql('ALTER TABLE course CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE course CHANGE code code varchar(40) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE course CHANGE course_language course_language varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE course CHANGE department_name department_name varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course CHANGE department_url department_url varchar(180) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course CHANGE directory directory varchar(40) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course CHANGE introduction introduction longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course CHANGE legal legal longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course CHANGE registration_code registration_code varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course CHANGE title title varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course CHANGE tutor_name tutor_name varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course CHANGE video_url video_url varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE course CHANGE visual_code visual_code varchar(40) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        // Revert collation for course_category table
        $this->addSql('ALTER TABLE course_category CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE course_category CHANGE auth_cat_child auth_cat_child varchar(40) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course_category CHANGE auth_course_child auth_course_child varchar(40) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course_category CHANGE code code varchar(40) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE course_category CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course_category CHANGE title title longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for course_rel_class table
        $this->addSql('ALTER TABLE course_rel_class CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE course_rel_class CHANGE course_code course_code varchar(40) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for course_request table
        $this->addSql('ALTER TABLE course_request CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE course_request CHANGE category_code category_code varchar(40) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course_request CHANGE code code varchar(40) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE course_request CHANGE course_language course_language varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE course_request CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course_request CHANGE objetives objetives longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course_request CHANGE target_audience target_audience longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course_request CHANGE title title varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE course_request CHANGE tutor_name tutor_name varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course_request CHANGE visual_code visual_code varchar(40) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        // Revert collation for course_type table
        $this->addSql('ALTER TABLE course_type CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE course_type CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course_type CHANGE props props longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE course_type CHANGE title title varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE course_type CHANGE translation_var translation_var varchar(40) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        // Revert collation for c_announcement_attachment table
        $this->addSql('ALTER TABLE c_announcement_attachment CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_announcement_attachment CHANGE comment comment longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_announcement_attachment CHANGE filename filename varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_announcement_attachment CHANGE path path varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_attendance table
        $this->addSql('ALTER TABLE c_attendance CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_attendance CHANGE attendance_qualify_title attendance_qualify_title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_attendance CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_attendance CHANGE title title longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_attendance_sheet table
        $this->addSql('ALTER TABLE c_attendance_sheet CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_attendance_sheet CHANGE signature signature longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        // Revert collation for c_attendance_sheet_log table
        $this->addSql('ALTER TABLE c_attendance_sheet_log CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_attendance_sheet_log CHANGE lastedit_type lastedit_type varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_blog table
        $this->addSql('ALTER TABLE c_blog CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_blog CHANGE blog_subtitle blog_subtitle varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_blog CHANGE title title longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_blog_attachment table
        $this->addSql('ALTER TABLE c_blog_attachment CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_blog_attachment CHANGE comment comment longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_blog_attachment CHANGE filename filename varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_blog_attachment CHANGE path path varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_blog_comment table
        $this->addSql('ALTER TABLE c_blog_comment CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_blog_comment CHANGE comment comment longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_blog_comment CHANGE title title varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_blog_post table
        $this->addSql('ALTER TABLE c_blog_post CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_blog_post CHANGE full_text full_text longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_blog_post CHANGE title title varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_blog_rating table
        $this->addSql('ALTER TABLE c_blog_rating CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_blog_rating CHANGE rating_type rating_type varchar(40) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_blog_task table
        $this->addSql('ALTER TABLE c_blog_task CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_blog_task CHANGE color color varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_blog_task CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_blog_task CHANGE title title varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_calendar_event table
        $this->addSql('ALTER TABLE c_calendar_event CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_calendar_event CHANGE color color varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_calendar_event CHANGE comment comment longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_calendar_event CHANGE content content longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_calendar_event CHANGE invitation_type invitation_type varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_calendar_event CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_calendar_event_attachment table
        $this->addSql('ALTER TABLE c_calendar_event_attachment CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_calendar_event_attachment CHANGE comment comment longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_calendar_event_attachment CHANGE filename filename varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_calendar_event_repeat table
        $this->addSql('ALTER TABLE c_calendar_event_repeat CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_calendar_event_repeat CHANGE cal_days cal_days varchar(7) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_calendar_event_repeat CHANGE cal_type cal_type varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        // Revert collation for c_course_description table
        $this->addSql('ALTER TABLE c_course_description CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_course_description CHANGE content content longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_course_description CHANGE title title longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        // Revert collation for c_course_setting table
        $this->addSql('ALTER TABLE c_course_setting CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_course_setting CHANGE category category varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_course_setting CHANGE comment comment varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_course_setting CHANGE subkey subkey varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_course_setting CHANGE subkeytext subkeytext varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_course_setting CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_course_setting CHANGE type type varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_course_setting CHANGE value value longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_course_setting CHANGE variable variable varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_document table
        $this->addSql('ALTER TABLE c_document CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_document CHANGE comment comment longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_document CHANGE filetype filetype varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_document CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_dropbox_category table
        $this->addSql('ALTER TABLE c_dropbox_category CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_dropbox_category CHANGE title title longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_dropbox_feedback table
        $this->addSql('ALTER TABLE c_dropbox_feedback CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_dropbox_feedback CHANGE feedback feedback longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_dropbox_file table
        $this->addSql('ALTER TABLE c_dropbox_file CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_dropbox_file CHANGE author author varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_dropbox_file CHANGE description description varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_dropbox_file CHANGE filename filename varchar(190) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_dropbox_file CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_dropbox_post table
        $this->addSql('ALTER TABLE c_dropbox_post CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_dropbox_post CHANGE feedback feedback longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        // Revert collation for c_forum_attachment table
        $this->addSql('ALTER TABLE c_forum_attachment CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_forum_attachment CHANGE comment comment longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_forum_attachment CHANGE filename filename varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_forum_attachment CHANGE path path varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_forum_category table
        $this->addSql('ALTER TABLE c_forum_category CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_forum_category CHANGE cat_comment cat_comment longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_forum_category CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_forum_forum table
        $this->addSql('ALTER TABLE c_forum_forum CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_forum_forum CHANGE approval_direct_post approval_direct_post varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_forum_forum CHANGE default_view default_view varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_forum_forum CHANGE forum_comment forum_comment longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_forum_forum CHANGE forum_group_public_private forum_group_public_private varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_forum_forum CHANGE forum_image forum_image varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_forum_forum CHANGE forum_of_group forum_of_group varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_forum_forum CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_forum_post table
        $this->addSql('ALTER TABLE c_forum_post CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_forum_post CHANGE post_text post_text longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_forum_post CHANGE title title varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_forum_thread table
        $this->addSql('ALTER TABLE c_forum_thread CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_forum_thread CHANGE thread_title_qualify thread_title_qualify varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_forum_thread CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_glossary table
        $this->addSql('ALTER TABLE c_glossary CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_glossary CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_glossary CHANGE title title longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_group_category table
        $this->addSql('ALTER TABLE c_group_category CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_group_category CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci  NULL');
        $this->addSql('ALTER TABLE c_group_category CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_group_info table
        $this->addSql('ALTER TABLE c_group_info CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_group_info CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_group_info CHANGE title title varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_group_rel_user table
        $this->addSql('ALTER TABLE c_group_rel_user CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_group_rel_user CHANGE role role varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_link table
        $this->addSql('ALTER TABLE c_link CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_link CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_link CHANGE target target varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_link CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_link CHANGE url url longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_link_category table
        $this->addSql('ALTER TABLE c_link_category CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_link_category CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_link_category CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_lp table
        $this->addSql('ALTER TABLE c_lp CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_lp CHANGE author author longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp CHANGE content_license content_license longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp CHANGE content_local content_local varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp CHANGE content_maker content_maker longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp CHANGE default_encoding default_encoding varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp CHANGE default_view_mod default_view_mod varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_lp CHANGE js_lib js_lib longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp CHANGE path path longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp CHANGE ref ref longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_lp CHANGE theme theme varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_lp_category table
        $this->addSql('ALTER TABLE c_lp_category CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_lp_category CHANGE title title longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_lp_item table
        $this->addSql('ALTER TABLE c_lp_item CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_lp_item CHANGE audio audio varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_lp_item CHANGE description description varchar(511) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_lp_item CHANGE item_type item_type varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp_item CHANGE launch_data launch_data longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp_item CHANGE max_time_allowed max_time_allowed varchar(13) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_lp_item CHANGE parameters parameters longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_lp_item CHANGE path path longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp_item CHANGE prerequisite prerequisite longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_lp_item CHANGE ref ref longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp_item CHANGE terms terms longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_lp_item CHANGE title title varchar(511) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_lp_item_view table
        $this->addSql('ALTER TABLE c_lp_item_view CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_lp_item_view CHANGE core_exit core_exit varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp_item_view CHANGE lesson_location lesson_location longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_lp_item_view CHANGE max_score max_score varchar(8) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_lp_item_view CHANGE status status varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp_item_view CHANGE suspend_data suspend_data longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        // Revert collation for c_lp_iv_interaction table
        $this->addSql('ALTER TABLE c_lp_iv_interaction CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_lp_iv_interaction CHANGE completion_time completion_time varchar(16) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp_iv_interaction CHANGE correct_responses correct_responses longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp_iv_interaction CHANGE interaction_id interaction_id varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp_iv_interaction CHANGE interaction_type interaction_type varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp_iv_interaction CHANGE latency latency varchar(16) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp_iv_interaction CHANGE result result varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp_iv_interaction CHANGE student_response student_response longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_lp_iv_objective table
        $this->addSql('ALTER TABLE c_lp_iv_objective CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_lp_iv_objective CHANGE objective_id objective_id varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_lp_iv_objective CHANGE status status varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_notebook table
        $this->addSql('ALTER TABLE c_notebook CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_notebook CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_notebook CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_quiz table
        $this->addSql('ALTER TABLE c_quiz CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_quiz CHANGE access_condition access_condition longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz CHANGE notifications notifications varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz CHANGE page_result_configuration page_result_configuration longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_quiz CHANGE sound sound varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz CHANGE text_when_finished text_when_finished longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz CHANGE text_when_finished_failure text_when_finished_failure longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz CHANGE title title longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_quiz_answer table
        $this->addSql('ALTER TABLE c_quiz_answer CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_quiz_answer CHANGE answer answer longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_quiz_answer CHANGE answer_code answer_code varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz_answer CHANGE comment comment longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz_answer CHANGE hotspot_coordinates hotspot_coordinates longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz_answer CHANGE hotspot_type hotspot_type varchar(40) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
	// Revert collation for c_quiz_category table
        $this->addSql('ALTER TABLE c_quiz_category CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_quiz_category CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz_category CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_quiz_question table
        $this->addSql('ALTER TABLE c_quiz_question CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_quiz_question CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz_question CHANGE extra extra varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz_question CHANGE feedback feedback longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz_question CHANGE picture picture varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz_question CHANGE question question longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_quiz_question CHANGE question_code question_code varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        // Revert collation for c_quiz_question_category table
        $this->addSql('ALTER TABLE c_quiz_question_category CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_quiz_question_category CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz_question_category CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_quiz_question_option table
        $this->addSql('ALTER TABLE c_quiz_question_option CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_quiz_question_option CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_quiz_rel_question table
        $this->addSql('ALTER TABLE c_quiz_rel_question CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_quiz_rel_question CHANGE destination destination longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        // Revert collation for c_student_publication table
        $this->addSql('ALTER TABLE c_student_publication CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_student_publication CHANGE author author varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_student_publication CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_student_publication CHANGE extensions extensions longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_student_publication CHANGE filetype filetype varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_student_publication CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_student_publication_comment table
        $this->addSql('ALTER TABLE c_student_publication_comment CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_student_publication_comment CHANGE comment comment longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_student_publication_comment CHANGE file file varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        // Revert collation for c_survey table
        $this->addSql('ALTER TABLE c_survey CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_survey CHANGE access_condition access_condition longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_survey CHANGE anonymous anonymous varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_survey CHANGE code code varchar(40) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_survey CHANGE form_fields form_fields longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_survey CHANGE intro intro longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_survey CHANGE invite_mail invite_mail longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_survey CHANGE is_shared is_shared varchar(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_survey CHANGE lang lang varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_survey CHANGE mail_subject mail_subject varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_survey CHANGE reminder_mail reminder_mail longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_survey CHANGE subtitle subtitle longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_survey CHANGE surveythanks surveythanks longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_survey CHANGE survey_version survey_version varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_survey CHANGE template template varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_survey CHANGE title title longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_survey_answer table
        $this->addSql('ALTER TABLE c_survey_answer CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_survey_answer CHANGE option_id option_id longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_survey_answer CHANGE user user varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_survey_invitation table
        $this->addSql('ALTER TABLE c_survey_invitation CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_survey_invitation CHANGE invitation_code invitation_code varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_survey_question table
        $this->addSql('ALTER TABLE c_survey_question CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_survey_question CHANGE display display varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_survey_question CHANGE survey_question survey_question longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_survey_question CHANGE survey_question_comment survey_question_comment longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_survey_question CHANGE type type varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_survey_question_option table
        $this->addSql('ALTER TABLE c_survey_question_option CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_survey_question_option CHANGE option_text option_text longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_thematic table
        $this->addSql('ALTER TABLE c_thematic CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_thematic CHANGE content content longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_thematic CHANGE title title longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_thematic_advance table
        $this->addSql('ALTER TABLE c_thematic_advance CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_thematic_advance CHANGE content content longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        // Revert collation for c_thematic_plan table
        $this->addSql('ALTER TABLE c_thematic_plan CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_thematic_plan CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE c_thematic_plan CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_tool table
        $this->addSql('ALTER TABLE c_tool CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_tool CHANGE title title longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_tool_intro table
        $this->addSql('ALTER TABLE c_tool_intro CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_tool_intro CHANGE intro_text intro_text longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_wiki table
        $this->addSql('ALTER TABLE c_wiki CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_wiki CHANGE comment comment longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_wiki CHANGE content content longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_wiki CHANGE linksto linksto longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_wiki CHANGE progress progress longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_wiki CHANGE reflink reflink varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_wiki CHANGE tag tag longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_wiki CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_wiki CHANGE user_ip user_ip varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_wiki_conf table
        $this->addSql('ALTER TABLE c_wiki_conf CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_wiki_conf CHANGE feedback1 feedback1 longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_wiki_conf CHANGE feedback2 feedback2 longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_wiki_conf CHANGE feedback3 feedback3 longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_wiki_conf CHANGE fprogress1 fprogress1 varchar(3) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_wiki_conf CHANGE fprogress2 fprogress2 varchar(3) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_wiki_conf CHANGE fprogress3 fprogress3 varchar(3) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_wiki_conf CHANGE task task longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for c_wiki_discuss table
        $this->addSql('ALTER TABLE c_wiki_discuss CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_wiki_discuss CHANGE comment comment longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE c_wiki_discuss CHANGE p_score p_score varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        // Revert collation for c_wiki_mailcue table
        $this->addSql('ALTER TABLE c_wiki_mailcue CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE c_wiki_mailcue CHANGE type type longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for extra_field table
        $this->addSql('ALTER TABLE extra_field CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE extra_field CHANGE default_value default_value longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE extra_field CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE extra_field CHANGE display_text display_text varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE extra_field CHANGE helper_text helper_text longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE extra_field CHANGE variable variable varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for extra_field_options table
        $this->addSql('ALTER TABLE extra_field_options CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE extra_field_options CHANGE display_text display_text varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE extra_field_options CHANGE option_value option_value longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE extra_field_options CHANGE priority priority varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE extra_field_options CHANGE priority_message priority_message varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        // Revert collation for extra_field_saved_search table
        $this->addSql('ALTER TABLE extra_field_saved_search CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE extra_field_saved_search CHANGE value value longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        // Revert collation for extra_field_values table
        $this->addSql('ALTER TABLE extra_field_values CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE extra_field_values CHANGE comment comment longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE extra_field_values CHANGE field_value field_value longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        // Revert collation for ext_log_entries table
        $this->addSql('ALTER TABLE ext_log_entries CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE ext_log_entries CHANGE action action varchar(8) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE ext_log_entries CHANGE data data longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE ext_log_entries CHANGE object_class object_class varchar(191) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE ext_log_entries CHANGE object_id object_id varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE ext_log_entries CHANGE username username varchar(191) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        // Revert collation for fos_group table
        $this->addSql('ALTER TABLE fos_group CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE fos_group CHANGE code code varchar(40) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE fos_group CHANGE roles roles longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE fos_group CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for gradebook_category table
        $this->addSql('ALTER TABLE gradebook_category CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE gradebook_category CHANGE depends depends longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE gradebook_category CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE gradebook_category CHANGE title title longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for gradebook_certificate table
        $this->addSql('ALTER TABLE gradebook_certificate CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE gradebook_certificate CHANGE path_certificate path_certificate longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        // Revert collation for gradebook_evaluation table
        $this->addSql('ALTER TABLE gradebook_evaluation CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE gradebook_evaluation CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE gradebook_evaluation CHANGE title title longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE gradebook_evaluation CHANGE type type varchar(40) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE gradebook_evaluation CHANGE user_score_list user_score_list longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        // Revert collation for gradebook_link table
        $this->addSql('ALTER TABLE gradebook_link CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE gradebook_link CHANGE user_score_list user_score_list longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        // Revert collation for gradebook_linkeval_log table
        $this->addSql('ALTER TABLE gradebook_linkeval_log CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE gradebook_linkeval_log CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE gradebook_linkeval_log CHANGE title title longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE gradebook_linkeval_log CHANGE type type varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for gradebook_score_display table
        $this->addSql('ALTER TABLE gradebook_score_display CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE gradebook_score_display CHANGE display display varchar(40) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for grade_components table
        $this->addSql('ALTER TABLE grade_components CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE grade_components CHANGE acronym acronym varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE grade_components CHANGE percentage percentage varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE grade_components CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for grade_model table
        $this->addSql('ALTER TABLE grade_model CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE grade_model CHANGE default_external_eval_prefix default_external_eval_prefix varchar(140) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE grade_model CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE grade_model CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for language table
        $this->addSql('ALTER TABLE language CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE language CHANGE english_name english_name varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE language CHANGE isocode isocode varchar(8) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE language CHANGE original_name original_name varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        // Revert collation for legal table
        $this->addSql('ALTER TABLE legal CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE legal CHANGE changes changes longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE legal CHANGE content content longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        // Revert collation for message_attachment table
        $this->addSql('ALTER TABLE message_attachment CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE message_attachment CHANGE comment comment longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE message_attachment CHANGE filename filename varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE message_attachment CHANGE path path varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for portfolio table
        $this->addSql('ALTER TABLE portfolio CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE portfolio CHANGE content content longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE portfolio CHANGE title title longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for portfolio_category table
        $this->addSql('ALTER TABLE portfolio_category CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE portfolio_category CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE portfolio_category CHANGE title title longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for promotion table
        $this->addSql('ALTER TABLE promotion CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE promotion CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE promotion CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for room table
        $this->addSql('ALTER TABLE room CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE room CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE room CHANGE geolocation geolocation varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE room CHANGE ip ip varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE room CHANGE ip_mask ip_mask varchar(6) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE room CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for scheduled_announcements table
        $this->addSql('ALTER TABLE scheduled_announcements CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE scheduled_announcements CHANGE message message longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE scheduled_announcements CHANGE subject subject varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for search_engine_ref table
        $this->addSql('ALTER TABLE search_engine_ref CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE search_engine_ref CHANGE tool_id tool_id varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for sequence table
        $this->addSql('ALTER TABLE sequence CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE sequence CHANGE graph graph longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE sequence CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for sequence_condition table
        $this->addSql('ALTER TABLE sequence_condition CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE sequence_condition CHANGE act_false act_false varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE sequence_condition CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE sequence_condition CHANGE mat_op mat_op varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for sequence_method table
        $this->addSql('ALTER TABLE sequence_method CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE sequence_method CHANGE act_false act_false varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE sequence_method CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE sequence_method CHANGE formula formula longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE sequence_method CHANGE met_type met_type varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for sequence_row_entity table
        $this->addSql('ALTER TABLE sequence_row_entity CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE sequence_row_entity CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for sequence_rule table
        $this->addSql('ALTER TABLE sequence_rule CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE sequence_rule CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for sequence_type_entity table
        $this->addSql('ALTER TABLE sequence_type_entity CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE sequence_type_entity CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE sequence_type_entity CHANGE ent_table ent_table varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE sequence_type_entity CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for sequence_variable table
        $this->addSql('ALTER TABLE sequence_variable CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE sequence_variable CHANGE default_val default_val varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE sequence_variable CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE sequence_variable CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        // Revert collation for session table
        $this->addSql('ALTER TABLE session CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE session CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE session CHANGE title title varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for session_category table
        $this->addSql('ALTER TABLE session_category CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE session_category CHANGE title title varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for settings table
        $this->addSql('ALTER TABLE settings CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE settings CHANGE category category varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE settings CHANGE comment comment longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE settings CHANGE scope scope varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE settings CHANGE selected_value selected_value longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE settings CHANGE subkey subkey varchar(190) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE settings CHANGE subkeytext subkeytext varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE settings CHANGE title title longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE settings CHANGE type type varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE settings CHANGE variable variable varchar(190) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for settings_options table
        $this->addSql('ALTER TABLE settings_options CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE settings_options CHANGE display_text display_text varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE settings_options CHANGE value value varchar(190) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE settings_options CHANGE variable variable varchar(190) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for skill table
        $this->addSql('ALTER TABLE skill CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE skill CHANGE criteria criteria longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE skill CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE skill CHANGE icon icon varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE skill CHANGE short_code short_code varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE skill CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for skill_level table
        $this->addSql('ALTER TABLE skill_level CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE skill_level CHANGE short_title short_title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE skill_level CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for skill_level_profile table
        $this->addSql('ALTER TABLE skill_level_profile CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE skill_level_profile CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for skill_profile table
        $this->addSql('ALTER TABLE skill_profile CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE skill_profile CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE skill_profile CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for skill_rel_gradebook table
        $this->addSql('ALTER TABLE skill_rel_gradebook CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE skill_rel_gradebook CHANGE type type varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for skill_rel_item table
        $this->addSql('ALTER TABLE skill_rel_item CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE skill_rel_item CHANGE obtain_conditions obtain_conditions varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        // Revert collation for skill_rel_user table
        $this->addSql('ALTER TABLE skill_rel_user CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE skill_rel_user CHANGE argumentation argumentation longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for skill_rel_user_comment table
        $this->addSql('ALTER TABLE skill_rel_user_comment CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE skill_rel_user_comment CHANGE feedback_text feedback_text longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for specific_field table
        $this->addSql('ALTER TABLE specific_field CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE specific_field CHANGE code code varchar(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE specific_field CHANGE title title varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for specific_field_values table
        $this->addSql('ALTER TABLE specific_field_values CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE specific_field_values CHANGE course_code course_code varchar(40) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE specific_field_values CHANGE tool_id tool_id varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE specific_field_values CHANGE value value varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for system_template table
        $this->addSql('ALTER TABLE system_template CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE system_template CHANGE comment comment longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE system_template CHANGE content content longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE system_template CHANGE language language varchar(40) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE system_template CHANGE title title varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for sys_announcement table
        $this->addSql('ALTER TABLE sys_announcement CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE sys_announcement CHANGE content content longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE sys_announcement CHANGE lang lang varchar(70) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE sys_announcement CHANGE roles roles longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE sys_announcement CHANGE title title varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for tag table
        $this->addSql('ALTER TABLE tag CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE tag CHANGE tag tag varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for templates table
        $this->addSql('ALTER TABLE templates CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE templates CHANGE description description varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE templates CHANGE title title varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for ticket_category table
        $this->addSql('ALTER TABLE ticket_category CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE ticket_category CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket_category CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for ticket_message table
        $this->addSql('ALTER TABLE ticket_message CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci;');
        $this->addSql('ALTER TABLE ticket_message CHANGE subject subject VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;');
        $this->addSql('ALTER TABLE ticket_message CHANGE message message LONGTEXT CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL;');
        $this->addSql('ALTER TABLE ticket_message CHANGE status status VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;');
        $this->addSql('ALTER TABLE ticket_message CHANGE ip_address ip_address VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;');
        // Revert collation for ticket_message_attachments table
        $this->addSql('ALTER TABLE ticket_message_attachments CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE ticket_message_attachments CHANGE filename filename longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE ticket_message_attachments CHANGE path path varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for ticket_priority table
        $this->addSql('ALTER TABLE ticket_priority CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE ticket_priority CHANGE code code varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE ticket_priority CHANGE color color varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE ticket_priority CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket_priority CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE ticket_priority CHANGE urgency urgency varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for ticket_project table
        $this->addSql('ALTER TABLE ticket_project CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci;');
        $this->addSql('ALTER TABLE ticket_project CHANGE title title VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;');
        $this->addSql('ALTER TABLE ticket_project CHANGE description description LONGTEXT CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL;');
        $this->addSql('ALTER TABLE ticket_project CHANGE email email VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL;');
        // Revert collation for ticket_status table
        $this->addSql('ALTER TABLE ticket_status CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE ticket_status CHANGE code code varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE ticket_status CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket_status CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for ticket_ticket table
        $this->addSql('ALTER TABLE ticket_ticket CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE ticket_ticket CHANGE code code VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;');
        $this->addSql('ALTER TABLE ticket_ticket CHANGE subject subject VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;');
        $this->addSql('ALTER TABLE ticket_ticket CHANGE message message LONGTEXT CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL;');
        $this->addSql('ALTER TABLE ticket_ticket CHANGE personal_email personal_email VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;');
        $this->addSql('ALTER TABLE ticket_ticket CHANGE keyword keyword VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL;');
        $this->addSql('ALTER TABLE ticket_ticket CHANGE source source VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL;');
        // Revert collation for tool table
        $this->addSql('ALTER TABLE tool CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE tool CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for tool_resource_right table
        $this->addSql('ALTER TABLE tool_resource_right CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE tool_resource_right CHANGE role role varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for track_e_access table
        $this->addSql('ALTER TABLE track_e_access CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE track_e_access CHANGE access_tool access_tool varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE track_e_access CHANGE user_ip user_ip varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for track_e_attempt table
        $this->addSql('ALTER TABLE track_e_attempt CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE track_e_attempt CHANGE answer answer longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE track_e_attempt CHANGE filename filename varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE track_e_attempt CHANGE teacher_comment teacher_comment longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for track_e_course_access table
        $this->addSql('ALTER TABLE track_e_course_access CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE track_e_course_access CHANGE user_ip user_ip varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for track_e_default table
        $this->addSql('ALTER TABLE track_e_default CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE track_e_default CHANGE default_event_type default_event_type varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE track_e_default CHANGE default_value default_value longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE track_e_default CHANGE default_value_type default_value_type varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for track_e_downloads table
        $this->addSql('ALTER TABLE track_e_downloads CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE track_e_downloads CHANGE down_doc_path down_doc_path varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        // Revert collation for track_e_exercises table
        $this->addSql('ALTER TABLE track_e_exercises CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE track_e_exercises CHANGE blocked_categories blocked_categories longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE track_e_exercises CHANGE data_tracking data_tracking longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE track_e_exercises CHANGE questions_to_check questions_to_check longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE track_e_exercises CHANGE status status varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE track_e_exercises CHANGE user_ip user_ip varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for track_e_hotpotatoes table
        $this->addSql('ALTER TABLE track_e_hotpotatoes CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE track_e_hotpotatoes CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for track_e_hotspot table
        $this->addSql('ALTER TABLE track_e_hotspot CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE track_e_hotspot CHANGE hotspot_coordinate hotspot_coordinate longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for track_e_lastaccess table
        $this->addSql('ALTER TABLE track_e_lastaccess CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE track_e_lastaccess CHANGE access_tool access_tool varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        // Revert collation for track_e_login table
        $this->addSql('ALTER TABLE track_e_login CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE track_e_login CHANGE user_ip user_ip varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for track_e_online table
        $this->addSql('ALTER TABLE track_e_online CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE track_e_online CHANGE user_ip user_ip varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for user table
        $this->addSql('ALTER TABLE user CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE user CHANGE address address varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE api_token api_token varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE biography biography longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE competences competences longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE confirmation_token confirmation_token varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE diplomas diplomas longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE email email varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE email_canonical email_canonical varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE firstname firstname varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE gender gender varchar(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE lastname lastname varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE locale locale varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE mfa_backup_codes mfa_backup_codes longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE mfa_secret mfa_secret varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE mfa_service mfa_service varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE official_code official_code varchar(40) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE openarea openarea longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE openid openid varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE password password varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE phone phone varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE picture_uri picture_uri varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE productions productions varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE roles roles longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE salt salt varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE teach teach longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE theme theme varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE timezone timezone varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE username username varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE username_canonical username_canonical varchar(180) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE website website varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        // Revert collation for usergroup table
        $this->addSql('ALTER TABLE usergroup CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE usergroup CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE usergroup CHANGE picture picture varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE usergroup CHANGE title title varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE usergroup CHANGE url url varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE usergroup CHANGE visibility visibility varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for user_api_key table
        $this->addSql('ALTER TABLE user_api_key CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE user_api_key CHANGE api_end_point api_end_point longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        $this->addSql('ALTER TABLE user_api_key CHANGE api_key api_key varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE user_api_key CHANGE api_service api_service varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        $this->addSql('ALTER TABLE user_api_key CHANGE description description longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL');
        // Revert collation for user_course_category table
        $this->addSql('ALTER TABLE user_course_category CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE user_course_category CHANGE title title longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');
        // Revert collation for user_friend_relation_type table
        $this->addSql('ALTER TABLE user_friend_relation_type CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci');
        $this->addSql('ALTER TABLE user_friend_relation_type CHANGE title title varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL');

    }
}
