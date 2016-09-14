<?php
namespace dee\base;
/**
 * Description of User
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class User
{
    public $id;
    private $_profile = [];

    public function __construct()
    {
        session_start();
        if (isset($_SESSION['__identity'])) {
            $this->id = $_SESSION['__identity'];
            if (isset($_SESSION['__profile'])) {
                $this->_profile = $_SESSION['__profile'];
            }
        }
    }

    public function login($id, $profile = [])
    {
        $this->id = $_SESSION['__identity'] = $id;
        $this->_profile = $_SESSION['__profile'] = $profile;
    }

    public function logout()
    {
        session_unset();
        $sessionId = session_id();
        session_destroy();
        session_id($sessionId);
    }

    public function __get($name)
    {
        if (isset($this->_profile[$name])) {
            return $this->_profile[$name];
        }
    }
}
