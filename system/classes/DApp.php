<?php

/**
 * Description of DApp
 *
 * @property DView $view
 * @property DDbConnection $db
 * @property DUser $user
 * @property DRequest $request
 * @property DUrlManager $urlManager
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class DApp
{
    public $components = [
        'view' => ['class' => 'DView'],
        'user' => ['class' => 'DUser'],
        'db' => ['class' => 'DDbConnection'],
        'request' => ['class' => 'DRequest'],
        'urlManager' => ['class' => 'DUrlManager'],
    ];
    public $basePath;
    public $params = [];
    public $defaultRoute = 'site';
    public $showScriptName = true;
    public $imports = [];

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
        foreach ($this->imports as $path) {
            Dee::import($path);
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
        if (method_exists($this, 'get' . $name)) {
            $method = 'get' . $name;
            return $this->$method();
        }
        throw new Exception("Component {$name} not exists");
    }

    public function run()
    {
        list($route, $params) = $this->request->resolve();

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

        $class = str_replace(' ', '', ucwords(str_replace('-', ' ', $id))).'Controller';
        require ($this->basePath . "/controllers/{$class}.php");

        /* @var $controller DController */
        $controller = new $class($id);
        echo $controller->run($route, $params);
    }
    private $_pathInfo;
    private $_baseUrl;
    private $_scriptUrl;

    public function getPathInfo()
    {
        if ($this->_pathInfo === null) {
            $this->_scriptUrl = $_SERVER['SCRIPT_NAME'];
            $this->_baseUrl = dirname($this->_scriptUrl);
            $requestUri = $_SERVER['REQUEST_URI'];
            if (strpos($requestUri, $this->_scriptUrl) === 0) {
                $this->_pathInfo = substr($requestUri, strlen($this->_scriptUrl));
            } elseif (strpos($requestUri, $this->_baseUrl) === 0) {
                $this->_pathInfo = substr($requestUri, strlen($this->_baseUrl));
            } else {
                $this->_pathInfo = '';
            }
        }

        return $this->_pathInfo;
    }

    public function getBaseUrl()
    {
        return $this->_baseUrl;
    }

    public function getScriptUrl()
    {
        return $this->_scriptUrl;
    }
}
