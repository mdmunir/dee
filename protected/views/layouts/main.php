<?php
/* @var $this DView */
$this->begin();
?>
<!DOCTYPE html>
<html>
    <head>
        <title><?= $this->title ?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css" rel="stylesheet">
        <script type="text/javascript" src="//code.jquery.com/jquery-2.1.1.min.js"></script>
        <!--#SCRIPT_HEAD-->
    </head>
    <body>
        <div class="wrap">
            <div class="container">
                <div class="pull-right">
                    <?php if (isset(Dee::$app->user->id)): ?>
                        <?= Dee::$app->user->id ?> &nbsp; <a href="<?= Dee::createUrl('site/logout') ?>">Logout</a>
                    <?php else: ?>
                        <a href="<?= Dee::createUrl('site/login') ?>">Login</a>
                    <?php endif; ?>
                </div>
                <h1><?= $this->title ?></h1>
                <?= $content ?>
            </div>
        </div>
        <!--#SCRIPT_END-->
    </body>
</html>
<?php
$this->end();
