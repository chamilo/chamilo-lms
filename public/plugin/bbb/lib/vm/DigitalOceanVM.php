<?php
/* For licensing terms, see /license.txt */

use DigitalOcean\DigitalOcean;
use DigitalOcean\Credentials;

/**
 * Class DigitalOceanVM
 */
class DigitalOceanVM extends AbstractVM implements VirtualMachineInterface
{
    /**
     *
     */
    public function __construct($settings)
    {
        parent::__construct($settings);
        $this->connect();
    }

    /**
     * @inheritdoc
     */
    public function connect()
    {
        // Set up your credentials.
        $credentials = new Credentials($this->vmClientId, $this->apiKey);
        // Use the default adapter, CurlHttpAdapter.
        $this->connector = new DigitalOcean($credentials);

        // Or use BuzzHttpAdapter.
        //$this->connector = new DigitalOcean($credentials, new BuzzHttpAdapter());
    }

    /**
     * @return DigitalOcean
     */
    public function getConnector()
    {
        return $this->connector;
    }

    /**
     * @param string $type min or max
     */
    public function resizeTo($type = 'min')
    {
        try {
            $droplets = $this->getConnector()->droplets();
            $sizes = $this->getConnector()->sizes();
            $availableSizes = $sizes->getAll();

            if (isset($availableSizes->status) && $availableSizes->status == 'OK') {

                $minSizeIdExists = false;
                $maxSizeIdExists = false;

                foreach ($availableSizes->sizes as $size) {
                    if ($size->id == $this->vmMaxSize) {
                        $maxSizeIdExists = true;
                    }
                    if ($size->id == $this->vmMinSizeSize) {
                        $minSizeIdExists = true;
                    }
                }
                if ($maxSizeIdExists && $minSizeIdExists) {
                    throw new \Exception('Sizes are not well configured');
                }
            } else {
                throw new \Exception('Sizes not available');
            }

            // Returns all active droplets that are currently running in your account.
            //$allActive = $droplets->showAllActive();

            $dropletInfo = $droplets->show($this->vmId);

            if ($dropletInfo->status == 'OK') {
                switch ($type) {
                    case 'min':
                        if ($dropletInfo->droplet->size_id == $this->vmMinSize) {
                            // No resize
                            $this->addMessage(
                                'Nothing to execute. The size was already reduced.'
                            );
                        } else {
                            $this->resize($this->vmMinSize);
                        }

                        break;
                    case 'max':
                        if ($dropletInfo->droplet->size_id == $this->vmMaxSize) {
                            // No resize
                            $this->addMessage(
                                'Nothing to execute. The size was already boost.'
                            );
                        } else {
                            $this->resize($this->vmMaxSize);
                        }
                        break;
                }
            } else {
                throw new \Exception(" Id ".$this->vmId." doesn't exists.");
            }
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }

    /**
     * Turns off / resize / turns on
     * @param int $sizeId
     */
    public function resize($sizeId)
    {
        $droplets = $this->getConnector()->droplets();
        $dropletInfo = $droplets->show($this->vmId);

        $powerOff = $droplets->powerOff($this->vmId);

        $this->addMessage('Power off droplet #'.$this->vmId);

        $this->waitForEvent($powerOff->event_id);

        $this->addMessage('Current status: '.$dropletInfo->droplet->status);

        $resizeDroplet = $droplets->resize(
            $this->vmId,
            array('size_id' => intval($sizeId))
        );
        $this->addMessage('Resize droplet to size id: '.$sizeId);
        $this->waitForEvent($resizeDroplet->event_id);

        $powerOn = $droplets->powerOn($this->vmId);
        $this->waitForEvent($powerOn->event_id);
        $this->addMessage('Power on droplet #'.$this->vmId);

    }

    /**
     * Loops until an event answer 100 percentage
     * @param int $eventId
     */
    public function waitForEvent($eventId)
    {
        $events = $this->getConnector()->events();
        $status = false;
        while ($status == false) {
            $infoStatus = $events->show($eventId);
            if ($infoStatus->status == 'OK' && $infoStatus->event->percentage == 100) {
                $status = true;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function runCron()
    {
        $this->resizeToMinLimit();
        echo $this->getMessageToString();
    }

    /**
     * @inheritdoc
     */
    public function resizeToMaxLimit()
    {
        $this->resizeTo('max');
    }

    /**
     * @inheritdoc
     */
    public function resizeToMinLimit()
    {
        $this->resizeTo('min');
    }
}
