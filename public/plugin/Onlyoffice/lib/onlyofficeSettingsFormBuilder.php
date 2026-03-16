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
require_once __DIR__.'/../../../main/inc/global.inc.php';

class OnlyofficeSettingsFormBuilder
{
    /**
     * Directory with layouts for banners/partials.
     */
    private const ONLYOFFICE_LAYOUT_DIR = '/Onlyoffice/layout/';

    /**
     * Reserved form keys that must never be treated as plugin settings.
     */
    private const RESERVED_KEYS = [
        'submit',
        'submit_button',
        'save',
        'save_settings',
        'test_connection',
        'connect_demo',
        '_token',
    ];

    /**
     * Build OnlyOffice plugin settings form.
     *
     * @param OnlyofficeAppsettings $settingsManager
     *
     * @return FormValidator
     */
    public static function buildSettingsForm(OnlyofficeAppsettings $settingsManager)
    {
        $plugin = $settingsManager->plugin;

        $pluginInfo = $plugin->get_info();
        $form = $pluginInfo['settings_form'];

        $demoData = $settingsManager->getDemoData();
        $demoAvailable = is_array($demoData) && array_key_exists('available', $demoData)
            ? (bool) $demoData['available']
            : false;

        $connectDemoCheckbox = $form->createElement(
            'checkbox',
            'connect_demo',
            '',
            self::t($plugin, 'connect_demo', 'Connect to demo')
        );

        if (!$demoAvailable) {
            $message = self::t($plugin, 'demoPeriodIsOver', 'Demo trial period is over.');
            $connectDemoCheckbox->setAttribute('disabled', 'disabled');
        } else {
            if ($settingsManager->useDemo()) {
                $message = self::t($plugin, 'demoUsingMessage', 'Demo is currently active.');
                $connectDemoCheckbox->setChecked(true);
            } else {
                $message = self::t($plugin, 'demoPrevMessage', 'You can enable a 30-day demo.');
            }
        }
        $demoServerMessageHtml = Display::return_message($message, 'info');
        $demoServerMessage = $form->createElement('html', $demoServerMessageHtml);

        $bannerTemplate = self::buildTemplate('get_docs_cloud_banner', [
            'docs_cloud_link' => $settingsManager->getLinkToDocs(),
            'banner_title' => self::t($plugin, 'DocsCloudBannerTitle', 'ONLYOFFICE Docs Cloud'),
            'banner_main_text' => self::t($plugin, 'DocsCloudBannerMain', 'Try Docs Cloud for production use.'),
            'banner_button_text' => self::t($plugin, 'DocsCloudBannerButton', 'Learn more'),
        ]);
        $banner = $form->createElement('html', $bannerTemplate);

        $testBtn = $form->createElement(
            'submit',
            'test_connection',
            self::t($plugin, 'TestConnection', 'Test connection')
        );

        $docUrl = $settingsManager->getDocumentServerUrl();
        $docAvailable = $settingsManager->useDemo() || !empty($docUrl);
        if ($docAvailable) {
            $healthUrl = $settingsManager->getDocumentServerHealthcheckUrl();
            $apiUrl = $settingsManager->getDocumentServerApiUrl();
            $preloaderUrl = $settingsManager->getDocumentServerPreloaderUrl();

            $quickLinksHtml = '
            <div class="panel panel-default" style="margin-top:8px;">
              <div class="panel-heading"><strong>Quick checks</strong></div>
              <div class="panel-body">
                <ul style="margin-bottom:8px;">
                  <li><a href="'.Security::remove_XSS((string) $healthUrl).'" target="_blank" rel="noopener">Healthcheck</a></li>
                  <li><a href="'.Security::remove_XSS((string) $apiUrl).'" target="_blank" rel="noopener">API (api.js)</a></li>
                  <li><a href="'.Security::remove_XSS((string) $preloaderUrl).'" target="_blank" rel="noopener">Preloader</a></li>
                </ul>
                <p style="margin:0;">
                  <a href="#" onclick="var b=document.querySelector(\'button[name=test_connection],input[name=test_connection]\'); if (b) { b.click(); } return false;">
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
                <p>No Document Server URL configured and demo mode is disabled. Enable <em>Connect to demo</em> or set a server URL.</p>
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

            error_log('[OnlyOffice] settings_form: submit anchor not found; appended elements at end');
        }

        return $form;
    }

    /**
     * Validate OnlyOffice plugin settings form and prepare runtime values.
     *
     * Important:
     * - configure_plugin.php will persist the final form values after this method returns.
     * - This method must remain non-destructive on normal save.
     *
     * @param OnlyofficeAppsettings $settingsManager
     *
     * @return OnlyofficePlugin
     */
    public static function validateSettingsForm(OnlyofficeAppsettings $settingsManager)
    {
        $plugin = $settingsManager->plugin;
        $pluginInfo = $plugin->get_info();
        $form = $pluginInfo['settings_form'];

        $result = $form->getSubmitValues();
        if (!is_array($result)) {
            $result = [];
        }

        $connectDemo = self::toBool($result['connect_demo'] ?? false);
        $testing = array_key_exists('test_connection', $result);

        $runtimeSettings = self::sanitizeSubmittedValues($result, $form);

        // Make posted values available as runtime overrides for SettingsManager.
        $settingsManager->newSettings = $runtimeSettings;

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

        // Toggle demo state, but do not hard-crash the save flow.
        if (!$settingsManager->selectDemo($connectDemo)) {
            Display::addFlash(
                Display::return_message(
                    self::t($plugin, 'demoPeriodIsOver', 'Demo trial period is over.'),
                    'error'
                )
            );
        }

        // On normal save, connection validation must be non-blocking.
        // This allows local preparation before deploying to a public/staging environment.
        $docUrl = $settingsManager->getDocumentServerUrl();
        if (!empty($docUrl) && !$connectDemo) {
            $check = self::checkDocumentServer($settingsManager);

            if (!$check['ok']) {
                $warningMessage = '[OnlyOffice] Settings were accepted, but the connection check failed: '.$check['message'];
                Display::addFlash(
                    Display::return_message($warningMessage, 'warning')
                );
                error_log('[OnlyOffice] Save warning: '.$check['message']);
            }
        }

        return $plugin;
    }

