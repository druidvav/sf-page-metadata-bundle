<?php
namespace Druidvav\PageMetadataBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class DvPageMetadataExtension extends Extension
{
    /**
     * @param  array            $configs
     * @param  ContainerBuilder $container
     * @return void
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $this->loadConfiguration($configs, $container);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }

    /**
     * Loads the configuration in, with any defaults
     *
     * @param array $configs
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    protected function loadConfiguration(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new DvPageMetadataConfiguration(), $configs);
        $container->setParameter('page_metadata.options', $config);

        $optionDef = new Definition('Druidvav\PageMetadataBundle\PageMetadata');
        $optionDef->addArgument(new Reference('router'));
        $optionDef->addArgument(new Reference('translator'));
        if (!empty($config['title']['default'])) {
            $optionDef->addMethodCall('setTitle', [ $config['title']['default'] ]);
        }
        $optionDef->addMethodCall('setTitleDelimeter', [ $config['title']['delimeter'] ]);
        if (!empty($config['meta']['description'])) {
            $optionDef->addMethodCall('setMetaDescription', [ $config['meta']['description'] ]);
        }
        if (!empty($config['meta']['keywords'])) {
            $optionDef->addMethodCall('setMetaKeywords', [ $config['meta']['keywords'] ]);
        }
        $optionDef->setPublic(true);
        $container->setDefinition('page_metadata', $optionDef);
    }
}
