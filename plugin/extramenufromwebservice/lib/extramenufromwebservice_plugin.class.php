<?php
/* For licensing terms, see /license.txt */

/**
 * Define the ExtraMenuFromWebservice class as an extension of Plugin
 * install/uninstall the plugin.
 */
class ExtraMenuFromWebservicePlugin extends Plugin
{
    /**
     * ExtraMenuFromWebservice constructor.
     */
    protected function __construct()
    {
        $settings = [
            'tool_enable' => 'boolean',
            'authentication_url' => 'text',
            'authentication_email' => 'text',
            'authentication_password' => 'text',
            'normal_menu_url' => 'text',
            'mobile_menu_url' => 'text',
            'session_timeout' => 'text',
            'list_css_imports' => 'text',
            'list_fonts_imports' => 'text',
        ];

        parent::__construct(
            '0.1',
            'Borja Sanchez',
            $settings
        );
    }

    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    public function install()
    {
        return true;
    }

    public function uninstall()
    {
        $settings = [
            'tool_enable',
            'authentication_url',
            'authentication_email',
            'authentication_password',
            'normal_menu_url',
            'mobile_menu_url',
            'username_parameter',
            'session_timeout',
            'list_css_imports' => 'text',
            'list_fonts_imports' => 'text',
        ];

        $tableSettings = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
        $urlId = api_get_current_access_url_id();

        foreach ($settings as $variable) {
            $sql = "DELETE FROM $tableSettings WHERE variable = '$variable' AND access_url = $urlId";
            Database::query($sql);
        }
    }

    /**
     * Get a token through the WS indicated in plugin configuration.
     */
    public function getToken()
    {
        $response = [];
        $authenticationUrl = (string) $this->get('authentication_url');
        $authenticationEmail = (string) $this->get('authentication_email');
        $authenticationPassword = (string) $this->get('authentication_password');

        if (!empty($authenticationUrl) && !empty($authenticationEmail) && !empty($authenticationPassword)) {
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => $authenticationUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_NOSIGNAL => 1,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
                    "email": "'.$authenticationEmail.'",
                    "password": "'.$authenticationPassword.'"
                }',
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                ],
            ]);

            $curlResponse = curl_exec($curl);
            curl_close($curl);
            if (false !== $curlResponse) {
                $curlResponse = json_decode($curlResponse, true);

                if (isset($curlResponse['data']['data']['token'])) {
                    $response = $curlResponse['data']['data']['token'];
                }
            }
        }

        return $response;
    }

    /**
     * Get the menu from the WS indicated in plugin configuration.
     * */
    public function getMenu(
        string $token,
        string $userEmail,
        bool $isMobile = false
    ): array {
        $response = [];
        $menuUrl = $isMobile ? (string) $this->get('mobile_menu_url') : (string) $this->get('normal_menu_url');
        if (!empty($menuUrl) && !empty($token) && !empty($userEmail)) {
            $menuUrl = substr($menuUrl, -1) === '/' ? $menuUrl : $menuUrl.'/';
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => $menuUrl.$userEmail,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_NOSIGNAL => 1,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer $token",
                ],
            ]);

            $curlResponse = curl_exec($curl);
            if (false !== $curlResponse) {
                $curlResponse = json_decode($curlResponse, true);
                if (isset($curlResponse['data']['data']['html']['data'])) {
                    $response['html'] = $curlResponse['data']['data']['html']['data'];
                }
                if (isset($curlResponse['data']['data']['css']['data'])) {
                    $response['css'] = $curlResponse['data']['data']['css']['data'];
                }
                if (isset($curlResponse['data']['data']['js']['data'])) {
                    $response['js'] = $curlResponse['data']['data']['js']['data'];
                }
            }
            curl_close($curl);
        }

        return $response;
    }

    /**
     * Checks if the login token is expired.
     */
    public static function tokenIsExpired(int $tokenStartTime, int $pluginSessionTimeout): bool
    {
        $now = api_get_utc_datetime(null, false, true)->getTimestamp();

        return ($now - $tokenStartTime) > $pluginSessionTimeout;
    }

    /**
     * Get the list of CSS or fonts indicated in plugin configuration.
     */
    public static function getImports(string $list = '')
    {
        $importsArray = [];

        if (!empty($list)) {
            $importsArray = explode(";", $list);
        }

        return $importsArray;
    }
}