    /**
     * Run a minimal self-test using the existing request service.
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

            $check = self::checkDocumentServer($settings);
            if (!$check['ok']) {
                return $check;
            }

            $healthUrl = method_exists($settings, 'getDocumentServerHealthcheckUrl')
                ? $settings->getDocumentServerHealthcheckUrl()
                : null;

            $versionStr = !empty($check['version']) ? 'Version '.$check['version'] : 'Version unknown';
            $extra = !empty($healthUrl) ? ' | Healthcheck: '.$healthUrl : '';

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
     * Perform a safe connection check against the Document Server.
     *
     * @param OnlyofficeAppsettings $settings
     *
     * @return array{ok: bool, message: string, version: string|null}
     */
    private static function checkDocumentServer(OnlyofficeAppsettings $settings): array
    {
        try {
            $httpClient = new OnlyofficeHttpClient();
            $jwtManager = new OnlyofficeJwtManager($settings);
            $requestService = new OnlyofficeAppRequests($settings, $httpClient, $jwtManager);

            $rawResult = $requestService->checkDocServiceUrl();
            $parsed = self::normalizeDocServiceCheckResult($rawResult);

            if (!empty($parsed['error'])) {
                $versionStr = !empty($parsed['version']) ? ' (Version '.$parsed['version'].')' : '';

                return [
                    'ok' => false,
                    'message' => $parsed['error'].$versionStr,
                    'version' => $parsed['version'],
                ];
            }

            return [
                'ok' => true,
                'message' => 'Connection check completed successfully.',
                'version' => $parsed['version'],
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Unexpected exception during connection check: '.$e->getMessage(),
                'version' => null,
            ];
        }
    }

    /**
     * Normalize the SDK result of checkDocServiceUrl().
     *
     * Expected shape is usually:
     *   [error|null, version|null]
     *
     * However, the plugin must tolerate malformed or unexpected values.
     *
     * @param mixed $result
     *
     * @return array{error: string, version: string|null}
     */
    private static function normalizeDocServiceCheckResult($result): array
    {
        $error = '';
        $version = null;

        if (is_array($result)) {
            $error = isset($result[0]) ? self::stringifyValue($result[0]) : '';
            $version = isset($result[1]) ? self::stringifyNullableValue($result[1]) : null;

            return [
                'error' => $error,
                'version' => $version,
            ];
        }

        if (is_string($result)) {
            return [
                'error' => $result,
                'version' => null,
            ];
        }

        if ($result === null || $result === false || $result === true) {
            return [
                'error' => '',
                'version' => null,
            ];
        }

        return [
            'error' => 'Unexpected response type from checkDocServiceUrl(): '.gettype($result),
            'version' => null,
        ];
    }

    /**
     * Remove reserved keys from submitted values.
     *
     * @param array         $values
     * @param FormValidator $form
     *
     * @return array
     */
    private static function sanitizeSubmittedValues(array $values, $form): array
    {
        $clean = $values;

        $formName = method_exists($form, 'getAttribute')
            ? ($form->getAttribute('name') ?: 'form')
            : 'form';

        $reservedKeys = self::RESERVED_KEYS;
        $reservedKeys[] = '_qf__'.$formName;

        foreach ($reservedKeys as $key) {
            if (array_key_exists($key, $clean)) {
                unset($clean[$key]);
            }
        }

        return $clean;
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
        $tpl = new Template('', false, false, false, false, false, false);
        if (!empty($params)) {
            foreach ($params as $key => $param) {
                $tpl->assign($key, $param);
            }
        }

        return $tpl->fetch(self::ONLYOFFICE_LAYOUT_DIR.$templateName.'.tpl');
    }

    /**
     * Translate a plugin key with a safe fallback.
     *
     * @param Plugin $plugin
     * @param string $key
     * @param string $fallback
     *
     * @return string
     */
    private static function t($plugin, string $key, string $fallback): string
    {
        try {
            if (!empty($plugin) && method_exists($plugin, 'get_lang')) {
                $value = $plugin->get_lang($key);
                if (is_string($value) && '' !== trim($value)) {
                    return $value;
                }
            }
        } catch (\Throwable $e) {
            error_log('[OnlyOffice] translation fallback for key '.$key.': '.$e->getMessage());
        }

        return $fallback;
    }

    /**
     * Convert common checkbox-like values to boolean.
     *
     * @param mixed $value
     *
     * @return bool
     */
    private static function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            return in_array($normalized, ['1', 'true', 'on', 'yes'], true);
        }

        return !empty($value);
    }

    /**
     * Convert a mixed value to string.
     *
     * @param mixed $value
     *
     * @return string
     */
    private static function stringifyValue($value): string
    {
        if (null === $value) {
            return '';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }

    /**
     * Convert a mixed value to nullable string.
     *
     * @param mixed $value
     *
     * @return string|null
     */
    private static function stringifyNullableValue($value): ?string
    {
        $stringValue = self::stringifyValue($value);

        return '' === $stringValue ? null : $stringValue;
    }
}
