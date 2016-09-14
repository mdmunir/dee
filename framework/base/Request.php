<?php

namespace dee\base;

use Dee;

/**
 * Description of Request
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
            if (isset($this->_routes['static'][$pathInfo])) {
                foreach ($this->_routes['static'][$pathInfo] as $data) {
                    list($route, $params, $verbs ) = $data;
                    if (empty($verbs) || in_array($method, $verbs)) {
                        return [$route, $params];
                    }
                }
            }
            foreach ($this->_routes['var'] as $regex => $routes) {
                if (preg_match($regex, $pathInfo, $matches)) {
                    foreach ($routes as $data) {
                        list($route, $params, $verbs, $varNames) = $data;
                        if (empty($verbs) || in_array($method, $verbs)) {
                            foreach ($varNames as $p => $varName) {
                                $params[$varName] = $matches[$p];
                            }
                            return[$route, $params];
                        }
                    }
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
            if (($pos = strpos($requestUri, '?')) !== false) {
                $requestUri = substr($requestUri, 0, $pos);
            }
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

                    if (is_array($route)) {
                        $params = array_slice($route, 1);
                        $route = $route[0];
                    } else {
                        $params = [];
                    }
                    $this->parse($pattern, $route, $params, $methods);
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
    protected function parse($pattern, $route, $params, $verbs)
    {
        $pattern = ltrim($pattern, '/');
        if (preg_match_all(self::REGEX, $pattern, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            $regex = '~^';
            $variables = [];
            $offset = 0;
            $i = 1;
            foreach ($matches as $match) {
                if ($match[0][1] > $offset) {
                    $regex .= preg_quote(substr($pattern, $offset, $match[0][1] - $offset), '~');
                }
                $variables['d' . $i] = $match[1][0];
                $_regex = isset($match[2][0]) ? $match[2][0] : self::DEFAULT_REGEX;
                $regex .= "(?P<d{$i}>$_regex)";
                $offset = $match[0][1] + strlen($match[0][0]);
                $i++;
            }

            if ($offset != strlen($pattern)) {
                $regex .= preg_quote(substr($pattern, $offset), '~');
            }
            $regex .= '$~';
            if (isset($this->_routes['var'][$regex])) {
                $this->_routes['var'][$regex][] = [$route, $params, $verbs, $variables];
                usort($this->_routes['var'][$regex], function($v1, $v2) {
                    return count($v1[2]) >= count($v2[2]) ? -1 : 1;
                });
            } else {
                $this->_routes['var'][$regex] = [[$route, $params, $verbs, $variables]];
            }
        } else {
            if (isset($this->_routes['static'][$pattern])) {
                $this->_routes['static'][$pattern][] = [$route, $params, $verbs];
                usort($this->_routes['static'][$pattern], function($v1, $v2) {
                    return count($v1[2]) >= count($v2[2]) ? -1 : 1;
                });
            } else {
                $this->_routes['static'][$pattern] = [[$route, $params, $verbs]];
            }
        }
    }
}
