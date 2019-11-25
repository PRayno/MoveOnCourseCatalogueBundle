<?php
namespace PRayno\MoveOnCourseCatalogueBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class PRaynoMoveOnCourseCatalogueExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $definition = $container->getDefinition('prayno_moveon_course_catalogue.course');
        $definition->setArgument(0, $config["academic_periods"]);

        $definition = $container->getDefinition('prayno_move_on_course_catalogue.course.csv_course');
        $definition->setArgument(2, $config["csv"]);
        $definition->setArgument(3, $config["sub_institution"]);

        $definition = $container->getDefinition('prayno_move_on_course_catalogue.logic.move_on_process');
        $definition->setArgument(2, $config["update_courses_modified_by"]);
        $definition->setArgument(3, $config["course_identifier_regex"]);
    }

    public function getAlias()
    {
        return 'prayno_moveon_course_catalogue';
    }
}