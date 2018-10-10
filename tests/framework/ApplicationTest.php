<?php

namespace tests\framework;

use Dee;

/**
 * Description of ApplicationTest
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class ApplicationTest extends \tests\TestCase
{

    public function testCreateController()
    {
        $this->mockApplication([
            'controllerNamespace' => __NAMESPACE__ . '\controllers'
        ]);

        $controller = Dee::$app->createController('my-controller', '');
        $this->assertNotFalse($controller);
        list($controller, $route) = $controller;
        $this->assertEquals('my-controller', $controller->id);
        $this->assertEquals('', $route);
    }

    public function testCreateUrl()
    {
        $this->mockApplication();
        Dee::$app->request->setBaseUrl('/');
        Dee::$app->request->setScriptUrl('index.php');

        $url = Dee::$app->createUrl('site/index');
        $this->assertEquals('index.php/site/index', $url);

        $url = Dee::$app->createUrl('site/index', ['page' => 'about']);
        $this->assertEquals('index.php/site/index?page=about', $url);

        $url = Dee::$app->createUrl('users/{id}', ['id' => 3426]);
        $this->assertEquals('index.php/users/3426', $url);

        $url = Dee::$app->createUrl('users/profile/{name}', ['name' => 'cak-munir']);
        $this->assertEquals('index.php/users/profile/cak-munir', $url);
    }

    public function testFilter()
    {
        $this->mockApplication([
            'controllerNamespace' => __NAMESPACE__ . '\controllers',
            'filters' => [
                'tests\framework\JsonFilter'
            ]
        ]);
        list($controller,) = Dee::$app->createController('my-controller', '');

        $output = $controller->run('json');
        $this->assertEquals(json_encode([3426, 'cak munir']), $output);
    }
}
