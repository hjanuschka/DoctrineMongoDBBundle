<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests;

use Doctrine\Bundle\MongoDBBundle\DependencyInjection\DoctrineMongoDBExtension;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use function sys_get_temp_dir;

class ContainerTest extends TestCase
{
    /** @var ContainerBuilder */
    private $container;

    /** @var DoctrineMongoDBExtension */
    private $extension;

    protected function setUp()
    {
        $this->container = new ContainerBuilder(new ParameterBag([
            'kernel.bundles'      => [],
            'kernel.cache_dir'    => sys_get_temp_dir(),
            'kernel.root_dir'     => sys_get_temp_dir(),
            'kernel.environment'  => 'test',
            'kernel.name'         => 'kernel',
            'kernel.debug'        => true,
        ]));

        $this->container->setDefinition('annotation_reader', new Definition(AnnotationReader::class));
        $this->extension = new DoctrineMongoDBExtension();
    }

    /**
     * @dataProvider provideLoggerConfigs
     */
    public function testLoggerConfig(bool $expected, array $config, bool $debug)
    {
        $this->container->setParameter('kernel.debug', $debug);
        $this->extension->load([$config], $this->container);

        $definition = $this->container->getDefinition('doctrine_mongodb.odm.command_logger');
        $this->assertSame($expected, $definition->hasMethodCall('register'));
    }

    public function provideLoggerConfigs()
    {
        $config = ['connections' => ['default' => []]];

        return [
            [
                // Logging is always enabled in debug mode
                true,
                [
                    'document_managers' => ['default' => []],
                ] + $config,
                true,
            ],
            [
                // Logging is disabled by default when not in debug mode
                false,
                [
                    'document_managers' => ['default' => []],
                ] + $config,
                false,
            ],
            [
                // Logging can be enabled by config
                true,
                [
                    'document_managers' => ['default' => ['logging' => true]],
                ] + $config,
                false,
            ],
        ];
    }

    /**
     * @dataProvider provideDataCollectorConfigs
     */
    public function testDataCollectorConfig(bool $expected, array $config, bool $debug)
    {
        $this->container->setParameter('kernel.debug', $debug);
        $this->extension->load([$config], $this->container);

        $loggerDefinition = $this->container->getDefinition('doctrine_mongodb.odm.data_collector.command_logger');
        $this->assertSame($expected, $loggerDefinition->hasMethodCall('register'));

        $dataCollectorDefinition = $this->container->getDefinition('doctrine_mongodb.odm.data_collector');
        $this->assertSame($expected, $dataCollectorDefinition->hasTag('data_collector'));
    }

    public function provideDataCollectorConfigs()
    {
        $config = ['connections' => ['default' => []]];

        return [
            [
                // Profiling is always enabled in debug mode
                true,
                [
                    'document_managers' => ['default' => []],
                ] + $config,
                true,
            ],
            [
                // Profiling is disabled by default when not in debug mode
                false,
                [
                    'document_managers' => ['default' => []],
                ] + $config,
                false,
            ],
            [
                // Profiling can be enabled by config
                true,
                [
                    'document_managers' => ['default' => ['profiler' => true]],
                ] + $config,
                false,
            ],
        ];
    }
}
