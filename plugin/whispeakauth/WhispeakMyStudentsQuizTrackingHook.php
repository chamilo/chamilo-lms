<?php
/* For licensing terms, see /license.txt */

/**
 * Class WhispeakMyStudentsQuizTrackingHook.
 */
class WhispeakMyStudentsQuizTrackingHook extends HookObserver implements HookMyStudentsQuizTrackingObserverInterface
{
    /**
     * WhispeakMyStudentsQuizTrackingHook constructor.
     */
    protected function __construct()
    {
        parent::__construct(
            'plugin/whispeakauth/WhispeakAuthPlugin.php',
            'whispeakauth'
        );
    }

    /**
     * Return an associative array this value and attributes.
     * <code>
     * [
     *     'value' => 'Users online',
     *     'attrs' => ['class' => 'text-center'],
     * ]
     * </code>.
     *
     * @return array
     */
    public function trackingHeader(HookMyStudentsQuizTrackingEventInterface $hook)
    {
        if (false === WhispeakAuthPlugin::create()->isEnabled()) {
            return [];
        }

        return [
            'value' => WhispeakAuthPlugin::create()->get_lang('plugin_title'),
            'attrs' => [
                'class' => 'text-center',
            ],
        ];
    }

    /**
     * Return an associative array this value and attributes.
     * <code>
     * [
     *     'value' => '5 connected users ',
     *     'attrs' => ['class' => 'text-center text-success'],
     * ]
     * </code>.
     *
     * @throws \Doctrine\ORM\Query\QueryException
     *
     * @return array
     */
    public function trackingContent(HookMyStudentsQuizTrackingEventInterface $hook)
    {
        if (false === WhispeakAuthPlugin::create()->isEnabled()) {
            return [];
        }
        $data = $hook->getEventData();

        $totalCount = WhispeakAuthPlugin::countAllAttemptsInQuiz($data['quiz_id'], $data['student_id']);

        if (0 === $totalCount) {
            return [
                'value' => '-',
                'attrs' => ['class' => 'text-center'],
            ];
        }

        $successCount = WhispeakAuthPlugin::countSuccessAttemptsInQuiz($data['quiz_id'], $data['student_id']);

        $attrs = ['class' => 'text-center '];
        $attrs['class'] .= $successCount <= $totalCount / 2 ? 'text-danger' : 'text-success';

        return [
            'value' => Display::tag('strong', "$successCount / $totalCount"),
            'attrs' => $attrs,
        ];
    }
}
