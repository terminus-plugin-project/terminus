<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Friends\EnvironmentInterface;
use Pantheon\Terminus\Friends\EnvironmentTrait;

/**
 * Class EnvironmentOwnedCollection
 * @package Pantheon\Terminus\Collections
 */
abstract class EnvironmentOwnedCollection extends TerminusCollection implements EnvironmentInterface
{
    use EnvironmentTrait;

    /**
     * @inheritdoc
     */
    public function __construct($options = [])
    {
        parent::__construct($options);
        $this->setEnvironment($options['environment']);
    }

    /**
     * @inheritdoc
     */
    public function getUrl()
    {
        return $this->replaceUrlTokens(parent::getUrl());
    }

    /**
     * @param $url
     * @return string
     */
    protected function replaceUrlTokens($url)
    {
        $tr = [
            '{environment_id}' => $this->getEnvironment()->id,
            '{site_id}' => $this->getEnvironment()->getSite()->id
        ];
        return strtr($url, $tr);
    }
}
