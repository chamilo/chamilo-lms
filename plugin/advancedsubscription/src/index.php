<?php
/* For license terms, see /license.txt */
/**
 * Index of the Buy Courses plugin courses list
 * @package chamilo.plugin.advancedsubscription
 */
/**
 *
 */

require_once __DIR__ . '/../config.php';
$plugin = AdvancedSubscriptionPlugin::create();
$data = isset($_REQUEST['data']) ?
    strlen($_REQUEST['data']) > 16 ?
        $plugin->decrypt($_REQUEST['data']) :
        null :
    null;
if (isset($data)) {
    if (is_string($data)) {
        $data = unserialize($data);
    }
    if (is_array($data)) {
        if (isset($data['template'])) {
            $template = '/advancedsubscription/views/' . $data['template'];
            $templateName = $plugin->get_lang('plugin_title');
            $tpl = new Template($templateName);
            $tplParams = array('user', 'student', 'students','superior', 'admin', 'session', 'signature', '_p', );
            foreach ($tplParams as $tplParam) {
                if (isset($data['superior'])) {
                    $tpl->assign($tplParam, $data[$tplParam]);
                }
            }
            $content = $tpl->fetch($template);
            $tpl->assign('content', $content);
            $tpl->display_one_col_template();
        } elseif ($data['action']) {
            switch($data['action']) {
                case ADV_SUB_ACTION_STUDENT_REQUEST:
                    $plugin->startSubscription($data['user']['id'], $data['session']['id'], $data);
                    $plugin->sendMail($data, $data['action']);
                    break;
                case ADV_SUB_ACTION_SUPERIOR_APPROVE:
                    $plugin->updateQueueStatus($data, ADV_SUB_QUEUE_STATUS_BOSS_APPROVED);
                    $plugin->sendMail($data, $data['action']);
                    break;
                case ADV_SUB_ACTION_SUPERIOR_DISAPPROVE:
                    $plugin->updateQueueStatus($data, ADV_SUB_QUEUE_STATUS_BOSS_DISAPPROVED);
                    $plugin->sendMail($data, $data['action']);
                    break;
                case ADV_SUB_ACTION_SUPERIOR_SELECT:
                    $plugin->updateQueueStatus($data, ADV_SUB_QUEUE_STATUS_BOSS_APPROVED);
                    $plugin->sendMail($data, $data['action']);
                    break;
                case ADV_SUB_ACTION_ADMIN_APPROVE:
                    $plugin->updateQueueStatus($data, ADV_SUB_QUEUE_STATUS_ADMIN_APPROVED);
                    $plugin->sendMail($data, $data['action']);
                    break;
                case ADV_SUB_ACTION_ADMIN_DISAPPROVE:
                    $plugin->updateQueueStatus($data, ADV_SUB_QUEUE_STATUS_BOSS_DISAPPROVED);
                    $plugin->sendMail($data, $data['action']);
                    break;
            }
        }
    }
}
