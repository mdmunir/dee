<?php

/**
 * Description of DController
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class DController
{
    public $layout = '/layouts/main';
    public $defaultAction = 'index';
    public $id;
    public $route;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function render($view, $params = [])
    {
        $dview = Dee::$app->view;
        if (strncmp($view, '/', 1)) {
            $view = '/' . $this->id . '/' . $view;
        }
        $content = $dview->render($view, $params);
        return $dview->render($this->layout, ['content' => $content]);
    }

    public function renderPartial($view, $params = [])
    {
        $dview = Dee::$app->view;
        if (strncmp($view, '/', 1)) {
            $view = '/' . $this->id . $view;
        }
        return $dview->render($view, $params);
    }

    public function redirect($route, $params = [])
    {
        if (strpos($route, '/') === false) {
            $route = $this->id . '/' . $route;
        }
        Dee::redirect($route, $params);
    }

    public function run($route, $params = [])
    {
        if ($route == '') {
            $route = $this->defaultAction;
        }
        $this->route = $this->id . '/' . $route;
        $action = 'action' . str_replace(' ', '', ucwords(str_replace('-', ' ', $route)));

        $reflection = new ReflectionMethod($this, $action);
        $args = [];
        foreach ($reflection->getParameters() as $param) {
            $name = $param->getName();
            if (array_key_exists($name, $params)) {
                $args[] = $params[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                throw new Exception("Missing parameter '{$name}'");
            }
        }
        return call_user_func_array([$this, $action], $args);
    }
}
