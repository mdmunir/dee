<?php
if (session_status() == PHP_SESSION_ACTIVE) {
    session_unset();
    $sessionId = session_id();
    session_destroy();
    session_id($sessionId);
}
redirectTo('home');