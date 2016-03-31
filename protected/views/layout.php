<!DOCTYPE html>
<html>
    <head>
        <title><?= get('title') ?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css" rel="stylesheet">
        <script type="text/javascript" src="//code.jquery.com/jquery-2.1.1.min.js"></script>
    </head>
    <body>
        <div class="wrap">
            <div class="container">
                <div class="pull-right">
                    <?php if (isset($_SESSION['__identity'])): ?>
                        <?= $_SESSION['__identity'] ?> &nbsp; <a href="<?= createUrl('logout') ?>">Logout</a>
                    <?php else: ?>
                        <a href="<?= createUrl('login') ?>">Login</a>
                    <?php endif; ?>
                </div>
                <h1><?= get('title') ?></h1>
                <?= $content ?>
            </div>
        </div>
        <script >
<?= get('script') ?>
        </script>
    </body>
</html>