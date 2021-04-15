<?php

namespace App\Kuo\Bundle\ChainCommandBundle\Provider;

/**
 * Class ChainCommandConfigureProvider
 * @package App\Kuo\Bundle\ChainCommandBundle\Provider
 */
class ChainCommandConfigureProvider
{

    /** @var array */
    protected $configure;

    /**
     * ChainCommandConfigureProvider constructor.
     * @param array $configure
     */
    public function __construct(array $configure)
    {
        $this->configure = $configure;
    }

    /**
     * Get all command that is marked as a member
     * @return array
     */
    public function getAllMemberCommands()
    {
        $members = [];
        if (!empty($this->configure)) {
            foreach ($this->configure as $group) {
                if (isset($group['members'])) {
                    $members = $members + $group['members'];
                }
            }
        }

        return $members;
    }

    /**
     * Get all commands that mark as master.
     * @return array
     */
    public function getAllMasterCommands()
    {
        $masters = [];
        if (!empty($this->configure)) {
            foreach ($this->configure as $group) {
                if (isset($group['master'])) {
                    $masters[] = $group['master'];
                }
            }
        }

        return $masters;
    }

    /**
     * @param string $command
     * @return mixed|null
     */
    public function getMasterByMemberName(string $command)
    {
        $master = null;
        if (!empty($command) && !empty($this->configure)) {
            foreach ($this->configure as $group) {
                if (isset($group['members']) && in_array($command, $group['members'])) {
                    return isset($group['master']) ? $group['master'] : null;
                }
            }
        }

        return $master;
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function getMembersByMasterName(string $name)
    {
        $members = null;
        if (!empty($name)) {
            foreach ($this->configure as $group) {
                if (isset($group['master']) && $group['master'] === $name) {
                    return $group['members'];
                }
            }
        }

        return $members;
    }
}
