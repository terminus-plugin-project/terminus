<?php

namespace Pantheon\Terminus\Commands\Upstream\Updates;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\UpstreamUpdate\WorkflowQueue;

/**
 * Class ApplyCommand
 * @package Pantheon\Terminus\Commands\Upstream\Updates
 */
class ApplyCommand extends UpdatesCommand implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Applies upstream updates to a site's development environment.
     *
     * @authorize
     *
     * @command upstream:updates:apply
     *
     * @param string $site_env Site & environment to access as `$site` and (optional) `$env`
     * @option boolean $updatedb Run update.php after update (Drupal only)
     * @option boolean $accept-upstream Attempt to automatically resolve conflicts in favor of the upstream
     * @option boolean $all Target all development environments of all sites. Does not override the $site_env parameter.
     *
     * @throws TerminusException
     *
     * @usage <site>.<env> Applies upstream updates to <site>'s <env> environment.
     * @usage <site> Applies upstream updates to all of <site>'s development environments.
     * @usage <site>.<env> --updatedb Applies upstream updates to <site>'s <env> environment and runs update.php after update.
     * @usage <site>.<env> --accept-upstream Applies upstream updates to <site>'s <env> environment and attempts to automatically resolve conflicts in favor of the upstream.
     * @usage --all Applies upstream updates to all the development environments of all sites the logged-in user may update.
     */
    public function applyUpstreamUpdates($site_env = null, $options = [
        'updatedb' => false,
        'accept-upstream' => false,
        'all' => false,
    ])
    {
        $targets = $this->acquireTargets($site_env, isset($options['all']) ? $options['all'] : false);
        $updatedb = isset($options['updatedb']) ? $options['updatedb'] : false;
        $accept_upstream = isset($options['accept-upstream']) ? $options['accept-upstream'] : false;
        $workflow_queue = $this->getContainer()->get(WorkflowQueue::class);

        if (empty($targets)) {
            $this->log()->notice('None of the targeted environments have updates to apply.');
            return;
        }

        foreach ($targets as $env) {
            $this->log()->info(
                'Applying available updates to the {env} environment of {site}.',
                ['env' => $env->id, 'site' => $env->getSite()->getName(),]
            );
            $workflow_queue->push($env->applyUpstreamUpdates($updatedb, $accept_upstream));
        }

        while ($workflow_queue->inProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->info($workflow_queue);
    }

    /**
     * @param string $site_env
     * @param boolean $all
     * @return Environment[]
     */
    protected function acquireTargets($site_env, $all)
    {
        $envs = [];
        list($site, $env) = $this->getOptionalSiteEnv($site_env);
        if (is_null($site)) {
            if ($all) {
                $this->log()->notice('This operation may take a long time to run.');
                $this->log()->info('Retrieving all sites.');
                $sites = $this->sites->all();
                $this->log()->info('{count} sites were found.', ['count' => count($sites),]);
                foreach ($sites as $site) {
                    $envs = array_merge($envs, $this->getAllDevEnvsForSite($site));
                }
            }
        } else if (is_null($env)) {
            $envs = $this->getAllDevEnvsForSite($site);
        } else {
            if (!$env->isDevelopment()) {
                throw new TerminusException(
                    'Upstream updates cannot be applied to the {env} environment',
                    ['env' => $env->id,]
                );
            }
            $envs[$env->id] = $env;
        }
        return $envs;
    }

    /**
     * @param Site $site
     * @return Environments[]
     */
    private function getAllDevEnvsForSite($site)
    {
        $this->log()->info('Retrieving all development environments of {site}.', ['site' => $site->getName(),]);
        return $site->getEnvironments()->filterForUpstreamUpdates()->all();
    }
}
