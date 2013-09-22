<?php

namespace Heffe\EPFTimetableBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class HeffeEPFTimetableExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('heffe_epf_timetable.weeks_to_sync', $config['weeks_to_sync']);
        $container->setParameter('heffe_epf_timetable.helato_next_url', $config['helato_next_url']);
        $container->setParameter('heffe_epf_timetable.helato_weekday_url', $config['helato_weekday_url']);
        $container->setParameter('heffe_epf_timetable.helato_login_url', $config['helato_login_url']);
        $container->setParameter('heffe_epf_timetable.validation_email_from', $config['validation_email_from']);

        $container->setParameter('heffe_epf_timetable.google_app_name', $config['google_app_name']);
        $container->setParameter('heffe_epf_timetable.google_client_id', $config['google_client_id']);
        $container->setParameter('heffe_epf_timetable.google_client_secret', $config['google_client_secret']);
        $container->setParameter('heffe_epf_timetable.google_developer_key', $config['google_developer_key']);
        $container->setParameter('heffe_epf_timetable.google_redirect_uri', $config['google_redirect_uri']);


        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }
}
