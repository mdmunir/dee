<?php

/**
 * Description of DRequest
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class DRequest
{

    public function resolve()
    {
        if (PHP_SAPI === 'cli') {
            $params = $_SERVER['argv'];
            array_shift($params);
            $route = isset($params[0]) ? $params[0] : '';
            array_shift($params);
            return[$route, $params];
        } else {
            $urlManager = Dee::$app->urlManager;
            list($route, $params) = $urlManager->resolve($this);
            $_GET += $params;
            return [$route, $_GET];
        }
    }

    public function get($name = null, $default = null)
    {
        return $name === null ? $_GET : (isset($_GET[$name]) ? $_GET[$name] : $default);
    }
    private $_bodyParams;

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
}
