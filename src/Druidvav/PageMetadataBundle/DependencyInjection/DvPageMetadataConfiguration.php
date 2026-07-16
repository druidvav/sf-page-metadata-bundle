<?php
namespace Druidvav\PageMetadataBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class DvPageMetadataConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder("dv_page_metadata");
        $treeBuilder->getRootNode()->
            children()->
                scalarNode('base_url')->isRequired()->cannotBeEmpty()->end()->
                arrayNode('canonical')->
                    addDefaultsIfNotSet()->
                    children()->
                        arrayNode('alternate_locales')->
                            scalarPrototype()->cannotBeEmpty()->end()->
                            defaultValue([ ])->
                        end()->
                    end()->
                end()->
                arrayNode('breadcrumbs')->
                    children()->
                        scalarNode("listId")->defaultValue("")->end()->
                        scalarNode("listClass")->defaultValue("")->end()->
                        scalarNode("itemClass")->defaultValue("")->end()->
                        scalarNode("linkRel")->defaultValue("")->end()->
                        scalarNode("locale")->defaultNull()->end()->
                        scalarNode("translation_domain")->defaultNull()->end()->
                        scalarNode("viewTemplate")->defaultValue("@DvPageMetadata/breadcrumbs/bootstrap.html.twig")->end()->
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
                arrayNode('structured_data')->
                    addDefaultsIfNotSet()->
                    children()->
                        booleanNode('enabled')->defaultTrue()->end()->
                        booleanNode('breadcrumbs')->defaultTrue()->end()->
                        variableNode('nodes')->
                            defaultValue([ ])->
                            validate()->
                                ifTrue(static fn ($value): bool => !is_array($value))->
                                thenInvalid('Structured data nodes must be an array.')->
                            end()->
                        end()->
                    end()->
                end()->
            end()
        ;

        return $treeBuilder;
    }
}
