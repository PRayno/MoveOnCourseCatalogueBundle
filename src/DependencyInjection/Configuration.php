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
                ->arrayNode('csv')
                    ->children()
                        ->scalarNode("delimiter")->defaultNull()->info('CSV delimiter (default is a tabulation)')->end()
                        ->arrayNode('latest_date_fields')->scalarPrototype()->info('Array of update date fields')->end()->end()
                        ->arrayNode('required_fields')->scalarPrototype()->info('Array of required fields for a CSV line to be processed')->end()->end()
                        ->arrayNode("excluded_lines")
                            ->prototype('array')
                                ->prototype('scalar')->end()
                            ->end()
                            ->defaultValue(array())
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('sub_institution')
                    ->children()
                        ->scalarNode("code_field")->info('MoveOn field bearing the common sub institution code with the CSV values')->end()
                        ->scalarNode("course_catalog_id_field")->defaultValue('institution.id')->info('MoveOn course catalog field for sub institution id')->end()
                        ->scalarNode("main_institution_id")->info('Parent institution id')->end()
                    ->end()
                ->end()
                ->arrayNode("academic_periods")
                    ->prototype('scalar')->end()
                    ->defaultValue(array())
                ->end()

                ->arrayNode("update_courses_modified_by")->scalarPrototype()->info('Only update courses modified by this list of users ["user1.firstname, user1.lastname","user2.firstname, user2.lastname"]')->end()->end()
                ->scalarNode("course_identifier_regex")->info('Regex of the identifier for synchronized courses (used for deactivation)')->end()
            ->end();

        return $treeBuilder;
    }
}