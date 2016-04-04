<?php

/**
 * Description of DView
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class DView
{
    const POS_HEAD = 1;
    const POS_END = 2;
    const POS_READY = 3;

    private $_files = [];
    public $title;
    public $params = [];
    public $js = [];

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
        throw new Exception("View {$view} not found");
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

    public function begin()
    {
        ob_start();
        ob_implicit_flush(false);
    }

    public function end()
    {
        $content = ob_get_clean();
        $jsHead = empty($this->js[self::POS_HEAD]) ? '' :
            "<script>\n" . implode("\n", $this->js[self::POS_HEAD]) . "\n</script>";
        $jsEnd = empty($this->js[self::POS_READY]) ? '' :
            "(function($){\n" . implode("\n", $this->js[self::POS_READY]) . "\n})(jQuery);";
        $jsEnd .= empty($this->js[self::POS_END]) ? '' : "\n" . implode("\n", $this->js[self::POS_END]);
        $jsEnd = empty(trim($jsEnd)) ? '' : "<script>\n{$jsEnd}\n</script>";
        echo strtr($content, [
            '<!--#SCRIPT_HEAD-->' => $jsHead,
            '<!--#SCRIPT_END-->' => $jsEnd,
        ]);
    }
}
