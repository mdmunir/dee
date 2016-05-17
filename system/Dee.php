<?php

/**
 * Description of Dee
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class Dee
{
    /**
     * @var DApp
     */
    public static $app;
    public static $classMap;
    private static $_paths = [];

    public static function autoload($class)
    {
        if (isset(static::$classMap[$class])) {
            include static::$classMap[$class];
            return true;
        } else {
            foreach (self::$_paths as $i => $path) {
                if (strncmp($path, '@app/', 5) === 0) {
                    self::$_paths[$i] = $path = static::$app->basePath . substr($path, 4);
                }
                if (is_file("{$path}/{$class}.php")) {
                    include "{$path}/{$class}.php";
                    return true;
                }
            }
        }
        return false;
    }

    public static function createObject($type, $params = [])
    {
        if (is_string($type)) {
            $class = $type;
            $type = [];
        } else {
            $class = $type['class'];
            unset($type['class']);
        }
        if (count($params)) {
            $reff = new ReflectionClass($class);
            $object = $reff->newInstanceArgs($params);
        } else {
            $object = new $class();
        }
        foreach ($type as $name => $value) {
            $object->$name = $value;
        }
        return $object;
    }

    public static function import($path)
    {
        self::$_paths[] = rtrim($path, '/');
    }

    public static function createUrl($route, $params = [])
    {
        return Dee::$app->urlManager->createUrl($route, $params);
    }

    public static function redirect($route, $params = [])
    {
        header('Location: ' . static::createUrl($route, $params));
        exit();
    }
}

Dee::$classMap = require(__DIR__ . '/classes.php');
spl_autoload_register(['Dee', 'autoload']);
