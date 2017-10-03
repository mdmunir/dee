<?php

namespace tests\framework\controllers;

use dee\base\Controller;
/**
 * Description of MyControllers
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class MyController extends Controller
{
    public function actionIndex()
    {
        return 'index';
    }

    public function actionHello()
    {
        return 'hallo cak';
    }

    public function actionJson()
    {
        return [3426, 'cak munir'];
    }
}
