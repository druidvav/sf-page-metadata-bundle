<?php
namespace Druidvav\PageMetadataBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class DvPageMetadataConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        if (method_exists('TreeBuilder', 'getRootNode')) {
            $treeBuilder = new TreeBuilder("dv_page_metadata");
            $rootNode = $treeBuilder->getRootNode();
        } else { // 3.4 version
            $treeBuilder = new TreeBuilder();
            $rootNode = $treeBuilder->root("dv_page_metadata");
        }

        $rootNode->
            children()->
                arrayNode('breadcrumbs')->
                    children()->
                        scalarNode("listId")->defaultValue("")->end()->
                        scalarNode("listClass")->defaultValue("")->end()->
                        scalarNode("itemClass")->defaultValue("")->end()->
                        scalarNode("linkRel")->defaultValue("")->end()->
                        scalarNode("locale")->defaultNull()->end()->
                        scalarNode("translation_domain")->defaultNull()->end()->
                        scalarNode("viewTemplate")->defaultValue("DvPageMetadataBundle::breadcrumbs/bootstrap.html.twig")->end()->
                    end()->
                end()->
                arrayNode('title')->
                    children()->
                        scalarNode("default")->defaultNull()->end()->
                        scalarNode("delimiter")->defaultValue(' - ')->end()->
                        scalarNode("locale")->defaultNull()->end()->
                        scalarNode("translation_domain")->defaultNull()->end()->
                    end()->
                end()->
                arrayNode('meta')->
                    children()->
                        scalarNode("description")->defaultNull()->end()->
                        scalarNode("keywords")->defaultNull()->end()->
                    end()->
                end()->
                arrayNode('opengraph')->
                    children()->
                        scalarNode("site_name")->defaultNull()->end()->
                        scalarNode("type")->defaultValue('website')->end()->
                        scalarNode("image")->defaultNull()->end()->
                        scalarNode("twitter_image")->defaultNull()->end()->
                        scalarNode("twitter_site")->defaultNull()->end()->
                    end()->
                end()->
            end()
        ;

        return $treeBuilder;
    }
}
