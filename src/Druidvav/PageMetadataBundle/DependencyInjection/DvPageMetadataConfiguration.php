<?php
namespace Druidvav\PageMetadataBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class DvPageMetadataConfiguration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder("dv_page_metadata");
        $rootNode = $treeBuilder->getRootNode();

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
                        scalarNode("viewTemplate")->defaultValue("DvPageMetadataBundle::breadcrumbs/bootstrap3.html.twig")->end()->
                    end()->
                end()->
                arrayNode('title')->
                    children()->
                        scalarNode("default")->defaultNull()->end()->
                        scalarNode("delimeter")->defaultValue(' - ')->end()->
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
            end()
        ;

        return $treeBuilder;
    }
}
