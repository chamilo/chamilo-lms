<?php
/* For licensing terms, see /license.txt */

/**
 * Class VM
 */
class VM
{
    protected $config;
    public $virtualMachine;

    /**
     * VM constructor.
     * @param $config
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param   bool    $checkEnabled   Check if, additionnally to being installed, the plugin is enabled
     * @return bool
     */
    public function isEnabled(bool $checkEnabled = false): bool
    {
        $config = $this->getConfig();

        if (!isset($config)) {

            return false;
        }

        if (!is_array($config)) {
            return false;
        }

        if (isset($config['enabled']) && $config['enabled']) {

            return true;
        }

        return false;
    }

    /**
     * @return VirtualMachineInterface
     */
    public function getVirtualMachine()
    {
        return $this->virtualMachine;
    }

    /**
     * @param VirtualMachineInterface $virtualMachine
     */
    public function setVirtualMachine(VirtualMachineInterface $virtualMachine)
    {
        $this->virtualMachine = $virtualMachine;
    }

    /**
     * @return VirtualMachineInterface
     */
    public function getVirtualMachineFromConfig()
    {
        $vmList = $this->config['vms'];

        foreach ($vmList as $vm) {
            if (isset($vm['enabled']) && $vm['enabled'] == true) {
                $className = $vm['name'].'VM';

                return new $className($vm);
                break;
            }
        }

        return false;
    }

    /**
     * Resize the VM to the max size
     */
    public function resizeToMaxLimit()
    {
        $virtualMachine = $this->getVirtualMachineFromConfig();
        $this->setVirtualMachine($virtualMachine);
        $virtualMachine->resizeToMaxLimit();
    }

    /**
     * Resize the VM to the min size
     */
    public function resizeToMinLimit()
    {
        $virtualMachine = $this->getVirtualMachineFromConfig();
        $this->setVirtualMachine($virtualMachine);
        $virtualMachine->resizeToMinLimit();
    }

    public function runCron()
    {
        $virtualMachine = $this->getVirtualMachineFromConfig();
        $this->setVirtualMachine($virtualMachine);

        $virtualMachine->runCron();
    }
}
