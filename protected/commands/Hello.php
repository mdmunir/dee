<?php

namespace app\commands;

use dee\base\Controller;

/**
 * Description of Hello
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class Hello extends Controller
{

    public function actionIndex($name = 'World')
    {
        echo "Hello $name\n";
    }
}
