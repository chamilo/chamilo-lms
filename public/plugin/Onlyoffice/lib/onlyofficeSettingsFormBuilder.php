<?php
/**
 * (c) Copyright Ascensio System SIA 2025.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
require_once __DIR__.'/../../../main/inc/global.inc.php';

class OnlyofficeSettingsFormBuilder
{
    /**
     * Directory with layouts.
     */
    private const ONLYOFFICE_LAYOUT_DIR = '/onlyoffice/layout/';

    /**
     * Build OnlyofficePlugin settings form.
     *
     * @return FormValidator
     */
    public static function buildSettingsForm(OnlyofficeAppsettings $settingsManager)
    {
        $plugin = $settingsManager->plugin;
        $demoData = $settingsManager->getDemoData();
        $plugin_info = $plugin->get_info();
        $message = '';
        $connectDemoCheckbox = $plugin_info['settings_form']->createElement(
            'checkbox',
            'connect_demo',
            '',
            $plugin->get_lang('connect_demo')
        );
        if (true === !$demoData['available']) {
            $message = $plugin->get_lang('demoPeriodIsOver');
            $connectDemoCheckbox->setAttribute('disabled');
        } else {
            if ($settingsManager->useDemo()) {
                $message = $plugin->get_lang('demoUsingMessage');
                $connectDemoCheckbox->setChecked(true);
            } else {
                $message = $plugin->get_lang('demoPrevMessage');
            }
        }
        $demoServerMessageHtml = Display::return_message(
            $message,
            'info'
        );
        $bannerTemplate = self::buildTemplate('get_docs_cloud_banner', [
            'docs_cloud_link' => $settingsManager->getLinkToDocs(),
            'banner_title' => $plugin->get_lang('DocsCloudBannerTitle'),
            'banner_main_text' => $plugin->get_lang('DocsCloudBannerMain'),
            'banner_button_text' => $plugin->get_lang('DocsCloudBannerButton'),
        ]);
        $plugin_info['settings_form']->insertElementBefore($connectDemoCheckbox, 'submit_button');
        $demoServerMessage = $plugin_info['settings_form']->createElement('html', $demoServerMessageHtml);
        $plugin_info['settings_form']->insertElementBefore($demoServerMessage, 'submit_button');
        $banner = $plugin_info['settings_form']->createElement('html', $bannerTemplate);
        $plugin_info['settings_form']->insertElementBefore($banner, 'submit_button');

        return $plugin_info['settings_form'];
    }

    /**
     * Validate OnlyofficePlugin settings form.
     *
     * @param OnlyofficeAppsettings $settingsManager - Onlyoffice SettingsManager
     *
     * @return OnlyofficePlugin
     */
    public static function validateSettingsForm(OnlyofficeAppsettings $settingsManager)
    {
        $plugin = $settingsManager->plugin;
        $errorMsg = null;
        $plugin_info = $plugin->get_info();
        $result = $plugin_info['settings_form']->getSubmitValues();
        unset($result['submit_button']);
        $settingsManager->newSettings = $result;
        if (!$settingsManager->selectDemo(true === (bool) $result['connect_demo'])) {
            $errorMsg = $plugin->get_lang('demoPeriodIsOver');
            self::displayError($errorMsg, $plugin->getConfigLink());
        }
        if (!empty($settingsManager->getDocumentServerUrl())) {
            if (false === (bool) $result['connect_demo']) {
                $httpClient = new OnlyofficeHttpClient();
                $jwtManager = new OnlyofficeJwtManager($settingsManager);
                $requestService = new OnlyofficeAppRequests($settingsManager, $httpClient, $jwtManager);
                list($error, $version) = $requestService->checkDocServiceUrl();
                if (!empty($error)) {
                    $errorMsg = $plugin->get_lang('connectionError').'('.$error.')'.(!empty($version) ? '(Version '.$version.')' : '');
                    self::displayError($errorMsg);
                }
            }
        }

        return $plugin;
    }

    /**
     * Build HTML-template.
     *
     * @param string $templateName - template name (*.tpl)
     * @param array  $params       - parameters to assign
     *
     * @return string
     */
    private static function buildTemplate($templateName, $params = [])
    {
        $tpl = new Template('', false, false, false, false, false, false);
        if (!empty($params)) {
            foreach ($params as $key => $param) {
                $tpl->assign($key, $param);
            }
        }
        $parsedTemplate = $tpl->fetch(self::ONLYOFFICE_LAYOUT_DIR.$templateName.'.tpl');

        return $parsedTemplate;
    }

    /**
     * Display error messahe.
     *
     * @param string $errorMessage - error message
     * @param string $location     - header location
     *
     * @return void
     */
    private static function displayError($errorMessage, $location = null)
    {
        Display::addFlash(
            Display::return_message(
                $errorMessage,
                'error'
            )
        );
        if (null !== $location) {
            header('Location: '.$location);
            exit;
        }
    }
}
