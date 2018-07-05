<?php

namespace dee\base;

use Dee;
use Exception;

/**
 * Description of Application
 *
 * @property View $view
 * @property Connection $db
 * @property User $user
 * @property Request $request
 * @property Response $response
 * @property UrlManager $urlManager
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class Application
{
    public $components = [
        'view' => ['class' => 'dee\base\View'],
        'user' => ['class' => 'dee\base\User'],
        'db' => ['class' => 'dee\base\Connection'],
        'request' => ['class' => 'dee\base\Request'],
        'response' => ['class' => 'dee\base\Response'],
    ];
    public $basePath;
    public $params = [];
    public $defaultRoute = 'site';
    public $showScriptName = true;
    public $aliases = [];
    public $controllerNamespace = 'app\controllers';
    /**
     *
     * @var Controller 
     */
    public $controller;
    /**
     *
     * @var Filter[]
     */
    public $filters = [];
    private $_memoryReserve;

    public function __construct($config = [])
    {
        Dee::$app = $this;
        $this->registerErrorHandler();
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
        if ($this->controllerNamespace === null) {
            throw new Exception("'controllerNamespace' must be specified");
        }
        Dee::setAlias('@app', $this->basePath);
        foreach ($this->aliases as $alias => $path) {
            Dee::setAlias($alias, $path);
        }
        if (PHP_SAPI !== 'cli' && !isset(Dee::$aliases['@web'])) {
            Dee::setAlias('@web', $this->request->getBaseUrl());
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
        if (isset($this->filters[$name])) {
            $component = $this->filters[$name];
            if (!is_object($component)) {
                return $this->filters[$name] = Dee::createObject($component);
            }
            return $component;
        }
        throw new Exception("Component {$name} not exists");
    }

    public function run()
    {
        list($route, $params, $config) = $this->request->resolve();

        if (empty($route)) {
            $route = $this->defaultRoute;
        }

        if (($pos = strrpos($route, '/')) !== false) {
            $id = substr($route, 0, $pos);
            $route = substr($route, $pos + 1);
        } else {
            $id = $route;
            $route = '';
        }

        /* @var $controller Controller */
        if (($result = $this->createController($id, $route)) !== false) {
            list($controller, $route) = $result;
            $this->controller = $controller;
            if (isset($config['_aliases'])) {
                $aliases = $controller->aliases();
                foreach ($config['_aliases'] as $key => $value) {
                    $controller->{$aliases[$key]} = $value;
                }
                unset($config['_aliases']);
            }
            foreach ($config as $key => $value) {
                $controller->$key = $value;
            }
            $this->response->data = $controller->run($route, $params, PHP_SAPI !== 'cli');
            $this->response->send();
        } else {
            throw new \Exception("Page {$id}/{$route} not found");
        }
    }

    /**
     *
     * @param string $id
     * @param string $route
     * @return boolean|array
     */
    public function createController($id, $route)
    {
        $ns = $this->controllerNamespace;
        $base = Dee::getAlias('@' . str_replace('\\', '/', $ns));
        $pos = strrpos($id, '/');
        if ($pos === false) {
            $className = $id;
            $prefix = '';
        } else {
            $prefix = substr($id, 0, $pos + 1);
            $className = substr($id, $pos + 1);
        }
        $className = $prefix . str_replace(' ', '', ucwords(str_replace('-', ' ', $className)));

        if (is_file($file = $base . '/' . $className . '.php')) {
            $className = $ns . '\\' . str_replace('/', '\\', $className);
            if (!class_exists($className)) {
                require $file;
            }
            $controller = new $className($id, $this);
            return [$controller, $route];
        } elseif ($route !== '') {
            return $this->createController($id . '/' . $route, '');
        }
        return false;
    }

    public function createUrl($route, $params = [])
    {
        $route = preg_replace_callback('/\{([\w._-]+)\}/', function($matches) use(&$params) {
            $name = $matches[1];
            if (array_key_exists($name, $params)) {
                $value = $params[$name];
                unset($params[$name]);
                return $value;
            }
            return $matches[0];
        }, $route);

        $request = $this->request;
        $url = ($this->showScriptName ? $request->getScriptUrl() : $request->getBaseUrl()) . '/' . rtrim($route, '/');
        if (!empty($params) && ($query = http_build_query($params)) !== '') {
            $url .= '?' . $query;
        }
        return $url;
    }

    public function registerErrorHandler()
    {
        if (defined('DEE_ERROR_HANDLER') && DEE_ERROR_HANDLER) {
            ini_set('display_errors', false);
            set_exception_handler([$this, 'handleException']);
            set_error_handler([$this, 'handleError']);
            $this->_memoryReserve = str_repeat('x', 262144);
            register_shutdown_function([$this, 'handleFatalError']);
        }
    }

    /**
     *
     * @param \Exception $exception
     */
    public function handleException($exception)
    {
        restore_error_handler();
        restore_exception_handler();

        $str = get_class($exception) . ': "' . $exception->getMessage() . '" at ' . $exception->getFile() .
            ':' . $exception->getLine() . "\n" . $exception->getTraceAsString();

        if (ob_get_level()) {
            ob_end_clean();
        }
        echo PHP_SAPI === 'cli' ? $str : "<pre>\n$str\n</pre>";
        //exit(1);
    }

    public function handleError($code, $message, $file, $line)
    {
        if (error_reporting() & $code) {
            $exception = new \ErrorException($message, $code, $code, $file, $line);

            // in case error appeared in __toString method we can't throw any exception
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            array_shift($trace);
            foreach ($trace as $frame) {
                if ($frame['function'] === '__toString') {
                    $this->handleException($exception);
                    if (defined('HHVM_VERSION')) {
                        flush();
                    }
                    exit(1);
                }
            }

            throw $exception;
        }
        return false;
        //exit(1);
    }

    public function handleFatalError()
    {
        unset($this->_memoryReserve);
        $error = error_get_last();
        if (isset($error['type']) && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR,
                E_COMPILE_WARNING])) {
            $this->handleError($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }
}
