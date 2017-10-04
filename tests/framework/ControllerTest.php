<?php

namespace tests\framework;

/**
 * Description of ControllerTest
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class ControllerTest extends \tests\TestCase
{
    public function testRun()
    {
        $controller = new controllers\MyController('my');

        $this->assertEquals('index', $controller->run());
        $this->assertEquals('hallo cak', $controller->run('hello'));
    }

    public function testRender()
    {

    }
}
