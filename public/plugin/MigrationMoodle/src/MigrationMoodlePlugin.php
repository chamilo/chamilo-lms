<?php
/* For licensing terms, see /license.txt */

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

/**
 * Class MigrationMoodlePlugin.
 */
class MigrationMoodlePlugin extends Plugin
{
    public const SETTING_USER_FILTER = 'user_filter';
    public const SETTING_URL_ID = 'url_id';
    public const SETTING_MOODLE_PATH = 'moodle_path';

    public $isAdminPlugin = true;

    protected function __construct()
    {
        $version = '0.0.1';
        $author = 'Angel Fernando Quiroz Campos';
        $settings = [
            'db_host' => 'text',
            'db_user' => 'text',
            'db_password' => 'text',
            'db_name' => 'text',
            self::SETTING_USER_FILTER => 'text',
            self::SETTING_URL_ID => 'text',
            self::SETTING_MOODLE_PATH => 'text',
        ];

        parent::__construct($version, $author, $settings);
    }

    /**
     * @return MigrationMoodlePlugin|null
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return Connection
     */
    public function getConnection()
    {
        if (!$this->hasRequiredDatabaseConfiguration()) {
            throw new \RuntimeException('Missing Moodle database configuration.');
        }

        $params = [
            'host' => $this->get('db_host'),
            'user' => $this->get('db_user'),
            'password' => $this->get('db_password'),
            'dbname' => $this->get('db_name'),
            'driver' => 'pdo_mysql',
            'charset' => 'utf8mb4',
        ];

        $connection = DriverManager::getConnection($params, new Configuration());

        return $connection;
    }

    public function hasRequiredDatabaseConfiguration(): bool
    {
        return '' !== trim((string) $this->get('db_host'))
            && '' !== trim((string) $this->get('db_user'))
            && '' !== trim((string) $this->get('db_name'));
    }

    /**
     * @return string
     */
    public function getUserFilterSetting()
    {
        $userFilter = $this->get(self::SETTING_USER_FILTER);

        return trim((string) $userFilter);
    }

    /**
     * @return int
     */
    public function getAccessUrlId()
    {
        $urlId = (int) $this->get(self::SETTING_URL_ID);

        return $urlId ?: 1;
    }

    /**
     * @return string
     */
    public function getMoodledataPath()
    {
        $path = $this->get(self::SETTING_MOODLE_PATH);

        return rtrim(trim((string) $path), ' /');
    }

    public function getAdminTaskMenu(): array
    {
        return [
            '_' => [
                'course_categories',
                'courses',
                'users',
            ],
            'courses' => [
                'course_introductions',
                'course_sections',
                'course_modules_scorm',
            ],
            'course_sections' => [
                'files_for_course_sections',
                'course_modules_lesson',
                'course_modules_quiz',
                'course_modules_url',
                'sort_section_modules',
            ],
            'course_modules_lesson' => [
                'lesson_pages',
            ],
            'lesson_pages' => [
                'lesson_pages_document',
                'lesson_pages_quiz',
            ],
            'lesson_pages_document' => [
                'files_for_lesson_pages',
            ],
            'lesson_pages_quiz' => [
                'lesson_pages_quiz_question',
                'files_for_lesson_answers',
            ],
            'lesson_pages_quiz_question' => [
                'lesson_answers_true_false',
                'lesson_answers_multiple_choice',
                'lesson_answers_multiple_answer',
                'lesson_answers_matching',
                'lesson_answers_essay',
                'lesson_answers_short_answer',
            ],
            'course_modules_quiz' => [
                'quizzes',
                'quizzes_scores',
            ],
            'quizzes' => [
                'files_for_quizzes',
                'question_categories',
                'questions',
            ],
            'questions' => [
                'question_multi_choice_single',
                'question_multi_choice_multiple',
                'questions_true_false',
                'question_short_answer',
                'question_gapselect',
            ],
            'course_modules_scorm' => [
                'scorm_scoes',
            ],
            'scorm_scoes' => [
                'files_for_scorm_scoes',
            ],
            'course_introductions' => [
                'files_for_course_introductions',
            ],
            'course_modules_url' => [
                'urls',
            ],
            'users' => [
                'users_last_login',
                'track_login',
                'user_sessions',
            ],
            'user_sessions' => [
                'users_learn_paths',
                'users_scorms_view',
                'track_course_access',
            ],
            'users_learn_paths' => [
                'users_learn_paths_lesson_timer',
                'users_learn_paths_lesson_branch',
                'users_learn_paths_lesson_attempts',
                'users_learn_paths_quizzes',
            ],
            'users_learn_paths_quizzes' => [
                'users_quizzes_attempts',
                'user_question_attempts_shortanswer',
                'user_question_attempts_gapselect',
                'user_question_attempts_truefalse',
            ],
        ];
    }

