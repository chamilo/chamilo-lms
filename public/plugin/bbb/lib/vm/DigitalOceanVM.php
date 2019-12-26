<?php

/* For licensing terms, see /license.txt */

use DigitalOcean\Credentials;
use DigitalOcean\DigitalOcean;

/**
 * Class DigitalOceanVM.
 */
class DigitalOceanVM extends AbstractVM implements VirtualMachineInterface
{
    public function __construct($settings)
    {
        parent::__construct($settings);
        $this->connect();
    }

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

            if (isset($availableSizes->status) && 'OK' == $availableSizes->status) {
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

            if ('OK' == $dropletInfo->status) {
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
                throw new \Exception(' Id '.$this->vmId." doesn't exists.");
            }
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }

    /**
     * Turns off / resize / turns on.
     *
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
            ['size_id' => (int) $sizeId]
        );
        $this->addMessage('Resize droplet to size id: '.$sizeId);
        $this->waitForEvent($resizeDroplet->event_id);

        $powerOn = $droplets->powerOn($this->vmId);
        $this->waitForEvent($powerOn->event_id);
        $this->addMessage('Power on droplet #'.$this->vmId);
    }

    /**
     * Loops until an event answer 100 percentage.
     *
     * @param int $eventId
     */
    public function waitForEvent($eventId)
    {
        $events = $this->getConnector()->events();
        $status = false;
        while (false == $status) {
            $infoStatus = $events->show($eventId);
            if ('OK' == $infoStatus->status && 100 == $infoStatus->event->percentage) {
                $status = true;
            }
        }
    }

    public function runCron()
    {
        $this->resizeToMinLimit();
        echo $this->getMessageToString();
    }

    public function resizeToMaxLimit()
    {
        $this->resizeTo('max');
    }

    public function resizeToMinLimit()
    {
        $this->resizeTo('min');
    }
}
