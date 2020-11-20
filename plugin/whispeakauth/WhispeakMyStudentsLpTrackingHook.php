<?php
/* For licensing terms, see /license.txt */

/**
 * Class WhispeakMyStudentsLpTrackingHook.
 */
class WhispeakMyStudentsLpTrackingHook extends HookObserver implements HookMyStudentsLpTrackingObserverInterface
{
    /**
     * WhispeakMyStudentsLpTrackingHook constructor.
     */
    protected function __construct()
    {
        parent::__construct(
            'plugin/whispeakauth/WhispeakAuthPlugin.php',
            'whispeakauth'
        );
    }

    /**
     * @return array
     */
    public function trackingHeader(HookMyStudentsLpTrackingEventInterface $hook)
    {
        if (false === WhispeakAuthPlugin::create()->isEnabled()) {
            return [];
        }

        return [
            'value' => WhispeakAuthPlugin::create()->get_lang('plugin_title'),
            'attrs' => ['class' => 'text-center'],
        ];
    }

    /**
     * @throws \Doctrine\ORM\Query\QueryException
     *
     * @return array
     */
    public function trackingContent(HookMyStudentsLpTrackingEventInterface $hook)
    {
        if (false === WhispeakAuthPlugin::create()->isEnabled()) {
            return [];
        }

        $data = $hook->getEventData();

        $totalCount = WhispeakAuthPlugin::countAllAttemptsInLearningPath($data['lp_id'], $data['student_id']);

        if (0 === $totalCount) {
            return [
                'value' => '-',
                'attrs' => ['class' => 'text-center'],
            ];
        }

        $successCount = WhispeakAuthPlugin::countSuccessAttemptsInLearningPath($data['lp_id'], $data['student_id']);

        $attrs = ['class' => 'text-center '];
        $attrs['class'] .= $successCount <= $totalCount / 2 ? 'text-danger' : 'text-success';

        return [
            'value' => Display::tag('strong', "$successCount / $totalCount"),
            'attrs' => $attrs,
        ];
    }
}
