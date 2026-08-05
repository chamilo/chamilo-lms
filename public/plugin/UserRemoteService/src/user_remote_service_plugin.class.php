<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\UserRemoteService\UserRemoteService;

class UserRemoteServicePlugin extends Plugin
{
    public const TABLE = 'plugin_user_remote_service';

    protected function __construct()
    {
        parent::__construct(
            '1.1',
            'Sébastien Ducoulombier',
            [
                'salt' => 'text',
                'hide_link_from_navigation_menu' => 'boolean',
            ]
        );

        $this->isAdminPlugin = true;
    }

    public static function create()
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    public function install()
    {
        Database::query(
            sprintf(
                'CREATE TABLE IF NOT EXISTS %s (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    url VARCHAR(255) NOT NULL
                )',
                Database::get_main_table(self::TABLE)
            )
        );
    }

    public function uninstall()
    {
        Database::query('DROP TABLE IF EXISTS '.Database::get_main_table(self::TABLE));
    }

    public function salt()
    {
        return trim((string) $this->get('salt'));
    }

    public function get_hide_link_from_navigation_menu()
    {
        return 'true' === (string) $this->get('hide_link_from_navigation_menu');
    }

    public function isConfigured()
    {
        return '' !== $this->salt();
    }

    public function getServices()
    {
        $table = Database::get_main_table(self::TABLE);
        $result = Database::query("SELECT id, title, url FROM $table ORDER BY title ASC, id ASC");
        $services = [];

        while ($row = Database::fetch_assoc($result)) {
            $service = new UserRemoteService();
            $service
                ->setId((int) $row['id'])
                ->setTitle((string) $row['title'])
                ->setURL((string) $row['url'])
            ;
            $services[] = $service;
        }

        return $services;
    }

    public function addService($title, $url)
    {
        $title = trim((string) $title);
        $url = $this->normalizeUrl((string) $url);

        if ('' === $title || !$this->isValidHttpUrl($url)) {
            return false;
        }

        $table = Database::get_main_table(self::TABLE);
        $title = Database::escape_string($title);
        $url = Database::escape_string($url);

        Database::query("INSERT INTO $table (title, url) VALUES ('$title', '$url')");

        return true;
    }

    public function removeService($service)
    {
        if ($service instanceof UserRemoteService) {
            $serviceId = (int) $service->getId();
        } else {
            $serviceId = (int) $service;
        }

        if (empty($serviceId)) {
            return false;
        }

        $table = Database::get_main_table(self::TABLE);
        Database::query("DELETE FROM $table WHERE id = $serviceId");

        return true;
    }

    public function updateService($service)
    {
        if (!$service instanceof UserRemoteService) {
            return false;
        }

        $serviceId = (int) $service->getId();
        $title = trim((string) $service->getTitle());
        $url = $this->normalizeUrl((string) $service->getURL());

        if (empty($serviceId) || '' === $title || !$this->isValidHttpUrl($url)) {
            return false;
        }

        $table = Database::get_main_table(self::TABLE);
        $title = Database::escape_string($title);
        $url = Database::escape_string($url);

        Database::query("UPDATE $table SET title = '$title', url = '$url' WHERE id = $serviceId");

        return true;
    }

    public function getActiveServiceId()
    {
        if (!isset($_GET['serviceId'])) {
            return null;
        }

        $serviceId = (int) $_GET['serviceId'];

        return $serviceId > 0 ? $serviceId : null;
    }

    public function getNavigationMenu()
    {
        if (!$this->isEnabled() || $this->get_hide_link_from_navigation_menu() || !$this->isConfigured() || api_is_anonymous()) {
            return [];
        }

        $menu = [];
        $activeServiceId = $this->getActiveServiceId();

        foreach ($this->getServices() as $service) {
            if (!$this->isValidHttpUrl($service->getURL())) {
                continue;
            }

            $key = 'service_'.$service->getId();
            $current = (int) $service->getId() === (int) $activeServiceId;

            $menu[$key] = [
                'key' => $key,
                'current' => $current ? 'active' : '',
                'url' => sprintf(
                    '%s%s/iframe.php?serviceId=%d',
                    api_get_path(WEB_PLUGIN_PATH),
                    $this->get_name(),
                    $service->getId()
                ),
                'title' => $service->getTitle(),
            ];
        }

        return $menu;
    }

    public function getServiceHTMLTable()
    {
        return $this->getAdminServicesTableHtml();
    }

    public function getCreationForm()
    {
        $form = new FormValidator('creationForm');
        $titleText = $form->addText('title', $this->get_lang('ServiceTitle'));
        $urlText = $form->addText('url', $this->get_lang('ServiceURL'));
        $form->addButtonCreate($this->get_lang('CreateService'));

        if ($form->validate()) {
            $this->addService($titleText->getValue(), $urlText->getValue());
        }

        return $form;
    }

    public function getDeletionForm()
    {
        $form = new FormValidator('deletionForm');
        $services = $this->getServices();
        $options = [];

        foreach ($services as $service) {
            $options[$service->getId()] = $service->getTitle();
        }

        $serviceIdSelect = $form->addSelect('serviceId', $this->get_lang('ServicesToDelete'), $options);
        $serviceIdSelect->setMultiple(true);
        $form->addButtonDelete($this->get_lang('DeleteServices'));

        if ($form->validate()) {
            foreach ($serviceIdSelect->getValue() as $serviceId) {
                $this->removeService((int) $serviceId);
            }
        }

        return $form;
    }

    public function getService($id)
    {
        $id = (int) $id;

        if (empty($id)) {
            return null;
        }

        $table = Database::get_main_table(self::TABLE);
        $result = Database::query("SELECT id, title, url FROM $table WHERE id = $id LIMIT 1");
        $row = Database::fetch_assoc($result);

        if (empty($row)) {
            return null;
        }

        $service = new UserRemoteService();

        return $service
            ->setId((int) $row['id'])
            ->setTitle((string) $row['title'])
            ->setURL((string) $row['url'])
        ;
    }

    public function getIFrame()
    {
        $service = $this->getService($this->getActiveServiceId());

        if (!$this->canUseService($service)) {
            return Display::return_message($this->get_lang('ServiceUnavailable'), 'warning');
        }

        $userInfo = api_get_user_info();
        $sourceUrl = $service->getCustomUserURL($userInfo['username'], $userInfo['id'], $this->salt());

        return sprintf(
            '<div class="rounded-2xl border border-gray-25 bg-white p-2 shadow-sm"><iframe class="h-[75vh] w-full rounded-xl border-0" src="%s" referrerpolicy="no-referrer-when-downgrade"></iframe></div>',
            $this->escape($sourceUrl)
        );
    }

    public function getActiveServiceSpecificUserUrl()
    {
        $service = $this->getService($this->getActiveServiceId());

        if (!$this->canUseService($service)) {
            return null;
        }

        $userInfo = api_get_user_info();

        return $service->getCustomUserRedirectURL($userInfo['id'], $this->salt());
    }

    public function handleAdminPost()
    {
        if ('POST' !== $_SERVER['REQUEST_METHOD']) {
            return;
        }

        $postedToken = isset($_POST['sec_token']) ? (string) $_POST['sec_token'] : '';

        if (!hash_equals($this->getAdminSecurityToken(), $postedToken)) {
            Display::addFlash(Display::return_message($this->get_lang('InvalidSecurityToken'), 'error'));
            $this->redirectToAdmin();
        }

        $action = isset($_POST['form_action']) ? (string) $_POST['form_action'] : '';

        if ('create_service' === $action) {
            $title = isset($_POST['title']) ? trim((string) $_POST['title']) : '';
            $url = isset($_POST['url']) ? trim((string) $_POST['url']) : '';

            if ('' === $title) {
                Display::addFlash(Display::return_message($this->get_lang('InvalidServiceTitle'), 'warning'));
                $this->redirectToAdmin();
            }

            if (!$this->isValidHttpUrl($this->normalizeUrl($url))) {
                Display::addFlash(Display::return_message($this->get_lang('InvalidServiceUrl'), 'warning'));
                $this->redirectToAdmin();
            }

            $this->addService($title, $url);
            Display::addFlash(Display::return_message($this->get_lang('ServiceCreated')));
            $this->redirectToAdmin();
        }

        if ('delete_service' === $action) {
            $serviceId = isset($_POST['service_id']) ? (int) $_POST['service_id'] : 0;

            if (!empty($serviceId)) {
                $this->removeService($serviceId);
                Display::addFlash(Display::return_message($this->get_lang('ServiceDeleted')));
            }

            $this->redirectToAdmin();
        }
    }

    public function getAdminPageHtml()
    {
        $html = '<div class="space-y-6">';

        if (!$this->isConfigured()) {
            $html .= Display::return_message($this->get_lang('MissingSaltWarning'), 'warning');
        }

        $html .= '<section class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">';
        $html .= '<div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">';
        $html .= '<div>';
        $html .= '<div class="text-sm font-semibold uppercase tracking-wide text-primary">'.$this->escape($this->get_title()).'</div>';
        $html .= '<h2 class="mt-1 text-2xl font-semibold text-gray-90">'.$this->escape($this->get_lang('ServiceManagement')).'</h2>';
        $html .= '<p class="mt-2 max-w-3xl text-sm text-gray-50">'.$this->escape($this->get_lang('RemoteServicesDescription')).'</p>';
        $html .= '</div>';
        $html .= '<a class="inline-flex items-center gap-2 rounded-lg border border-gray-25 bg-white px-4 py-2 text-sm font-semibold text-gray-90 hover:bg-gray-10" href="'.
            $this->escape(api_get_path(WEB_CODE_PATH).'admin/plugins.php').'">'.
            '<span class="mdi mdi-arrow-left ch-tool-icon" aria-hidden="true"></span>'.$this->escape(get_lang('Back to plugins')).'</a>';
        $html .= '</div>';
        $html .= '</section>';

        $html .= '<section class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">';
        $html .= '<h3 class="text-lg font-semibold text-gray-90">'.$this->escape($this->get_lang('AddRemoteService')).'</h3>';
        $html .= '<form class="mt-4 grid gap-4 md:grid-cols-[1fr_2fr_auto] md:items-end" method="post" action="'.$this->escape(api_get_self()).'">';
        $html .= '<input type="hidden" name="sec_token" value="'.$this->escape($this->getAdminSecurityToken()).'">';
        $html .= '<input type="hidden" name="form_action" value="create_service">';
        $html .= '<label class="block"><span class="mb-1 block text-sm font-semibold text-gray-70">'.$this->escape($this->get_lang('ServiceTitle')).'</span>';
        $html .= '<input class="w-full rounded-lg border border-gray-25 px-3 py-2 text-sm" type="text" name="title" required maxlength="255"></label>';
        $html .= '<label class="block"><span class="mb-1 block text-sm font-semibold text-gray-70">'.$this->escape($this->get_lang('ServiceURL')).'</span>';
        $html .= '<input class="w-full rounded-lg border border-gray-25 px-3 py-2 text-sm" type="url" name="url" required maxlength="255" placeholder="https://example.org/service"></label>';
        $html .= '<button class="btn btn--primary inline-flex items-center gap-2" type="submit">'.
            '<span class="mdi mdi-plus-box ch-tool-icon" aria-hidden="true"></span>'.$this->escape($this->get_lang('CreateService')).'</button>';
        $html .= '</form>';
        $html .= '</section>';

        $html .= '<section class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">';
        $html .= '<h3 class="text-lg font-semibold text-gray-90">'.$this->escape($this->get_lang('CurrentServices')).'</h3>';
        $html .= '<div class="mt-4">'.$this->getAdminServicesTableHtml().'</div>';
        $html .= '</section>';

        $html .= '</div>';

        return $html;
    }

    public function getAdminServicesTableHtml()
    {
        $services = $this->getServices();

        if (empty($services)) {
            return '<div class="rounded-xl border border-dashed border-gray-25 bg-gray-10 p-6 text-center text-sm text-gray-50">'.
                $this->escape($this->get_lang('NoServicesConfigured')).'</div>';
        }

        $html = '<div class="overflow-x-auto">';
        $html .= '<table class="w-full table-auto border-collapse text-left text-sm">';
        $html .= '<thead class="border-b border-gray-25 bg-gray-10 text-xs uppercase tracking-wide text-gray-50">';
        $html .= '<tr>';
        $html .= '<th class="px-4 py-3">'.$this->escape($this->get_lang('ServiceTitle')).'</th>';
        $html .= '<th class="px-4 py-3">'.$this->escape($this->get_lang('ServiceURL')).'</th>';
        $html .= '<th class="px-4 py-3">'.$this->escape($this->get_lang('RedirectAccessURL')).'</th>';
        $html .= '<th class="px-4 py-3 text-right">'.$this->escape($this->get_lang('Actions')).'</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody class="divide-y divide-gray-25">';

        foreach ($services as $service) {
            $redirectUrl = $service->getAccessURL($this->get_name());
            $iframeUrl = sprintf(
                '%s%s/iframe.php?serviceId=%d',
                api_get_path(WEB_PLUGIN_PATH),
                $this->get_name(),
                $service->getId()
            );
            $invalidUrl = !$this->isValidHttpUrl($service->getURL());

            $html .= '<tr class="align-top hover:bg-gray-10">';
            $html .= '<td class="px-4 py-3 font-semibold text-gray-90">'.$this->escape($service->getTitle()).'</td>';
            $html .= '<td class="px-4 py-3">';
            $html .= '<a class="text-primary hover:underline" href="'.$this->escape($service->getURL()).'" target="_blank" rel="noopener noreferrer">'.$this->escape($service->getURL()).'</a>';
            if ($invalidUrl) {
                $html .= '<div class="mt-1 inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-700">'.$this->escape($this->get_lang('InvalidServiceUrl')).'</div>';
            }
            $html .= '</td>';
            $html .= '<td class="px-4 py-3"><code class="break-all rounded bg-gray-10 px-2 py-1 text-xs text-gray-70">'.$this->escape($redirectUrl).'</code></td>';
            $html .= '<td class="px-4 py-3 text-right">';
            $html .= '<div class="inline-flex items-center gap-2">';
            $html .= '<a class="ch-tool-icon-button" href="'.$this->escape($iframeUrl).'" title="'.$this->escape($this->get_lang('OpenInIframe')).'">'.
                '<span class="mdi mdi-open-in-new ch-tool-icon" aria-hidden="true"></span><span class="sr-only">'.$this->escape($this->get_lang('OpenInIframe')).'</span></a>';
            $html .= '<a class="ch-tool-icon-button" href="'.$this->escape($redirectUrl).'" title="'.$this->escape($this->get_lang('OpenRedirect')).'">'.
                '<span class="mdi mdi-login-variant ch-tool-icon" aria-hidden="true"></span><span class="sr-only">'.$this->escape($this->get_lang('OpenRedirect')).'</span></a>';
            $html .= '<form method="post" action="'.$this->escape(api_get_self()).'" onsubmit="return confirm(\''.$this->escape($this->escapeJs(get_lang('Please confirm your choice'))).'\');">';
            $html .= '<input type="hidden" name="sec_token" value="'.$this->escape($this->getAdminSecurityToken()).'">';
            $html .= '<input type="hidden" name="form_action" value="delete_service">';
            $html .= '<input type="hidden" name="service_id" value="'.$this->escape((string) $service->getId()).'">';
            $html .= '<button class="ch-tool-icon-button" type="submit" title="'.$this->escape($this->get_lang('DeleteService')).'">'.
                '<span class="mdi mdi-delete ch-tool-icon text-red-600" aria-hidden="true"></span><span class="sr-only">'.$this->escape($this->get_lang('DeleteService')).'</span></button>';
            $html .= '</form>';
            $html .= '</div>';
            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    public function canUseService($service)
    {
        return $service instanceof UserRemoteService
            && $this->isEnabled()
            && $this->isConfigured()
            && $this->isValidHttpUrl($service->getURL())
        ;
    }

    public function isValidHttpUrl($url)
    {
        $url = trim((string) $url);

        if ('' === $url || false === filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true);
    }

    public function normalizeUrl($url)
    {
        return trim((string) $url);
    }

    private function getAdminSecurityToken()
    {
        if (empty($_SESSION['user_remote_service_admin_token'])) {
            $_SESSION['user_remote_service_admin_token'] = bin2hex(random_bytes(16));
        }

        return (string) $_SESSION['user_remote_service_admin_token'];
    }

    private function redirectToAdmin()
    {
        header('Location: '.api_get_self());
        exit;
    }

    private function escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    private function escapeJs($value)
    {
        return str_replace(["\\", "'"], ["\\\\", "\\'"], (string) $value);
    }
}
