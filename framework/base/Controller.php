<?php

namespace dee\base;

use Dee;

/**
 * Description of Controller
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class Controller
{
    public $layout = '/layouts/main';
    public $defaultAction = 'index';
    public $id;
    public $route;
    /**
     *
     * @var Application
     */
    public $parent;

    public function __construct($id, $parent = null)
    {
        $this->id = $id;
        $this->parent = $parent;
    }

    public function render($view, $params = [])
    {
        $dview = Dee::$app->view;
        if (strncmp($view, '/', 1)) {
            $view = '/' . $this->id . '/' . $view;
        }
        $content = $dview->render($view, $params);
        return $dview->render($this->layout, ['content' => $content]);
    }

    public function renderPartial($view, $params = [])
    {
        $dview = Dee::$app->view;
        if (strncmp($view, '/', 1)) {
            $view = '/' . $this->id . $view;
        }
        return $dview->render($view, $params);
    }

    /**
     *
     * @param string $route
     * @param array $params
     * @param bool $assoc
     * @return string|mixed
     * @throws \Exception
     */
    public function run($route = '', $params = [], $assoc = true)
    {
        if ($route == '') {
            $route = $this->defaultAction;
        }
        $this->route = $this->id . '/' . $route;
        $action = 'action' . str_replace(' ', '', ucwords(str_replace('-', ' ', $route)));

        $reflection = new \ReflectionMethod($this, $action);
        $args = [];
        foreach ($reflection->getParameters() as $param) {
            $name = $param->getName();
            if ($assoc && array_key_exists($name, $params)) {
                $args[] = $params[$name];
            } elseif (!$assoc && count($params)) {
                $args[] = array_shift($params);
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                throw new \Exception("Missing parameter '{$name}'");
            }
        }
        if (!$assoc && count($params)) {
            $args = array_merge($args, $params);
        }
        if ($this->parent === null) {
            $filters = $this->filters();
        } else {
            $filters = array_merge($this->parent->filters, $this->filters());
        }
        foreach ($filters as $i => $filter) {
            if (!is_object($filter)) {
                $filters[$i] = $filter = Dee::createObject($filter);
            }
            if (!$filter->before()) {
                return;
            }
        }
        $output = call_user_func_array([$this, $action], $args);
        foreach (array_reverse($filters) as $filter) {
            $output = $filter->after($output);
        }
        return $output;
    }

    /**
     *
     * @return array|Filter[]
     */
    public function filters()
    {
        return[
        ];
    }

    protected function aliases()
    {
        return[];
    }
}
