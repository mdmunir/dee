Contoh Framework PHP Sederhana
==============================

Instalasi
---------
Download filenya dari [sini](https://github.com/mdmunir/simple-php-fw/archive/master.zip), kemudian ekstak.

Cara Penggunaan
---------------

1. Membuat controller.
Buat file file di folder `protected/controllers` dengan nama `hello.php`.
```php
<?php

set('title','Hello');
echo render('hello.php',['name' => 'World']);
```

2. Membuat view.
Kemudian di folder `protected/views` kita buat file `hello.php`
```php
<div class="hello">
    <div class="jumbotron">
        <h1>Welcome!</h1>

        <p class="lead">Hello <?= $name; ?>.</p>

        <p><a href="https://mdmunir.wordpress.com" class="btn btn-lg btn-success">Get started</a></p>
    </div>
</div>
```

Untuk mengakses halaman yang kita buat, urlnya adalah `localhost/path/app/index.php/hello`