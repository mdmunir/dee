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
        $this->mockApplication();
        $controller = new controllers\MyController('my');
        $controller->layout = false;
        
        $content = $controller->render('@tests/framework/views/view1.php', ['param1' => 'Cak Munir']);
        $this->assertEquals("Hello Cak Munir.\n", $content);
    }
}
