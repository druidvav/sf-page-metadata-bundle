<?php
namespace Druidvav\PageMetadataBundle\DependencyInjection;

use Druidvav\PageMetadataBundle\PageMetadata;
use Druidvav\PageMetadataBundle\Templating\Helper\PageMetadataHelper;
use Druidvav\PageMetadataBundle\Twig\Extension\PageMetadataExtension;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DvPageMetadataExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $this->loadConfiguration($configs, $container);
    }

    protected function loadConfiguration(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new DvPageMetadataConfiguration(), $configs);
        $container->setParameter('page_metadata.options', $config);

        $optionDef = new Definition(PageMetadata::class);
        $optionDef->addArgument(new Reference('router'));
        $optionDef->addArgument(new Reference('translator'));
        if (!empty($config['title']['default'])) {
            $optionDef->addMethodCall('setPageTitle', [ $config['title']['default'] ]);
        }
        $optionDef->addMethodCall('setTitleDelimiter', [ $config['title']['delimiter'] ]);
        if (!empty($config['meta']['description'])) {
            $optionDef->addMethodCall('setMetaDescription', [ $config['meta']['description'] ]);
        }
        if (!empty($config['meta']['keywords'])) {
            $optionDef->addMethodCall('setMetaKeywords', [ $config['meta']['keywords'] ]);
        }
        if (!empty($config['opengraph']['type'])) {
            $optionDef->addMethodCall('setOgType', [ $config['opengraph']['type'] ]);
        }
        if (!empty($config['opengraph']['site_name'])) {
            $optionDef->addMethodCall('setOgSiteName', [ $config['opengraph']['site_name'] ]);
        }
        if (!empty($config['opengraph']['image'])) {
            $optionDef->addMethodCall('setOgImage', [ $config['opengraph']['image'] ]);
        }
        if (!empty($config['opengraph']['twitter_image'])) {
            $optionDef->addMethodCall('setOgTwitterImage', [ $config['opengraph']['twitter_image'] ]);
        }
        if (!empty($config['opengraph']['twitter_site'])) {
            $optionDef->addMethodCall('setOgTwitterSite', [$config['opengraph']['twitter_site']]);
        }
        $optionDef->setPublic(true);
        $container->setDefinition('page_metadata', $optionDef);

        $optionDef = new Definition(PageMetadataHelper::class);
        $optionDef->addArgument(new Reference('page_metadata'));
        $optionDef->addArgument(new Reference('templating'));
        $optionDef->addArgument($config);
        $optionDef->addTag('templating.helper', [ 'alias' => 'page_metadata' ]);
        $container->setDefinition(PageMetadataHelper::class, $optionDef);

        $optionDef = new Definition(PageMetadataExtension::class);
        $optionDef->addArgument(new Reference(PageMetadataHelper::class));
        $optionDef->addTag('twig.extension');
        $container->setDefinition(PageMetadataExtension::class, $optionDef);
    }
}
