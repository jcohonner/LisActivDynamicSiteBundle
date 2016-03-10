<?php

namespace LisActiv\Bundle\DynamicSiteBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class LisActivDynamicSiteExtension extends Extension implements PrependExtensionInterface
{

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator( __DIR__ . '/../Resources/config' )
        );

        // Base services override
        $loader->load( 'services.yml' );
        $loader->load( 'dynamicsite_parameters.yml' );
    }

    /**
     * Loads LisActivDynamicSiteExtension configuration.
     *
     * @param ContainerBuilder $container
     */
    public function prepend( ContainerBuilder $container )
    {

        //Get Bundle parameters
        $parameters = Yaml::parse( __DIR__ . '/../Resources/config/dynamicsite_parameters.yml' );
        $configFile = __DIR__ . '/../../../../..'. $parameters['parameters']['dynamicsite.config_file'];

        $config = Yaml::parse( $configFile );
        if (is_array($config)) {
            $container->prependExtensionConfig( 'ezpublish', $config );
            $container->addResource( new FileResource( $configFile ) );
        }

        //Just for test purpose, add simple template on site content
        $configFile = __DIR__ . '/../Resources/config/dynamicsite.yml';
        $config = Yaml::parse( file_get_contents( $configFile ) );
        $container->prependExtensionConfig( 'ezpublish', $config );
        $container->addResource( new FileResource( $configFile ) );
    }

}
