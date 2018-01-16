<?php
/* For license terms, see /license.txt */
/**
 * ImsLtiTool
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
class ImsLtiTool
{
    private $id = 0;
    private $name = '';
    private $description = null;
    private $launchUrl = '';
    private $consumerKey = '';
    private $sharedSecret = '';
    private $customParams = null;
    private $isGlobal = false;

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getLaunchUrl()
    {
        return $this->launchUrl;
    }

    public function getConsumerKey()
    {
        return $this->consumerKey;
    }

    public function getSharedSecret()
    {
        return $this->sharedSecret;
    }

    public function getCustomParams()
    {
        return $this->customParams;
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    public function setLaunchUrl($launchUrl)
    {
        $this->launchUrl = $launchUrl;

        return $this;
    }

    public function setConsumerKey($consumerKey)
    {
        $this->consumerKey = $consumerKey;

        return $this;
    }

    public function setSharedSecret($sharedSecret)
    {
        $this->sharedSecret = $sharedSecret;

        return $this;
    }

    public function setCustomParams($customParams)
    {
        $this->customParams = $customParams;

        return $this;
    }

    public function save()
    {
        $parameters = [
            'name' => $this->name,
            'description' => $this->description,
            'launch_url' => $this->launchUrl,
            'consumer_key' => $this->consumerKey,
            'shared_secret' => $this->sharedSecret,
            'custom_params' => $this->customParams,
            'is_global' => $this->isGlobal
        ];

        if (!empty($this->id)) {
            Database::update(
                ImsLtiPlugin::TABLE_TOOL,
                $parameters,
                ['id' => $this->id]
            );

            return;
        }

        $this->id = Database::insert(ImsLtiPlugin::TABLE_TOOL, $parameters);
    }

    public static function fetch($id)
    {
        $result = Database::select(
            '*',
            ImsLtiPlugin::TABLE_TOOL,
            ['where' => [
                'id = ?' => intval($id)
            ]],
            'first'
        );

        if (empty($result)) {
            return null;
        }

        $tool = new self();
        $tool->id = $result['id'];
        $tool->name = $result['name'];
        $tool->description = $result['description'];
        $tool->launchUrl = $result['launch_url'];
        $tool->consumerKey = $result['consumer_key'];
        $tool->sharedSecret = $result['shared_secret'];
        $tool->customParams = $result['custom_params'];
        $tool->isGlobal = (boolean) $result['is_global'];

        return $tool;
    }

    public static function fetchAll()
    {
        return Database::select(
            '*',
            ImsLtiPlugin::TABLE_TOOL
        );
    }

    public function parseCustomParams()
    {
        $strings = $this->customParams;

        $foo = explode('=', $strings);

        return [
            'key' => 'custom_'.$foo[0],
            'value' => $foo[1]
        ];
    }

    public function setIsGlobal($isGlobal = true)
    {
        $this->isGlobal = $isGlobal;
    }

    public function isGlobal()
    {
        return $this->isGlobal;
    }
}
