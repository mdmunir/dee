<?php
define('APP_PATH', __DIR__);
define('SYS_GLOBAL_VAR', '_d326_fw');
session_start();

function set($name, $value)
{
    $GLOBALS[SYS_GLOBAL_VAR][$name] = $value;
}

function get($name, $default = null)
{
    return isset($GLOBALS[SYS_GLOBAL_VAR][$name]) ? $GLOBALS[SYS_GLOBAL_VAR][$name] : $default;
}

function createUrl($route, $params = [])
{
    $url = get('baseUrl') . '/' . rtrim($route, '/');
    if (!empty($params) && ($query = http_build_query($params)) !== '') {
        $url .= '?' . $query;
    }
    return $url;
}

function redirectTo($route, $params = [])
{
    header('Location: ' . createUrl($route, $params));
    exit();
}

function render($view, $params = [], $layout = false)
{
    if (strncmp($view, '/', 1) !== 0) { // kalau diawali '/' berarti absolut path
        $view = APP_PATH . '/views/' . $view;
    }
    if (!is_file($view) && is_file($view . '.php')) {
        $view .= '.php';
    }
    $content = renderFile($view, $params);
    if ($layout) {
        return renderFile(APP_PATH . '/views/layout.php', ['content' => $content]);
    }
    return $content;
}

function renderFile($_file_, $_params_ = [])
{
    ob_start();
    ob_implicit_flush(false);
    extract($_params_, EXTR_OVERWRITE);
    require($_file_);
    return ob_get_clean();
}

function run()
{
    set('baseUrl', $_SERVER['SCRIPT_NAME']);
    $route = rtrim($_SERVER['PATH_INFO'], '/');
    if (empty($route)) {
        $route = 'home'; // ubah default route di sini
    }
    set('route', $route);

    echo renderFile(APP_PATH . "/controllers/{$route}.php");
}
