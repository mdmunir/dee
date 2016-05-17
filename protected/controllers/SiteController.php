<?php

/**
 * Description of SiteController
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class SiteController extends DController
{

    public function actionHello()
    {
        return 'Hello World';
    }

    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionLogin()
    {
        $message = $username = $password = null;
        if (isset($_POST['submit'])) {
            $username = $_POST['username'];
            $password = $_POST['password'];

            if ($username == 'admin' && $password == 'admin') {
                Dee::$app->user->login($username);
                $this->redirect('index');
            } else {
                $message = 'Wrong username password';
            }
        }

        return $this->render('login', [
                'message' => $message,
                'username' => $username,
                'password' => $password,
        ]);
    }

    public function actionLogout()
    {
        Dee::$app->user->logout();
        $this->redirect('index');
    }

    public function actionContoh()
    {
        return $this->render('contoh');
    }

    public function actionPage($page)
    {
        return $this->render('pages/' . $page);
    }
}
