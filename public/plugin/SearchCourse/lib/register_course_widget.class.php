<?php
/* For licensing terms, see /license.txt */

class RegisterCourseWidget
{
    public const ACTION_SUBSCRIBE = 'subscribe';
    public const PARAM_SUBSCRIBE = 'subscribe';
    public const PARAM_PASSCODE = 'course_registration_code';

    public static function post(string $key, string $default = ''): string
    {
        return isset($_POST[$key]) ? trim((string) $_POST[$key]) : $default;
    }

    public static function factory(): self
    {
        return new self();
    }

    public function run(): string
    {
        $action = self::post('action');

        if (self::ACTION_SUBSCRIBE !== $action) {
            return '';
        }

        if (api_is_anonymous()) {
            return Display::return_message(get_lang('You must login to subscribe to a course'), 'warning');
        }

        if (class_exists('Security') && false === Security::check_token('post')) {
            return Display::return_message(get_lang('Invalid security token. Please reload the page and try again.'), 'error');
        }

        $courseCode = self::post(self::PARAM_SUBSCRIBE);

        if ('' === $courseCode) {
            return Display::return_message(get_lang('Course not found'), 'error');
        }

        $registrationCode = self::post(self::PARAM_PASSCODE);

        $result = $this->subscribeUser($courseCode, $registrationCode);

        if (true === $result['success']) {
            return Display::return_message(get_lang('You have been registered to the course'), 'confirmation');
        }

        if (true === $result['requires_code']) {
            return $this->displayForm($courseCode, $result['message']);
        }

        return Display::return_message($result['message'], 'error');
    }

    public function subscribeUser(string $courseCode, string $registrationCode = '', ?int $userId = null): array
    {
        $course = api_get_course_info($courseCode);

        if (empty($course)) {
            return [
                'success' => false,
                'requires_code' => false,
                'message' => get_lang('Course not found'),
            ];
        }

        $courseId = (int) ($course['real_id'] ?? $course['id'] ?? 0);

        if (0 === $courseId) {
            return [
                'success' => false,
                'requires_code' => false,
                'message' => get_lang('Course not found'),
            ];
        }

        $registrationCodeExpected = (string) ($course['registration_code'] ?? '');

        if ('' !== $registrationCodeExpected && $registrationCodeExpected !== $registrationCode) {
            return [
                'success' => false,
                'requires_code' => true,
                'message' => get_lang('The course password is incorrect'),
            ];
        }

        if (null === $userId) {
            $userId = api_get_user_id();
        }

        if (empty($userId)) {
            return [
                'success' => false,
                'requires_code' => false,
                'message' => get_lang('You must login to subscribe to a course'),
            ];
        }

        $result = CourseManager::subscribeUser(
            $userId,
            $courseId,
            STUDENT,
            0,
            0,
            true,
            [
                'result' => true,
                'flash' => false,
                'emails' => true,
            ]
        );

        if (is_array($result)) {
            return [
                'success' => (bool) ($result['ok'] ?? false),
                'requires_code' => false,
                'message' => (string) ($result['message'] ?? get_lang('Subscription failed')),
            ];
        }

        return [
            'success' => (bool) $result,
            'requires_code' => false,
            'message' => $result ? get_lang('You have been registered to the course') : get_lang('Subscription failed'),
        ];
    }

    public function displayForm(string $courseCode, string $message = ''): string
    {
        global $stok;

        $course = api_get_course_info($courseCode);

        if (empty($course)) {
            return Display::return_message(get_lang('Course not found'), 'error');
        }

        $safeCode = SearchCourseWidget::escape((string) ($course['code'] ?? $courseCode));
        $visualCode = SearchCourseWidget::escape((string) ($course['visual_code'] ?? $courseCode));
        $title = SearchCourseWidget::escape((string) ($course['title'] ?? ''));
        $token = SearchCourseWidget::escape((string) $stok);
        $messageHtml = '';

        if ('' !== $message) {
            $messageHtml = Display::return_message($message, 'warning');
        }

        return $messageHtml.'
            <div class="rounded-2xl border border-gray-20 bg-white p-4 shadow-sm">
                <h3 class="mb-2 text-lg font-semibold">'.SearchCourseWidget::escape(get_lang('This course requires a password')).'</h3>
                <p class="mb-4 text-sm text-gray-60">'.$visualCode.' - '.$title.'</p>
                <form method="post" action="'.SearchCourseWidget::escape(SearchCourseWidget::getSearchPageUrl()).'" class="flex flex-wrap items-end gap-3">
                    <input type="hidden" name="sec_token" value="'.$token.'">
                    <input type="hidden" name="action" value="'.self::ACTION_SUBSCRIBE.'">
                    <input type="hidden" name="'.self::PARAM_SUBSCRIBE.'" value="'.$safeCode.'">
                    <label class="flex flex-col gap-1 text-sm">
                        <span>'.SearchCourseWidget::escape(get_lang('Course registration code')).'</span>
                        <input class="rounded-lg border border-gray-25 px-3 py-2" type="password" name="'.self::PARAM_PASSCODE.'" value="">
                    </label>
                    <button class="btn btn--primary" type="submit">'.SearchCourseWidget::escape(get_lang('Subscribe')).'</button>
                </form>
            </div>';
    }
}
