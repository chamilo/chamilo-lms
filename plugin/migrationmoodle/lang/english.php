<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Migration From Moodle';
$strings['plugin_comment'] = 'Execute a migration process from Moodle data to Chamilo.';

$strings['MoodlePassword'] = 'Moodle password';
$strings['UninstallError'] = 'An error ocurred while the plugin was uninstalled.';

$strings['active'] = 'Active';
$strings['db_host'] = 'Moodle DB host';
$strings['db_user'] = 'Moodle DB user';
$strings['db_password'] = 'Moodle DB password';
$strings['db_name'] = 'Moodle DB name';
$strings['user_filter'] = 'Filter for users';
$strings['user_filter_help'] = 'Allow migrate only users who have a username prefixed with it.<br>'
    .'Course migration is also affected by this configuration, it only migrates courses in which users with the username prefix are enrolled.';
$strings['url_id'] = 'URL ID';
$strings['url_id_help'] = 'Access URL ID to save course, users and sessions .';
$strings['moodle_path'] = 'Moodle data path';
$strings['moodle_path'] = '<pre>/var/www/moodle/moodledata</pre>';

// Tasks
$strings['UsersTask'] = 'Users';
$strings['CourseCategoriesTask'] = 'Course categories';
$strings['CoursesTask'] = 'Courses';
$strings['CourseSectionsTask'] = 'Course sections';
$strings['CourseModulesLessonTask'] = 'Course modules: Lessons';
$strings['LessonPagesTask'] = 'Lesson pages';
$strings['LessonPagesDocumentTask'] = 'Lesson pages: Documents';
$strings['FilesForLessonPagesTask'] = 'Files for lesson pages';
$strings['LessonPagesQuizTask'] = 'Lesson pages: Questions';
$strings['LessonPagesQuizQuestionTask'] = 'Questions for question pages';
$strings['LessonAnswersTrueFalseTask'] = 'Answers for True/False questions';
$strings['LessonAnswersMultipleChoiceTask'] = 'Answers for Multiple Choice questions';
$strings['LessonAnswersMultipleAnswerTask'] = 'Answers for Multiple Answer questions';
$strings['LessonAnswersMatchingTask'] = 'Answers for Matching questions';
$strings['LessonAnswersEssayTask'] = 'Answers for Essay questions';
$strings['LessonAnswersShortAnswerTask'] = 'Answers for Short Answer questions';
$strings['FilesForLessonAnswersTask'] = 'Files for lesson answers';
$strings['CourseModulesQuizTask'] = 'Course modules: Quizzes';
$strings['CQuizTask'] = 'C Quiz';
$strings['RoleAssignmentsTask'] = 'Role assignments';
$strings['QuizzesTask'] = 'Quizzes';
$strings['FilesForQuizzesTask'] = 'Files for quizzes';
$strings['QuestionCategoriesTask'] = 'Question categories';
$strings['QuestionsTask'] = 'Questions';
$strings['QuestionMultiChoiceSingleTask'] = 'Answers for multichoice questions (single)';
$strings['QuestionMultiChoiceMultipleTask'] = 'Answers for multichoice questions (multiple)';
$strings['QuestionsTrueFalseTask'] = 'Answers for truefalse questions';
$strings['QuestionShortAnswerTask'] = 'Answers for shortanswers questions';
$strings['CourseModulesScormTask'] = 'Course Scorms';
$strings['ScormScoesTask'] = 'Scorms items';
$strings['FilesForScormScoesTask'] = 'Files for Scorm items';
$strings['UserSessionsTask'] = 'Course Sessions for users';
$strings['CourseIntroductionsTask'] = 'Course introductions';
$strings['FilesForCourseIntroductionsTask'] = 'Files for course introductions';
$strings['FilesForCourseSectionsTask'] = 'Files for course sections';
$strings['CourseModulesUrlTask'] = 'Course modules: URLs';
$strings['UrlsTask'] = 'URLs';
$strings['SortSectionModulesTask'] = 'Sort modules in section';
$strings['UsersScormsViewTask'] = 'Scorm views for users';
$strings['UsersScormsProgressTask'] = 'Scorm progress';
$strings['UsersLearnPathsTask'] = 'Learn paths views of users';
$strings['UsersLearnPathsLessonTimerTask'] = 'Lesson timer to start time of Learn paths section';
$strings['QuizzesScoresTask'] = 'Update quiz scores in learn path';
$strings['QuestionGapselectTask'] = 'Answers for gapselect questions';
$strings['UsersLearnPathsLessonBranchTask'] = 'Lesson branch to total time in learn paths documents';
$strings['UsersLearnPathsLessonAttemptsTask'] = 'Lesson attempts to total time in learn paths quizzes';
$strings['UsersLearnPathsQuizzesTask'] = 'Quizzes attempts to learn paths quizzes attempts';
$strings['UsersQuizzesAttemptsTask'] = 'Quiz attempts of users';
$strings['UserQuestionAttemptsShortanswerTask'] = 'Question attempts of users for shortanswer';
$strings['UserQuestionAttemptsGapselectTask'] = 'Question attempts of users for gapselect';
$strings['UserQuestionAttemptsTruefalseTask'] = 'Question attempts of users for truefalse';
$strings['UsersLastLoginTask'] = 'Last logins for users';
$strings['TrackLoginTask'] = 'First login and last logout';
