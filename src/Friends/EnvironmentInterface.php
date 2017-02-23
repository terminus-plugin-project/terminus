<?php

namespace Pantheon\Terminus\Friends;

use Pantheon\Terminus\Models\Environment;

/**
 * Interface EnvironmentInterface
 * @package Pantheon\Terminus\Friends
 */
interface EnvironmentInterface
{
    /**
     * @return Environment Returns an Environment-type object
     */
    public function getEnvironment();

    /**
     * @return Workflows
     */
    public function getWorkflows();

    /**
     * @param Environment $environment
     */
    public function setEnvironment(Environment $environment);
}
