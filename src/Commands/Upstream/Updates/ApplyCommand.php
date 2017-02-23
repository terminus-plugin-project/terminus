<?php

namespace Pantheon\Terminus\Commands\Upstream\Updates;

use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class ApplyCommand
 * @package Pantheon\Terminus\Commands\Upstream\Updates
 */
class ApplyCommand extends UpdatesCommand
{

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
        die(print_r($targets,true));

        $updates = $this->getUpstreamUpdatesLog($env);
        $count = count($updates);
        if ($count) {
            $this->log()->notice(
                'Applying {count} upstream update(s) to the {env} environment of {site_id}...',
                ['count' => $count, 'env' => $env->id, 'site_id' => $site->getName(),]
            );

            $workflow = $env->applyUpstreamUpdates(
                isset($options['updatedb']) ? $options['updatedb'] : false,
                isset($options['accept-upstream']) ? $options['accept-upstream'] : false
            );

            while (!$workflow->checkProgress()) {
                // @TODO: Add Symfony progress bar to indicate that something is happening.
            }
            $this->log()->notice($workflow->getMessage());
        } else {
            $this->log()->warning('There are no available updates for this site.');
        }
    }

    /**
     * @param string $site_env
     * @param boolean $all
     * @return Environment[]
     */
    protected function acquireTargets($site_env, $all)
    {
        $targets = [];
        list($site, $env) = $this->getOptionalSiteEnv($site_env);
        if (is_null($site)) {
            if ($all) {
                foreach ($this->sites()->all() as $site) {
                    $targets = array_merge($targets, $site->getEnvironments()->filterForDevelopment()->ids());
                }
            }
        } else if (is_null($env)) {
            $targets = $site->getEnvironments()->filterForDevelopment()->ids();
        } else {
            if (!$env->isDevelopment()) {
                throw new TerminusException(
                    'Upstream updates cannot be applied to the {env} environment',
                    ['env' => $env->id,]
                );
            }
            $targets[] = $env->id;
        }
        return $targets;
    }
}
