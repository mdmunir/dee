<?php

/**
 * Description of DApp
 *
 * @property DView $view
 * @property DDbConnection $db
 * @property DUser $user
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class DApp
{
    public $components = [
        'view' => ['class' => 'DView'],
        'user' => ['class' => 'DUser'],
        'db' => ['class' => 'DDbConnection']
    ];
    public $basePath;
    public $baseUrl;
    public $params = [];
    public $defaultRoute = 'site';

    public function __construct($config = [])
    {
        Dee::$app = $this;
        if (isset($config['components'])) {
            foreach ($config['components'] as $name => $value) {
                if (isset($this->components[$name])) {
                    $this->components[$name] = array_merge($this->components[$name], $value);
                } else {
                    $this->components[$name] = $value;
                }
            }
            unset($config['components']);
        }
        foreach ($config as $name => $value) {
            $this->$name = $value;
        }
        if ($this->basePath === null) {
            throw new Exception("'basePath' must be specified");
        }
    }

    public function __get($name)
    {
        if (isset($this->components[$name])) {
            $component = $this->components[$name];
            if (!is_object($component)) {
                return $this->components[$name] = Dee::createObject($component);
            }
            return $component;
        }
        throw new Exception("Component {$name} not exists");
    }

    public function run()
    {
        if ($this->baseUrl === null) {
            $this->baseUrl = $_SERVER['SCRIPT_NAME'];
        }

        $route = trim($_SERVER['PATH_INFO'], '/');
        if (empty($route)) {
            $route = $this->defaultRoute;
        }
        if (($pos = strpos($route, '/')) !== false) {
            $id = substr($route, 0, $pos);
            $route = substr($route, $pos + 1);
        } else {
            $id = $route;
            $route = '';
        }
        $class = str_replace(' ', '', ucwords(str_replace('-', ' ', $id))) . 'Controller';
        require ($this->basePath . "/controllers/{$class}.php");

        /* @var $controller DController */
        $controller = new $class($id);
        echo $controller->run($route);
    }
}
