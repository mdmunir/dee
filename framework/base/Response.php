<?php

namespace dee\base;

use Dee;

/**
 * Description of Response
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class Response
{
    public $data;
    public $formats = [];
    public $format = 'html';
    public $formatCallback = [];
    public $contentType;
    private $_headers = [];
    private $_cookies = [];

    public function send()
    {
        $this->sendHeaders();
        echo $this->data;
    }

    public function setHeader($name, $value, $add = false)
    {
        if ($add) {
            $this->_headers[$name] = [$value];
        } else {
            $this->_headers[$name][] = $value;
        }
    }

    public function addCookie($name, $value, $expire = 0)
    {
        $this->_cookies[$name] = [
            'value' => json_encode($value),
            'expire' => $expire,
        ];
    }

    protected function sendHeaders()
    {
        if (PHP_SAPI === 'cli' || headers_sent()) {
            return;
        }
        $formatCallback = array_merge([
            'json' => 'json_encode',
            ], $this->formatCallback);

        $formats = array_merge([
            'html' => 'text/html',
            'text' => 'text/plain',
            'json' => 'application/json; charset=UTF-8',
            ], $this->formats);

        if ($this->format) {
            if ($this->contentType === null && isset($formats[$this->format])) {
                $this->contentType = $formats[$this->format];
            }
            if (isset($formatCallback[$this->format])) {
                $this->data = call_user_func($formatCallback[$this->format], $this->data);
            }
        }
        if ($this->contentType === null && !empty($this->format) && isset($formats[$this->format])) {
            $this->contentType = $formats[$this->format];
        }
        if ($this->contentType) {
            $this->_headers['Content-Type'] = [$this->contentType];
        }


        foreach ($this->_headers as $name => $values) {
            $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
            // set replace for first occurrence of header but false afterwards to allow multiple
            $replace = true;
            foreach ($values as $value) {
                header("$name: $value", $replace);
                $replace = false;
            }
        }

        $request = Dee::$app->request;
        $enableCookieValidation = $request->enableCookieValidation;
        if ($enableCookieValidation && !$request->cookieValidationKey) {
            $request->cookieValidationKey = Dee::getKey(get_class($request));
        }
        foreach ($this->_cookies as $name => $cookie) {
            $expire = $cookie['expire'] > 0 ? mktime() + $cookie['expire'] : 0;
            $value = $enableCookieValidation ? Dee::hashData($cookie['value'], $request->cookieValidationKey) : $cookie['value'];
            if (PHP_VERSION_ID >= 70300) {
                setcookie($name, $value, [
                    'expires' => $expire,
                    'path' => '/',
                    'domain' => '',
                    'secure' => false,
                    'httpOnly' => true,
                ]);
            } else {
                setcookie($name, $value, $expire, '/', '', false, true);
            }
        }
    }
}
