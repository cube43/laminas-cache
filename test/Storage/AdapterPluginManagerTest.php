<?php

declare(strict_types=1);

namespace LaminasTest\Cache\Storage;

use Laminas\Cache\Exception\ExtensionNotLoadedException;
use Laminas\Cache\Storage\AdapterPluginManager;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\ServiceManager;
use Laminas\ServiceManager\Test\CommonPluginManagerTrait;
use PHPUnit\Framework\TestCase;

class AdapterPluginManagerTest extends TestCase
{
    use CommonPluginManagerTrait {
        testPluginAliasesResolve as commonPluginAliasesResolve;
    }

    /**
     * @dataProvider aliasProvider
     */
    public function testPluginAliasesResolve(string $alias, string $expected)
    {
        try {
            $this->commonPluginAliasesResolve($alias, $expected);
        } catch (ServiceNotCreatedException $e) {
            // if we get as far as "extension not loaded" we've hit the constructor: alias has resolved
            if (! $e->getPrevious() instanceof ExtensionNotLoadedException) {
                self::fail($e->getMessage());
            }
        }
        $this->addToAssertionCount(1);
    }

    protected function getPluginManager(): AdapterPluginManager
    {
        return new AdapterPluginManager(new ServiceManager());
    }

    public function testShareByDefaultAndSharedByDefault()
    {
        self::markTestSkipped('Support for servicemanager v2 is dropped.');
    }

    protected function getV2InvalidPluginException()
    {
        self::fail('Somehow, servicemanager v2 compatibility is being tested.');
    }

    protected function getInstanceOf(): string
    {
        return StorageInterface::class;
    }
}
