<?php
/* @var $this dee\base\View */
$this->beginPage();

$this->registerPackage('bootstrap');

$user = Dee::$app->user;
?>
<!DOCTYPE html>
<html>
    <head>
        <title><?= $this->title ?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <?php $this->head() ?>
    </head>
    <body>
        <?php $this->beginBody() ?>
        <div class="wrap">
            <div class="container">
                <div class="pull-right">
                    <?php if ($user->isLoged): ?>
                        <?= $user->id ?> &nbsp; <a href="<?= Dee::createUrl('site/logout') ?>">Logout</a>
                    <?php else: ?>
                        <a href="<?= Dee::createUrl('site/login') ?>">Login</a>
                    <?php endif; ?>
                </div>
                <h1><?= $this->title ?></h1>
                <?= $content ?>
            </div>
        </div>
        <?php $this->endBody() ?>
    </body>
</html>
<?php
$this->endPage();
