<?php

namespace Pantheon\Terminus\UpstreamUpdate;

/**
 * Class WorkflowQueue
 * @package Pantheon\Terminus\UpstreamUpdate
 */
class WorkflowQueue extends \SplQueue
{
    /**
     * @return boolean
     */
    public function inProgress()
    {
        $this->poll();
        for ($this->rewind(); $this->valid(); $this->next()) {
            $workflow = $this->current();
            if (!$workflow->isFinished()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks on all unfinished workflows' statuses
     */
    public function poll()
    {
        for ($this->rewind(); $this->valid(); $this->next()) {
            $workflow = $this->current();
            if (!$workflow->isFinished()) {
                $workflow->fetch();
            }
        }
    }

    /**
     * @return mixed
     */
    public function __toString()
    {
        $statuses = [];
        for ($this->rewind(); $this->valid(); $this->next()) {
            $workflow = $this->current();
            $statuses[$workflow->getOwner()] = $workflow->getStatus();
        }
        return print_r($statuses, true);
    }
}