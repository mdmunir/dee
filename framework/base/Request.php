<?php

namespace dee\base;

use Dee;

/**
 * Description of DRequest
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class Request
{
    const REGEX = '~\{([\w._-]+):?([^\}]+)?\}~x';
    const DEFAULT_REGEX = '[^/]+';

    public $rules = [];
    public $cache = false;
    private $_routes;

    /**
     * Resolve request
     * @return array
     */
    public function resolve()
    {
        if (PHP_SAPI === 'cli') {
            $params = $_SERVER['argv'];
            array_shift($params);
            $route = isset($params[0]) ? $params[0] : '';
            array_shift($params);
            $config = [];
            foreach ($params as $i => $param) {
                if (preg_match('/^--(\w+)(?:=(.*))?$/', $param, $matches)) {
                    $config[$matches[1]] = isset($matches[2]) ? $matches[2] : true;
                    unset($params[$i]);
                } elseif (preg_match('/^-(\w+)(?:=(.*))?$/', $param, $matches)) {
                    $config['_aliases'][$matches[1]] = isset($matches[2]) ? $matches[2] : true;
                    unset($params[$i]);
                }
            }
            return[$route, array_values($params), $config];
        } else {
            list($route, $params) = $this->resolveRoute();
            $_GET += $params;
            return [$route, $_GET, []];
        }
    }

    protected function resolveRoute()
    {
        $this->prepare();
        $pathInfo = $this->getPathInfo();

        if (!empty($this->_routes)) {
            $method = $this->getMethod();
            if (isset($this->_routes[$pathInfo])) {
                list($route, $verbs, ) = $this->_routes[$pathInfo];
                if (empty($verbs) || in_array($method, $verbs)) {
                    return [$route, []];
                }
            }
            foreach ($this->_routes as $regex => $data) {
                list($route, $verbs, $varNames) = $data;
                if ((empty($verbs) || in_array($method, $verbs)) && preg_match($regex, $pathInfo, $matches)) {
                    $i = 0;
                    foreach ($varNames as $varName) {
                        $params[$varName] = $matches[++$i];
                    }
                    return[$route, $params];
                }
            }
        }
        return [$pathInfo, []];
    }

    /**
     * Get query params
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function get($name = null, $default = null)
    {
        return $name === null ? $_GET : (isset($_GET[$name]) ? $_GET[$name] : $default);
    }
    /**
     * @var array $_POST value
     */
    private $_bodyParams;

    /**
     * Post value
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function post($name = null, $default = null)
    {
        if ($this->_bodyParams === null) {
            $contenType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] :
                (isset($_SERVER['HTTP_CONTENT_TYPE']) ? $_SERVER['HTTP_CONTENT_TYPE'] : false);
            if ($contenType && strpos($contenType, 'json') !== false) {
                $this->_bodyParams = json_decode(file_get_contents('php://input'), true);
            } elseif ($this->getMethod() === 'POST') {
                $this->_bodyParams = $_POST;
            } else {
                $this->_bodyParams = [];
                mb_parse_str(file_get_contents('php://input'), $this->_bodyParams);
            }
        }
        return $name === null ? $this->_bodyParams : (isset($this->_bodyParams[$name]) ? $this->_bodyParams[$name] : $default);
    }
    /**
     *
     * @var pathInfo, baseUrl, scriptUrl
     */
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
                $this->_pathInfo = ltrim(substr($requestUri, strlen($this->_scriptUrl)), '/');
            } elseif (strpos($requestUri, $this->_baseUrl) === 0) {
                $this->_pathInfo = ltrim(substr($requestUri, strlen($this->_baseUrl)), '/');
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

    /**
     * Returns the method of the current request (e.g. GET, POST, HEAD, PUT, PATCH, DELETE).
     * @return string request method, such as GET, POST, HEAD, PUT, PATCH, DELETE.
     * The value returned is turned into upper case.
     */
    public function getMethod()
    {
        if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            return strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
        }

        if (isset($_SERVER['REQUEST_METHOD'])) {
            return strtoupper($_SERVER['REQUEST_METHOD']);
        }

        return 'GET';
    }

    /**
     * Prepare routing
     */
    protected function prepare()
    {
        if (empty($this->rules)) {
            $this->_routes = [];
            return;
        }

        if ($this->_routes === null) {
            $file = $this->cache ? Dee::getAlias('@app/runtime/routes_') . md5(serialize($this->rules)) . '.json' : false;
            if ($file && is_file($file)) {
                $this->_routes = json_decode(file_get_contents($file), true);
            } else {
                $verbs = 'GET|HEAD|POST|PUT|PATCH|DELETE|OPTIONS';
                $this->_routes = [];

                foreach ($this->rules as $pattern => $route) {
                    if (preg_match("/^((?:($verbs),)*($verbs))(?:\\s+(.*))?$/", $pattern, $matches)) {
                        $methods = explode(',', $matches[1]);
                        $pattern = isset($matches[4]) ? $matches[4] : '';
                    } else {
                        $methods = [];
                    }
                    list($regex, $variables) = $this->parse($pattern);
                    $this->_routes[$regex] = [$route, $methods, $variables];
                }
                if ($file) {
                    file_put_contents($file, json_encode($this->_routes));
                }
            }
        }
    }

    /**
     * Parses a route string that does not contain optional segments.
     */
    protected function parse($route)
    {
        $route = ltrim($route, '/');
        if (!preg_match_all(self::REGEX, $route, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            return [$route, []];
        }

        $regex = '';
        $variables = [];
        $offset = 0;
        foreach ($matches as $set) {
            if ($set[0][1] > $offset) {
                $regex .= preg_quote(substr($route, $offset, $set[0][1] - $offset), '~');
            }
            $variables[] = $set[1][0];
            $regex .= '(' . (isset($set[2][0]) ? $set[2][0] : self::DEFAULT_REGEX) . ')';
            $offset = $set[0][1] + strlen($set[0][0]);
        }

        if ($offset != strlen($route)) {
            $regex .= preg_quote(substr($route, $offset), '~');
        }
        return ['~^' . $regex . '$~', $variables];
    }
}
