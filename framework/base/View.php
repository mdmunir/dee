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
    const POS_END = 2;
    const POS_READY = 3;

    private $_files = [];
    public $title;
    public $params = [];
    public $js = [];
    public $jsFiles = [];
    public $jsMap = [];

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

    public function begin()
    {
        ob_start();
        ob_implicit_flush(false);
    }

    public function end()
    {
        $content = ob_get_clean();
        $jsHead = '';
        if (!empty($this->jsFiles[self::POS_HEAD])) {
            foreach ($this->jsFiles[self::POS_HEAD] as $file) {
                $jsHead .= "<script src=\"{$file}\" type=\"text/javascript\"></script>\n";
            }
        }
        $scriptEnd = '';
        if (!empty($this->jsFiles[self::POS_END])) {
            foreach ($this->jsFiles[self::POS_END] as $file) {
                $scriptEnd .= "<script src=\"{$file}\" type=\"text/javascript\"></script>\n";
            }
        }
        $jsHead .= empty($this->js[self::POS_HEAD]) ? '' :
            "<script>\n" . implode("\n", $this->js[self::POS_HEAD]) . "\n</script>";
        $jsEnd = empty($this->js[self::POS_READY]) ? '' :
            "jQuery(document).ready(function(){\n" . implode("\n", $this->js[self::POS_READY]) . "\n});";
        $jsEnd .= empty($this->js[self::POS_END]) ? '' : "\n" . implode("\n", $this->js[self::POS_END]);
        $jsEnd = $scriptEnd . (empty(trim($jsEnd)) ? '' : "<script>\n{$jsEnd}\n</script>");
        echo strtr($content, [
            '<!--#SCRIPT_HEAD-->' => $jsHead,
            '<!--#SCRIPT_END-->' => $jsEnd,
        ]);
    }
}
