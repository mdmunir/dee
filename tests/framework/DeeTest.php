<?php

namespace tests\framework;

use Dee;

/**
 * Description of DeeTest
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class DeeTest extends \tests\TestCase
{

    public function testAlias()
    {
        $path = dirname(__DIR__);
        Dee::setAlias('@test', $path);

        $this->assertEquals("$path/runtime", Dee::getAlias('@test/runtime'));
        $this->assertEquals("$path/runtime/file.dat", Dee::getAlias('@test/runtime/file.dat'));
        $this->assertEquals(false, Dee::getAlias('@testku/runtime'));
    }

    public function testHash()
    {
        $key = 'valid-key';

        $hash = Dee::hashData('data satu', $key);
        $this->assertEquals('data satu', Dee::validateData($hash, $key));

        $hash = Dee::hashData('Kalau data yang panjang', $key);
        $this->assertEquals('Kalau data yang panjang', Dee::validateData($hash, $key));
        $this->assertEquals(false, Dee::validateData($hash, 'invalid-key'));
    }

    public function testCreateObject()
    {
        $config = [
            '__class' => 'StdClass',
            'field1' => 'satu',
            'field2' => 'dua',
        ];

        $object = Dee::createObject($config);
        $this->assertNotNull($object);
        $this->assertNotNull($object->field1);
        $this->assertEquals('dua', $object->field2);
    }
}