    public function getAdminScriptMenu(): array
    {
        return [
            '_' => [
                'user_learn_paths_progress',
                'user_scorms_progress',
            ],
        ];
    }

    public function getCliTaskNames(): array
    {
        return [
            'course_categories',
            'courses',
            'course_introductions',
            'files_for_course_introductions',
            'course_sections',
            'files_for_course_sections',
            'course_modules_lesson',
            'lesson_pages',
            'lesson_pages_document',
            'files_for_lesson_pages',
            'lesson_pages_quiz',
            'lesson_pages_quiz_question',
            'lesson_answers_true_false',
            'lesson_answers_multiple_choice',
            'lesson_answers_multiple_answer',
            'lesson_answers_matching',
            'lesson_answers_essay',
            'lesson_answers_short_answer',
            'files_for_lesson_answers',
            'course_modules_quiz',
            'quizzes',
            'files_for_quizzes',
            'question_categories',
            'questions',
            'question_multi_choice_single',
            'question_multi_choice_multiple',
            'questions_true_false',
            'question_short_answer',
            'question_gapselect',
            'quizzes_scores',
            'course_modules_url',
            'urls',
            'sort_section_modules',
            'course_modules_scorm',
            'scorm_scoes',
            'files_for_scorm_scoes',
            'users',
            'users_last_login',
            'track_login',
            'user_sessions',
            'users_learn_paths',
            'users_learn_paths_lesson_timer',
            'users_learn_paths_lesson_branch',
            'users_learn_paths_lesson_attempts',
            'users_learn_paths_quizzes',
            'users_quizzes_attempts',
            'user_question_attempts_shortanswer',
            'user_question_attempts_gapselect',
            'user_question_attempts_truefalse',
            'users_scorms_view',
            'track_course_access',
        ];
    }

    public function getCliScriptNames(): array
    {
        return [
            'user_learn_paths_progress',
            'user_scorms_progress',
        ];
    }


    public function ensureInternalTables(): void
    {
        static $ensured = false;

        if ($ensured) {
            return;
        }

        Database::query(
            "CREATE TABLE IF NOT EXISTS plugin_migrationmoodle_task (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(255) NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB"
        );

        Database::query(
            "CREATE TABLE IF NOT EXISTS plugin_migrationmoodle_item (
                id INT AUTO_INCREMENT NOT NULL,
                task_id INT NOT NULL,
                hash VARCHAR(255) NOT NULL,
                extracted_id INT NOT NULL,
                loaded_id INT NOT NULL,
                INDEX IDX_HASH (hash),
                INDEX IDX_EXTRACTED_LOADED (extracted_id, loaded_id),
                INDEX IDX_LOADED (loaded_id),
                INDEX IDX_TASK (task_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB"
        );

        if (!$this->migrationMoodleForeignKeyExists()) {
            Database::query(
                "ALTER TABLE plugin_migrationmoodle_item
                    ADD CONSTRAINT FK_TASK
                    FOREIGN KEY (task_id)
                    REFERENCES plugin_migrationmoodle_task (id)
                    ON DELETE CASCADE"
            );
        }

        $ensured = true;
    }

    private function migrationMoodleForeignKeyExists(): bool
    {
        $sql = "SELECT COUNT(*) AS total
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'plugin_migrationmoodle_item'
                  AND CONSTRAINT_NAME = 'FK_TASK'";
        $row = Database::fetch_assoc(Database::query($sql));

        return !empty($row) && (int) $row['total'] > 0;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isTaskDone($name)
    {
        $this->ensureInternalTables();

        $result = Database::select(
            'COUNT(1) c',
            'plugin_migrationmoodle_task',
            [
                'where' => [
                    'name = ?' => Database::escape_string($name.'_task'),
                    'or name = ?' => Database::escape_string($name.'_script'),
                ],
            ],
            'first'
        );

        return !empty($result) && (int) $result['c'] > 0;
    }
}
