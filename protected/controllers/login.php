<?php
$message = null;
$username = $password = null;
if (isset($_POST['submit'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($username == 'admin' && $password == 'admin') {
        $_SESSION['__identity'] = $username;
        redirectTo('home');
    } else {
        $message = 'Wrong username password';
    }
}

set('title', 'Login');
echo render('login.php', [
    'message' => $message,
    'username' => $username,
    'password' => $password,
    ], true);