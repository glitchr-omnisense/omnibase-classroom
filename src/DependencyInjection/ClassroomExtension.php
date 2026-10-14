<?php

namespace Base\Classroom\DependencyInjection;

use Base\Bundle\AbstractBaseExtension;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class ClassroomExtension extends AbstractBaseExtension implements PrependExtensionInterface
{
    /**
     * classroom.consent: whether the site has omnibase/consent, whose EMBEDS
     * feature the frames then wait for (Compiler\EmbedsConsentPass declares
     * it, classroom_consent() names it to the templates). Without it the
     * frames load with the page, as before.
     */
    public function prepend(ContainerBuilder $container): void
    {
        $container->setParameter('classroom.consent', $container->hasExtension('consent'));
    }

    public function getConfiguration(array $config, ContainerBuilder $container): ClassroomConfiguration
    {
        return new ClassroomConfiguration();
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        (new PhpFileLoader($container, new FileLocator(\dirname(__DIR__, 2).'/config')))->load('services.php');
        $configuration = new ClassroomConfiguration();
        $config = (new Processor())->processConfiguration($configuration, $configs);
        $this->setConfiguration($container, $config, $configuration->getTreeBuilder()->buildTree()->getName());
    }
}
