<?php

namespace dee\base;

use Dee;

/**
 * Description of User
 *
 * @property int|string|null $id
 * @property bool $isGuest
 * @property bool $isLoged
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class User
{
    public $enableSession = true;
    public $cookieIdentityKey = '__identity';
    private $_id;
    private $_profile = [];
    private $_init = false;

    protected function init()
    {
        if ($this->_init === false) {
            $this->_init = true;
            if ($this->enableSession) {
                session_start();
                if (isset($_SESSION['__identity'])) {
                    $this->_id = $_SESSION['__identity'];
                    if (isset($_SESSION['__profile'])) {
                        $this->_profile = $_SESSION['__profile'];
                    }
                } elseif ($value = Dee::$app->request->cookie($this->cookieIdentityKey)) {
                    list($this->_id, $this->_profile) = $value;
                }
            }
        }
    }

    public function login($id, $profile = [], $duration = 0)
    {
        if ($this->enableSession) {
            session_start();
            $this->_id = $_SESSION['__identity'] = $id;
            $this->_profile = $_SESSION['__profile'] = $profile;
            $this->_init = true;
            if ($duration > 0) {
                $value = [$this->_id, $this->_profile];
                Dee::$app->response->addCookie($this->cookieIdentityKey, $value, $duration);
            }
        } else {
            $this->_id = $id;
            $this->_profile = $profile;
        }
    }

    public function logout()
    {
        if ($this->enableSession) {
            session_start();
            session_unset();
            $sessionId = session_id();
            session_destroy();
            session_id($sessionId);
        }
        $this->_id = null;
        $this->_profile = [];
    }

    public function __get($name)
    {
        $this->init();
        switch ($name) {
            case 'isGuest':
                return !$this->_id;
            case 'isLoged':
                return !!$this->_id;
            case 'id':
                return $this->_id;

            default:
                return isset($this->_profile[$name]) ? $this->_profile[$name] : null;
        }
    }
}
