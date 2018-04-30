<?php

namespace CytecTest\Cache\Storage\Adapter;

use Zend\Cache\StorageFactory;
use Zend\ServiceManager\Factory\InvokableFactory;

use Cytec\Cache\Storage\Adapter\Predis;

class PredisTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Predis
     */
    private $storage;

    public function setUp()
    {
        StorageFactory::getAdapterPluginManager()->setFactory(Predis::class, InvokableFactory::class);
        StorageFactory::getAdapterPluginManager()->setAlias('predis', Predis::class);

        $this->storage = StorageFactory::factory([
            'adapter' => [
                'name'    => 'predis',
                'options' => ['ttl' => 3600],
            ],
            'plugins' => ['serializer'],
        ]);

        parent::setUp();
    }

    public function tearDown()
    {
        if ($this->storage) {
            $this->storage->flush();
        }

        parent::tearDown();
    }

    public function testGetNonExistent()
    {
        $this->assertNull($this->storage->getItem('key'));
    }

    public function testPredisSerializer()
    {
        $value = ['test', 'of', 'array'];
        $this->storage->setItem('key', $value);
        $this->assertCount(count($value), $this->storage->getItem('key'), 'Problem with Redis serialization');
    }

    public function testPredisSetInt()
    {
        $key = 'key';
        $this->assertTrue($this->storage->setItem($key, 123));
        $this->assertEquals('123', $this->storage->getItem($key), 'Integer should be cast to string');
    }

    public function testPredisSetDouble()
    {
        $key = 'key';
        $this->assertTrue($this->storage->setItem($key, 123.12));
        $this->assertEquals('123.12', $this->storage->getItem($key), 'Integer should be cast to string');
    }

    public function testPredisSetNull()
    {
        $key = 'key';
        $this->assertTrue($this->storage->setItem($key, null));
        $this->assertEquals('', $this->storage->getItem($key), 'Null should be cast to string');
    }

    public function testPredisSetBoolean()
    {
        $key = 'key';
        $this->assertTrue($this->storage->setItem($key, true));
        $this->assertEquals('1', $this->storage->getItem($key), 'Boolean should be cast to string');
        $this->assertTrue($this->storage->setItem($key, false));
        $this->assertEquals('', $this->storage->getItem($key), 'Boolean should be cast to string');
    }

    public function testTouchItem()
    {
        $key = 'key';

        // no TTL
        $this->storage->getOptions()->setTtl(0);
        $this->storage->setItem($key, 'val');
        $this->assertEquals(0, $this->storage->getMetadata($key)['ttl']);
        $this->assertEquals('val', $this->storage->getItem($key));

        // touch with a specific TTL will add this TTL

        $ttl = 1000;
        $this->storage->getOptions()->setTtl($ttl);
        $this->assertTrue($this->storage->touchItem($key));
        $this->assertEquals($ttl, ceil($this->storage->getMetadata($key)['ttl']));
    }

    public function testGetItems()
    {
        $this->assertTrue($this->storage->setItem('key1', 1));
        $this->assertTrue($this->storage->setItem('key2', 2));

        $items = $this->storage->getItems(['key1', 'key2']);

        $this->assertArrayHasKey('key1', $items);
        $this->assertArrayHasKey('key2', $items);

        $this->assertEquals(1, $items['key1']);
        $this->assertEquals(2, $items['key2']);
    }

    public function testHasItem()
    {
        $key = 'key';
        $this->assertTrue($this->storage->setItem($key, true));
        $this->assertTrue($this->storage->hasItem($key));
        $this->assertFalse($this->storage->hasItem('non-existent-key'));
    }

    public function testSetMultipleItems()
    {
        $items = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
            'key4' => 'value4',
            'key5' => 'value5'
        ];

        $this->storage->setItems($items);

        foreach($items as $key=>$value)
        {
            $this->assertEquals($value, $this->storage->getItem($key));
        }
    }

    public function testDeleteItem()
    {
        $key = 'key';
        $this->assertTrue($this->storage->setItem($key, 'val'));
        $this->assertTrue($this->storage->removeItem($key));
        $this->assertFalse($this->storage->hasItem($key));
    }

    public function testDeleteItems()
    {
        $this->assertTrue($this->storage->setItem('key1', 1));
        $this->assertTrue($this->storage->setItem('key2', 2));

        $this->storage->removeItems(['key1', 'key2']);

        $this->assertFalse($this->storage->hasItem('key1'));
        $this->assertFalse($this->storage->hasItem('key2'));
    }

    public function testIncrementItem()
    {
        $key = 'key';
        $this->assertTrue($this->storage->setItem($key, 100));
        $this->assertEquals(200, $this->storage->incrementItem($key, 100));
    }

    public function testDecrementItem()
    {
        $key = 'key';
        $this->assertTrue($this->storage->setItem($key, 100));
        $this->assertEquals(0, $this->storage->decrementItem($key, 100));
    }

    public function testTotalSpace()
    {
        $this->assertGreaterThan(0, $this->storage->getTotalSpace());
    }

    public function testClearByPrefix()
    {
        
    }

    // TODO: Test clearByNamespace, clearByPrefix, flush, getTotalSpace, setTags, getTags, clearByTags
}