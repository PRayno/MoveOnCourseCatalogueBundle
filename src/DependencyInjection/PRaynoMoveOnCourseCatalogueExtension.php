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

        $definition = $container->getDefinition('prayno_moveon_course_catalogue.update_command');
        $definition->setArgument(0, new Reference($config['moveon_course_object']));
        $definition->setArgument(2, $config["csv"]);
    }

    public function getAlias()
    {
        return 'prayno_moveon_course_catalogue';
    }
}