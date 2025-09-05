<?php
/**
 * (c) Copyright Ascensio System SIA 2025.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * You may not use this file except in compliance with the License.
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
require_once __DIR__ . '/../../../main/inc/global.inc.php';

class OnlyofficeSettingsFormBuilder
{
    /**
     * Directory with layouts for banners/partials.
     */
    private const ONLYOFFICE_LAYOUT_DIR = '/Onlyoffice/layout/';

    /**
     * Build OnlyOffice plugin settings form.
     *
     * Adds:
     *  - "Connect to demo" checkbox
     *  - "Test connection" submit button (server-side check)
     *  - Quick test LINKS (healthcheck, API, preloader) opening in a new tab
     *
     * @param OnlyofficeAppsettings $settingsManager
     *
     * @return FormValidator
     */
    public static function buildSettingsForm(OnlyofficeAppsettings $settingsManager)
    {
        $plugin = $settingsManager->plugin;

        $plugin_info = $plugin->get_info();
        $form = $plugin_info['settings_form'];

        $demoData = $settingsManager->getDemoData();
        $demoAvailable = is_array($demoData) && array_key_exists('available', $demoData)
            ? (bool) $demoData['available']
            : false;

        $connectDemoCheckbox = $form->createElement(
            'checkbox',
            'connect_demo',
            '',
            $plugin->get_lang('connect_demo') ?: 'Connect to demo'
        );

        if (!$demoAvailable) {
            $message = $plugin->get_lang('demoPeriodIsOver') ?: 'Demo trial period is over.';
            $connectDemoCheckbox->setAttribute('disabled', 'disabled');
        } else {
            if ($settingsManager->useDemo()) {
                $message = $plugin->get_lang('demoUsingMessage') ?: 'Demo is currently active.';
                $connectDemoCheckbox->setChecked(true);
            } else {
                $message = $plugin->get_lang('demoPrevMessage') ?: 'You can enable a 30-day demo.';
            }
        }
        $demoServerMessageHtml = Display::return_message($message, 'info');
        $demoServerMessage = $form->createElement('html', $demoServerMessageHtml);

        $bannerTemplate = self::buildTemplate('get_docs_cloud_banner', [
            'docs_cloud_link'    => $settingsManager->getLinkToDocs(),
            'banner_title'       => $plugin->get_lang('DocsCloudBannerTitle') ?: 'ONLYOFFICE Docs Cloud',
            'banner_main_text'   => $plugin->get_lang('DocsCloudBannerMain') ?: 'Try Docs Cloud for production use.',
            'banner_button_text' => $plugin->get_lang('DocsCloudBannerButton') ?: 'Learn more',
        ]);
        $banner = $form->createElement('html', $bannerTemplate);

        $testBtn = $form->createElement('submit', 'test_connection', $plugin->get_lang('TestConnection') ?: 'Test connection');

        $docUrl = $settingsManager->getDocumentServerUrl();
        $docAvailable = $settingsManager->useDemo() || !empty($docUrl);
        if ($docAvailable) {
            $healthUrl    = $settingsManager->getDocumentServerHealthcheckUrl();
            $apiUrl       = $settingsManager->getDocumentServerApiUrl();
            $preloaderUrl = $settingsManager->getDocumentServerPreloaderUrl();

            $quickLinksHtml = '
            <div class="panel panel-default" style="margin-top:8px;">
              <div class="panel-heading"><strong>Quick checks</strong></div>
              <div class="panel-body">
                <ul style="margin-bottom:8px;">
                  <li><a href="'.Security::remove_XSS($healthUrl).'" target="_blank" rel="noopener">Healthcheck</a></li>
                  <li><a href="'.Security::remove_XSS($apiUrl).'" target="_blank" rel="noopener">API (api.js)</a></li>
                  <li><a href="'.Security::remove_XSS($preloaderUrl).'" target="_blank" rel="noopener">Preloader</a></li>
                </ul>
                <p style="margin:0;">
                  <a href="#" onclick="var b=document.querySelector(\'button[name=test_connection],input[name=test_connection]\'); if(b){b.click();} return false;">
                    Run server-side test now
                  </a>
                </p>
              </div>
            </div>';
        } else {
            $quickLinksHtml = '
            <div class="panel panel-default" style="margin-top:8px;">
              <div class="panel-heading"><strong>Quick checks</strong></div>
              <div class="panel-body">
                <p>No Document Server URL configured and demo is disabled. Enable <em>Connect to demo</em> or set a server URL.</p>
              </div>
            </div>';
        }
        $quickLinksBlock = $form->createElement('html', $quickLinksHtml);

        $anchorNames = ['submit_button', 'submit', 'save', 'save_settings'];
        $anchor = null;
        foreach ($anchorNames as $name) {
            if (method_exists($form, 'getElement') && $form->getElement($name)) {
                $anchor = $name;
                break;
            }
        }

        if ($anchor && method_exists($form, 'insertElementBefore')) {
            $form->insertElementBefore($banner, $anchor);
            $form->insertElementBefore($demoServerMessage, $anchor);
            $form->insertElementBefore($connectDemoCheckbox, $anchor);
            $form->insertElementBefore($testBtn, $anchor);
            $form->insertElementBefore($quickLinksBlock, $anchor);
        } else {
            if (method_exists($form, 'addElement')) {
                $form->addElement('html', $banner->toHtml());
                $form->addElement('html', $demoServerMessage->toHtml());
                $form->addElement($connectDemoCheckbox);
                $form->addElement($testBtn);
                $form->addElement('html', $quickLinksBlock->toHtml());
            }
            if (function_exists('error_log')) {
                error_log('[OnlyOffice] settings_form: submit anchor not found; appended elements at end');
            }
        }

        return $form;
    }

    /**
     * Validate OnlyOffice plugin settings form and persist values.
     *
     * @param OnlyofficeAppsettings $settingsManager
     *
     * @return OnlyofficePlugin
     */
    public static function validateSettingsForm(OnlyofficeAppsettings $settingsManager)
    {
        $plugin = $settingsManager->plugin;
        $plugin_info = $plugin->get_info();
        $form = $plugin_info['settings_form'];

        // Read submitted values from the form.
        $result = $form->getSubmitValues();
        unset($result['submit_button']);

        // Make posted values available as runtime overrides for SettingsManager.
        $settingsManager->newSettings = $result;

        // Detect "Test connection" (non-destructive).
        $testing = isset($result['test_connection']);

        // Checkbox may be absent in POST when unchecked.
        $connectDemo = (bool) ($result['connect_demo'] ?? false);

        if ($testing) {
            $outcome = self::runSelfTest($settingsManager);

            if ($outcome['ok']) {
                Display::addFlash(
                    Display::return_message('[OnlyOffice] Connection test passed: '.$outcome['message'], 'confirmation')
                );
            } else {
                Display::addFlash(
                    Display::return_message('[OnlyOffice] Connection test failed: '.$outcome['message'], 'error')
                );
            }

            header('Location: '.$plugin->getConfigLink());
            exit;
        }

        // Persisted flow: toggle demo with vendor trial rules.
        if (!$settingsManager->selectDemo($connectDemo)) {
            $errorMsg = $plugin->get_lang('demoPeriodIsOver');
            self::displayError($errorMsg, $plugin->getConfigLink());
        }

        // Validate connectivity if a custom server is set and demo is not used.
        $docUrl = $settingsManager->getDocumentServerUrl();
        if (!empty($docUrl) && !$connectDemo) {
            $httpClient = new OnlyofficeHttpClient();
            $jwtManager = new OnlyofficeJwtManager($settingsManager);
            $requestService = new OnlyofficeAppRequests($settingsManager, $httpClient, $jwtManager);

            // checkDocServiceUrl() returns [error|null, version|null]
            [$error, $version] = $requestService->checkDocServiceUrl();
            if (!empty($error)) {
                $versionStr = !empty($version) ? '(Version '.$version.')' : '';
                $errorMsg = $plugin->get_lang('connectionError').'('.$error.')'.$versionStr;
                self::displayError($errorMsg);
            }
        }

        return $plugin;
    }

    /**
     * Run a minimal self-test using the vendor SettingsManager + existing request service.
     *
     * @param OnlyofficeAppsettings $settings
     *
     * @return array{ok: bool, message: string}
     */
    private static function runSelfTest(OnlyofficeAppsettings $settings): array
    {
        try {
            $docUrl = $settings->getDocumentServerUrl();
            if (empty($docUrl) && !$settings->useDemo()) {
                return [
                    'ok' => false,
                    'message' => 'No Document Server URL set and demo mode is disabled.',
                ];
            }

            $httpClient = new OnlyofficeHttpClient();
            $jwtManager = new OnlyofficeJwtManager($settings);
            $requestService = new OnlyofficeAppRequests($settings, $httpClient, $jwtManager);

            [$error, $version] = $requestService->checkDocServiceUrl();

            if (!empty($error)) {
                $versionStr = !empty($version) ? ' (Version '.$version.')' : '';
                return [
                    'ok' => false,
                    'message' => 'checkDocServiceUrl() returned error: '.$error.$versionStr,
                ];
            }

            $healthUrl = method_exists($settings, 'getDocumentServerHealthcheckUrl')
                ? $settings->getDocumentServerHealthcheckUrl()
                : null;

            $versionStr = !empty($version) ? 'Version '.$version : 'Version unknown';
            $extra = $healthUrl ? ' | Healthcheck: '.$healthUrl : '';

            return [
                'ok' => true,
                'message' => 'Document Server is reachable. '.$versionStr.$extra,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Unexpected exception: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Build an HTML template chunk from plugin templates directory.
     *
     * @param string $templateName
     * @param array  $params
     *
     * @return string
     */
    private static function buildTemplate($templateName, $params = [])
    {
        // Template is used outside of full-page context; disable auto-wrapping.
        $tpl = new Template('', false, false, false, false, false, false);
        if (!empty($params)) {
            foreach ($params as $key => $param) {
                $tpl->assign($key, $param);
            }
        }

        return $tpl->fetch(self::ONLYOFFICE_LAYOUT_DIR.$templateName.'.tpl');
    }

    /**
     * Show an error flash and optionally redirect.
     *
     * @param string      $errorMessage
     * @param string|null $location
     *
     * @return void
     */
    private static function displayError($errorMessage, $location = null)
    {
        Display::addFlash(
            Display::return_message($errorMessage, 'error')
        );
        if (null !== $location) {
            header('Location: '.$location);
            exit;
        }
    }
}
