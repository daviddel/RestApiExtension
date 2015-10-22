<?php

namespace DavidDel\RestApi\RestApiExtension\ServiceContainer;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition,
    Symfony\Component\DependencyInjection\ContainerBuilder;

use Behat\Testwork\ServiceContainer\Extension as ExtensionInterface;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\DependencyInjection\Definition;
use Behat\Behat\Context\ServiceContainer\ContextExtension;

/**
 * Mink extension for MailCatcher manipulation.
 *
 * @author Alexandre Salomé <alexandre.salome@gmail.com>
 */
class RestApiExtension implements ExtensionInterface
{
    const REST_API_ID = 'rest_api';

    /**
     * {@inheritdoc}
     */
    public function load(ContainerBuilder $container, array $config)
    {
        $container->setParameter('behat.rest_api.base_url', $config['base_url']);
        $this->loadContextInitializer($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    private function loadContextInitializer(ContainerBuilder $container)
    {
        $definition = new Definition('DavidDel\RestApi\RestApiExtension\ContextInitializer', array(
            '%behat.rest_api.base_url%'
        ));
        $definition->addTag(ContextExtension::INITIALIZER_TAG, array('priority' => 0));
        $container->setDefinition('rest_api.context_initializer', $definition);
    }

    /**
     * {@inheritdoc}
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
                ->scalarNode('base_url')->isRequired()->end()
            ->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getCompilerPasses()
    {
        return array();
    }

    /**
     * @return array
     */
    protected function loadEnvironmentConfiguration()
    {
        $config = array();

        if ($url = getenv('VIRTUAL_HOST')) {
            $config['base_url'] = $url;
        }

        return $config;
    }
    
    /**
     * {@inheritDoc}
     */
    public function getConfigKey()
    {
        return 'rest_api';
    }
    
    /**
     * {@inheritdoc}
     */
    public function initialize(ExtensionManager $extensionManager)
    {
    }
    
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
    }
}
