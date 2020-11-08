<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cache/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cache/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Cache\Pattern;

use Laminas\Cache;
use LaminasTest\Cache\Pattern\TestAsset\FailableCallback;
use LaminasTest\Cache\Pattern\TestAsset\TestCallbackCache;

/**
 * Test function
 * @see LaminasTest\Cache\Pattern\Foo::bar
 */
function bar()
{
    return call_user_func_array(__NAMESPACE__ . '\TestAsset\TestCallbackCache::bar', func_get_args());
}

/**
 * @group      Laminas_Cache
 */
class CallbackCacheTest extends CommonPatternTest
{
    // @codingStandardsIgnoreStart
    /**
     * @var \Laminas\Cache\Storage\StorageInterface
     */
    protected $_storage;
    // @codingStandardsIgnoreEnd

    public function setUp(): void
    {
        $this->_storage = new Cache\Storage\Adapter\Memory([
            'memory_limit' => 0
        ]);
        $this->_options = new Cache\Pattern\PatternOptions([
            'storage' => $this->_storage,
        ]);
        $this->_pattern = new Cache\Pattern\CallbackCache();
        $this->_pattern->setOptions($this->_options);

        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function getCommonPatternNamesProvider()
    {
        return [
            ['callback'],
            ['Callback'],
        ];
    }

    public function testCallEnabledCacheOutputByDefault()
    {
        $this->_testCall(
            __NAMESPACE__ . '\TestAsset\TestCallbackCache::bar',
            ['testCallEnabledCacheOutputByDefault', 'arg2']
        );
    }

    public function testCallDisabledCacheOutput()
    {
        $options = $this->_pattern->getOptions();
        $options->setCacheOutput(false);
        $this->_testCall(
            __NAMESPACE__ . '\TestAsset\TestCallbackCache::bar',
            ['testCallDisabledCacheOutput', 'arg2']
        );
    }

    public function testMagicFunctionCall()
    {
        $this->_testCall(
            __NAMESPACE__ . '\bar',
            ['testMagicFunctionCall', 'arg2']
        );
    }

    public function testGenerateKey()
    {
        $callback = __NAMESPACE__ . '\TestAsset\TestCallbackCache::emptyMethod';
        $args     = ['arg1', 2, 3.33, null];

        $generatedKey = $this->_pattern->generateKey($callback, $args);
        $usedKey      = null;
        $this->_options->getStorage()->getEventManager()->attach('setItem.pre', function ($event) use (&$usedKey) {
            $params = $event->getParams();
            $usedKey = $params['key'];
        });

        $this->_pattern->call($callback, $args);
        $this->assertEquals($generatedKey, $usedKey);
    }

    public function testCallInvalidCallbackException()
    {
        $this->expectException('Laminas\Cache\Exception\InvalidArgumentException');
        $this->_pattern->call(1);
    }

    public function testCallUnknownCallbackException()
    {
        $this->expectException('Laminas\Cache\Exception\InvalidArgumentException');
        $this->_pattern->call('notExiststingFunction');
    }

    /**
     * Running tests calling LaminasTest\Cache\Pattern\TestCallbackCache::bar
     * using different callbacks resulting in this method call
     */
    // @codingStandardsIgnoreStart
    protected function _testCall($callback, array $args)
    {
        // @codingStandardsIgnoreEnd
        $returnSpec = 'foobar_return(' . implode(', ', $args) . ') : ';
        $outputSpec = 'foobar_output(' . implode(', ', $args) . ') : ';

        // first call - not cached
        $firstCounter = TestCallbackCache::$fooCounter + 1;

        ob_start();
        ob_implicit_flush(0);
        $return = $this->_pattern->call($callback, $args);
        $data = ob_get_clean();

        $this->assertEquals($returnSpec . $firstCounter, $return);
        $this->assertEquals($outputSpec . $firstCounter, $data);

        // second call - cached
        ob_start();
        ob_implicit_flush(0);
        $return = $this->_pattern->call($callback, $args);
        $data = ob_get_clean();

        $this->assertEquals($returnSpec . $firstCounter, $return);
        $options = $this->_pattern->getOptions();
        if ($options->getCacheOutput()) {
            $this->assertEquals($outputSpec . $firstCounter, $data);
        } else {
            $this->assertEquals('', $data);
        }
    }

    /**
     * @group 4629
     * @return void
     */
    public function testCallCanReturnCachedNullValues()
    {
        $callback = new FailableCallback();
        $key      = $this->_pattern->generateKey($callback, []);
        $this->_storage->setItem($key, [null]);
        $value    = $this->_pattern->call($callback);
        $this->assertNull($value);
    }
}
