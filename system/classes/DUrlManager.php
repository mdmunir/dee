<?php

/**
 * Description of DRoute
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class DUrlManager
{
    const REGEX = '~\{([\w._-]+):?([^\}]+)?\}~x';
    const DEFAULT_REGEX = '[^/]+';

    public $rules = [];
    public $showScriptName = true;
    public $cache = false;
    private $_routes;

    protected function prepare()
    {
        if ($this->_routes !== null) {
            return;
        }

        $file = $this->cache ? Dee::$app->basePath . '/runtime/routes_' . md5(serialize($this->rules)) . '.json' : false;
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

    public function resolve($request)
    {
        $this->prepare();
        $pathInfo = ltrim($request->getPathInfo(), '/');
        $method = $request->getMethod();
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
        return [$pathInfo, []];
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

        $request = Dee::$app->request;
        $url = ($this->showScriptName ? $request->getScriptUrl() : $request->getBaseUrl()) . '/' . rtrim($route, '/');
        if (!empty($params) && ($query = http_build_query($params)) !== '') {
            $url .= '?' . $query;
        }
        return $url;
    }
}
