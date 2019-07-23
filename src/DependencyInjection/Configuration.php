<?php
namespace PRayno\MoveOnCourseCatalogueBundle\DependencyInjection;


use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('prayno_moveon_course_catalogue');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->scalarNode('moveon_course_object')->defaultValue("PRayno\MoveOnCourseCatalogueBundle\Course\MoveonCourse")->end()
                ->arrayNode('csv')
                    ->children()
                        ->scalarNode("delimiter")->defaultNull()->info('CSV delimiter (default is a tabulation)')->end()
                        ->arrayNode('latest_date_fields')->scalarPrototype()->info('Array of update date fields')->end()->end()
                        ->arrayNode('required_fields')->scalarPrototype()->info('Array of required fields for a CSV line to be processed')->end()->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}