<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\UserRemoteService\UserRemoteService;
use Doctrine\ORM\OptimisticLockException;

class UserRemoteServicePlugin extends Plugin
{
    public const TABLE = 'plugin_user_remote_service';

    /**
     * UserRemoteServicePlugin constructor.
     */
    protected function __construct()
    {
        parent::__construct(
            '1.0',
            'Sébastien Ducoulombier',
            [
                'salt' => 'text',
                'hide_link_from_navigation_menu' => 'boolean',
            ]
        );
        $this->isAdminPlugin = true;
    }

    /**
     * Caches and returns a single instance.
     *
     * @return UserRemoteServicePlugin
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * Creates the plugin table.
     */
    public function install()
    {
        Database::query(
            sprintf(
                'create table if not exists %s (
    id int unsigned not null auto_increment primary key,
    title varchar(255) not null,
    url varchar(255) not null
)',
                Database::get_main_table(self::TABLE)
            )
        );
    }

    /**
     * Drops the plugin table.
     */
    public function uninstall()
    {
        Database::query('drop table if exists '.Database::get_main_table(self::TABLE));
    }

    /**
     * @return string the salt setting
     */
    public function salt()
    {
        return $this->get('salt');
    }

    /**
     * @return bool the value of hide_link_from_navigation_menu setting
     */
    public function get_hide_link_from_navigation_menu()
    {
        return $this->get('hide_link_from_navigation_menu');
    }

    /**
     * Retrieves the list of all services.
     *
     * @return UserRemoteService[]
     */
    public function getServices()
    {
        return Database::getManager()->getRepository(
            'Chamilo\PluginBundle\UserRemoteService\UserRemoteService'
        )->findAll();
    }

    /**
     * Adds a new service.
     *
     * @param string $title
     * @param string $url
     *
     * @throws OptimisticLockException
     */
    public function addService($title, $url)
    {
        $service = new UserRemoteService();
        $service->setTitle($title);
        $service->setURL($url);
        Database::getManager()->persist($service);
        Database::getManager()->flush();
    }

    /**
     * Removes a service.
     *
     * @param UserRemoteService $service
     *
     * @throws OptimisticLockException
     */
    public function removeService($service)
    {
        Database::getManager()->remove($service);
        Database::getManager()->flush();
    }

    /**
     * Updates a service.
     *
     * @param UserRemoteService $service
     *
     * @throws OptimisticLockException
     */
    public function updateService($service)
    {
        Database::getManager()->persist($service);
        Database::getManager()->flush();
    }

    /**
     * Returns the active service id.
     *
     * @return int|null
     */
    public function getActiveServiceId()
    {
        return array_key_exists('serviceId', $_REQUEST) ? intval($_REQUEST['serviceId']) : null;
    }

    /**
     * Generates the menu items to be appended to the navigation array.
     *
     * @see \return_navigation_array
     *
     * @return array menu items
     */
    public function getNavigationMenu()
    {
        $menu = [];
        $activeServiceId = $this->getActiveServiceId();
        foreach ($this->getServices() as $service) {
            $key = 'service_'.$service->getId();
            $current = $service->getId() == $activeServiceId;
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

    /**
     * Generates and handles submission of the creation form.
     *
     * @throws OptimisticLockException
     *
     * @return FormValidator
     */
    public function getCreationForm()
    {
        $form = new FormValidator('creationForm');
        $titleText = $form->addText('title', get_lang('ServiceTitle'));
        $urlText = $form->addText('url', get_lang('ServiceURL'));
        $form->addButtonCreate(get_lang('CreateService'));
        if ($form->validate()) {
            $this->addService($titleText->getValue(), $urlText->getValue());
        }

        return $form;
    }

    /**
     * Generates and handles submission of the service deletion form.
     *
     * @throws OptimisticLockException
     *
     * @return FormValidator
     */
    public function getDeletionForm()
    {
        $form = new FormValidator('deletionForm');
        $services = $this->getServices();
        $options = [];
        foreach ($services as $service) {
            $options[$service->getId()] = $service->getTitle();
        }
        $serviceIdSelect = $form->addSelect('serviceId', get_lang('ServicesToDelete'), $options);
        $serviceIdSelect->setMultiple(true);
        $form->addButtonDelete(get_lang('DeleteServices'));
        if ($form->validate()) {
            foreach ($serviceIdSelect->getValue() as $serviceId) {
                foreach ($services as $service) {
                    if ($service->getId() == $serviceId) {
                        $this->removeService($service);
                    }
                }
            }
        }

        return $form;
    }

    /**
     * Generates the service HTML table.
     *
     * @return string
     */
    public function getServiceHTMLTable()
    {
        $html = '';
        $services = $this->getServices();
        if (!empty($services)) {
            $table = new HTML_Table('class="table"');
            $table->addRow(
                [
                    get_lang('ServiceTitle'),
                    get_lang('ServiceURL'),
                    get_lang('RedirectAccessURL'),
                ],
                null,
                'th'
            );
            foreach ($services as $service) {
                $table->addRow([
                    $service->getTitle(),
                    $service->getURL(),
                    $service->getAccessURL($this->get_name()),
                ]);
            }
            $html = $table->toHtml();
        }

        return $html;
    }

    /**
     * Retrieves one service.
     *
     * @param int $id the service identifier
     *
     * @return UserRemoteService the service
     */
    public function getService($id)
    {
        return Database::getManager()->getRepository(
            'Chamilo\PluginBundle\UserRemoteService\UserRemoteService'
        )->find($id);
    }

    /**
     * Generates the iframe HTML element to load a service URL.
     *
     * @throws Exception on hash generation failure
     *
     * @return string the iframe HTML element
     */
    public function getIFrame()
    {
        $userInfo = api_get_user_info();

        return sprintf(
            '<div class="embed-responsive embed-responsive-16by9">
 <iframe class="embed-responsive-item" src="%s"></iframe>
</div>',
            $this->getService(
                $this->getActiveServiceId()
            )->getCustomUserURL($userInfo['username'], $userInfo['id'], $this->salt())
        );
    }

    /**
     * Generates the redirect user specific URL for redirection.
     *
     * @throws Exception on hash generation failure
     *
     * @return string the specific user redirect URL
     */
    public function getActiveServiceSpecificUserUrl()
    {
        $userInfo = api_get_user_info();

        return $this->getService(
                $this->getActiveServiceId()
            )->getCustomUserRedirectURL($userInfo['id'], $this->salt());
    }
}
