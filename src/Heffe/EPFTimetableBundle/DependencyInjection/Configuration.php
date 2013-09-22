<?php

namespace Heffe\EPFTimetableBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('heffe_epf_timetable');

        $rootNode->children()
            ->integerNode('weeks_to_sync')
                ->min(1)
                ->max(20)
                ->defaultValue(2)
                ->info('Number of weeks to sync forward')
            ->end()
            ->scalarNode('helato_next_url')
                ->defaultValue('')
                ->info('URL to week_next.php page')
            ->end()
            ->scalarNode('helato_weekday_url')
                ->defaultValue('')
                ->info('URL to planning_week_day.php')
            ->end()
            ->scalarNode('helato_login_url')
                ->defaultValue('')
                ->info('URL to portal autologin page (hint: page name is \'index_direct_2.php\')')
            ->end()
            ->scalarNode('validation_email_from')
                ->defaultValue('')
                ->info('The email address to send the confirmation email from')
            ->end()
            ->scalarNode('google_app_name')
                ->defaultValue('The application name, from the Google API Console')
            ->end()
            ->scalarNode('google_client_id')
                ->defaultValue('OAuth 2 client Id. You can get this from Google API Console')
            ->end()
            ->scalarNode('google_client_secret')
                ->defaultValue('OAuth 2 client secret. You can get this from Google API Console')
            ->end()
            ->scalarNode('google_developer_key')
                ->defaultValue('Google Developer Key.')
            ->end()
            ->scalarNode('google_redirect_uri')
                ->defaultValue('The URL the user should be redirected to after authing with Google')
            ->end()
        ->end();

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
