<?php

namespace dee\base;

use Dee;

/**
 * Description of View
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class View
{
    const POS_HEAD = 1;
    const POS_BEGIN = 2;
    const POS_END = 3;
    const POS_READY = 4;
    const POS_LOAD = 5;

    /**
     * This is internally used as the placeholder for receiving the content registered for the head section.
     */
    const PH_HEAD = '<![CDATA[DEE-BLOCK-HEAD]]>';

    /**
     * This is internally used as the placeholder for receiving the content registered for the beginning of the body section.
     */
    const PH_BODY_BEGIN = '<![CDATA[DEE-BLOCK-BODY-BEGIN]]>';

    /**
     * This is internally used as the placeholder for receiving the content registered for the end of the body section.
     */
    const PH_BODY_END = '<![CDATA[DEE-BLOCK-BODY-END]]>';

    public $title;
    public $params = [];
    public $js = [];
    public $jsFiles = [];
    public $css = [];
    public $cssFiles = [];
    public $jsMap = [];
    public $packages = [];
    protected $corePackages = [
        'jquery' => [
            'js' => [
                '//code.jquery.com/jquery-2.1.1.min.js',
            ]
        ],
    ];
    protected $registeredPackages = [];
    private $_files = [];
    private $_packages;

    public function render($view, $params = [])
    {
        if (strncmp($view, '/', 1) === 0 || count($this->_files) === 0) {
            $view = Dee::$app->basePath . '/views/' . ltrim($view, '/');
        } else {
            $view = dirname(end($this->_files)) . '/' . $view;
        }
        if (is_file($view)) {
            return $this->renderPhp($view, $params);
        } elseif (is_file($view . '.php')) {
            return $this->renderPhp($view . '.php', $params);
        }
        throw new \Exception("View {$view} not found");
    }

    protected function renderPhp($_file_, $_params_ = [])
    {
        $this->_files[] = $_file_;
        ob_start();
        ob_implicit_flush(false);
        extract($_params_, EXTR_OVERWRITE);
        require($_file_);
        array_pop($this->_files);
        return ob_get_clean();
    }

    public function registerJs($js, $pos = self::POS_READY)
    {
        $this->js[$pos][] = $js;
    }

    public function registerJsFile($jsFile, $pos = self::POS_END)
    {
        foreach ($this->jsMap as $file => $map) {
            if (($n = strlen($file)) <= strlen($jsFile) && $file === substr($jsFile, -$n)) {
                if ($map) {
                    $jsFile = $map;
                    break;
                } else {
                    return;
                }
            }
        }
        $this->jsFiles[$pos][md5($jsFile)] = $jsFile;
    }

    public function registerCss($css)
    {
        $this->css[] = $css;
    }

    public function registerCssFile($cssFile)
    {
        foreach ($this->jsMap as $file => $map) {
            if (($n = strlen($file)) <= strlen($cssFile) && $file === substr($cssFile, -$n)) {
                if ($map) {
                    $cssFile = $map;
                    break;
                } else {
                    return;
                }
            }
        }
        $this->cssFiles[md5($cssFile)] = $cssFile;
    }

    public function registerPackage($name, $position = null)
    {
        if ($this->_packages === null) {
            $this->_packages = array_merge($this->corePackages, $this->packages);
        }
        if (isset($this->registeredPackages[$name])) {
            if ($this->registeredPackages[$name] === true) {
                throw new \Exception("A circular dependency is detected for bundle '$name'.");
            } elseif ($position === null || $this->registeredPackages[$name] <= $position) {
                return true;
            }
        }
        if (!isset($this->_packages[$name])) {
            throw new \Exception("Undefined package '$name'.");
        }
        $this->registeredPackages[$name] = true;
        $package = $this->_packages[$name];
        if (empty($package)) {
            $this->registeredPackages[$name] = false;
            return;
        }
        $pos = empty($package['position']) ? self::POS_END : $package['position'];
        if ($position !== null && $position < $pos) {
            $pos = $position;
        }
        $depends = empty($package['depends']) ? [] : (array) $package['depends'];
        foreach ($depends as $depend) {
            $this->registerPackage($depend, $pos);
        }
        unset($this->registeredPackages[$name]);
        $this->registeredPackages[$name] = $pos;
    }

    protected function registerPackages()
    {
        if ($this->_packages === null) {
            $this->_packages = array_merge($this->corePackages, $this->packages);
        }
        foreach ($this->registeredPackages as $name => $pos) {
            if ($pos === false || empty($this->_packages[$name])) {
                continue;
            }
            $package = $this->_packages[$name];
            if (!empty($package['baseUrl'])) {
                $baseUrl = rtrim($package['baseUrl'], '/') . '/';
            } else {
                $baseUrl = '';
            }
            if (!empty($package['js'])) {
                foreach ((array) $package['js'] as $jsFile) {
                    $this->registerJsFile($baseUrl . $jsFile, $pos);
                }
            }
            if (!empty($package['css'])) {
                foreach ((array) $package['css'] as $cssFile) {
                    $this->registerCssFile($baseUrl . $cssFile);
                }
            }
        }
    }

    public function head()
    {
        echo self::PH_HEAD;
    }

    public function beginBody()
    {
        echo self::PH_BODY_BEGIN;
    }

    public function endBody()
    {
        echo self::PH_BODY_END;
    }

    public function beginPage()
    {
        ob_start();
        ob_implicit_flush(false);
    }

    public function endPage()
    {
        $content = ob_get_clean();
        if (!empty($this->js[self::POS_READY]) || !empty($this->js[self::POS_LOAD])) {
            $this->registerPackage('jquery');
        }
        $this->registerPackages();
        // head script
        $head = '';
        if (!empty($this->jsFiles[self::POS_HEAD])) {
            foreach ($this->jsFiles[self::POS_HEAD] as $file) {
                $file = $this->encode(Dee::getAlias($file));
                $head .= "<script src=\"{$file}\" type=\"text/javascript\"></script>\n";
            }
        }

        if (!empty($this->js[self::POS_HEAD])) {
            $head .= "<script>\n" . implode("\n", $this->js[self::POS_HEAD]) . "\n</script>";
        }

        if (!empty($this->cssFiles)) {
            foreach ($this->cssFiles as $file) {
                $file = $this->encode(Dee::getAlias($file));
                $head .= "<link href=\"{$file}\" rel=\"stylesheet\">\n";
            }
        }
        if (!empty($this->css)) {
            $head .= "<style>\n" . implode("\n", $this->css) . "\n</style>";
        }

        // begin body script
        $begin = '';
        if (!empty($this->jsFiles[self::POS_BEGIN])) {
            foreach ($this->jsFiles[self::POS_BEGIN] as $file) {
                $file = $this->encode(Dee::getAlias($file));
                $begin .= "<script src=\"{$file}\" type=\"text/javascript\"></script>\n";
            }
        }

        if (!empty($this->js[self::POS_BEGIN])) {
            $begin .= "<script>\n" . implode("\n", $this->js[self::POS_BEGIN]) . "\n</script>";
        }

        // end body script
        $end = '';
        if (!empty($this->jsFiles[self::POS_END])) {
            foreach ($this->jsFiles[self::POS_END] as $file) {
                $file = $this->encode(Dee::getAlias($file));
                $end .= "<script src=\"{$file}\" type=\"text/javascript\"></script>\n";
            }
        }

        $scriptEnd = empty($this->js[self::POS_END]) ? '' : "\n" . implode("\n", $this->js[self::POS_END]);
        if (!empty($this->js[self::POS_READY])) {
            $scriptEnd .= "\njQuery(document).ready(function(){\n" . implode("\n", $this->js[self::POS_READY]) . "\n});";
        }
        if (!empty($this->js[self::POS_LOAD])) {
            $scriptEnd .= "\njQuery(window).on('load', function(){\n" . implode("\n", $this->js[self::POS_LOAD]) . "\n});";
        }
        if (!empty(trim($scriptEnd))) {
            $end .= "\n<script>\n{$scriptEnd}\n</script>";
        }

        echo strtr($content, [
            self::PH_HEAD => $head,
            self::PH_BODY_BEGIN => $begin,
            self::PH_BODY_END => $end,
        ]);
    }

    protected function encode($content, $charset = 'UTF-8', $doubleEncode = true)
    {
        return htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, $charset, $doubleEncode);
    }
}
