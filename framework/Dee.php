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
     * @var \dee\base\Application
     */
    public static $app;
    public static $classMap;
    public static $aliases = ['@dee' => __DIR__];

    public static function setAlias($alias, $path)
    {
        if (strncmp($alias, '@', 1)) {
            $alias = '@' . $alias;
        }
        $alias = rtrim($alias, '/');
        if ($path === null) {
            unset(static::$aliases[$alias]);
        } else {
            static::$aliases[$alias] = rtrim($path, '/');
            krsort(static::$aliases);
        }
    }

    public static function getAlias($alias)
    {
        if (strncmp($alias, '@', 1)) {
            return $alias;
        }
        foreach (static::$aliases as $key => $path) {
            if (strpos($alias . '/', $key . '/') === 0) {
                return $path . substr($alias, strlen($key));
            }
        }
        return false;
    }

    public static function autoload($class)
    {
        if (isset(static::$classMap[$class])) {
            require static::$classMap[$class];
            return true;
        } else {
            $file = static::getAlias('@' . str_replace('\\', '/', $class)) . '.php';
            if (is_file($file)) {
                require $file;
                return true;
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

    public static function createUrl($route, $params = [])
    {
        return static::$app->createUrl($route, $params);
    }

    public static function redirect($route, $params = [])
    {
        header('Location: ' . static::createUrl($route, $params));
        exit();
    }
}

Dee::$classMap = require(__DIR__ . '/classes.php');
spl_autoload_register(['Dee', 'autoload']);
