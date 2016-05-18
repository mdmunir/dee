Contoh Framework PHP Sederhana V2.0
==============================
Untuk liat versi sebelumnya silakan buka branch `master.1.x`.
Ini adalah versi 2. Di sini kita menggunakan OOP secara penuh.

Instalasi
---------
Download filenya dari [sini](https://github.com/mdmunir/simple-php-fw/archive/master.zip), kemudian ekstak.

Fitur
------

* Menggunakan pola MVC.
* Menggunakan autoloader. Meminimalkan penggunaan `include` dan `require`.
* `View templating`. Beberapa halaman dapat berbagi `layout` yang sama.
* Koneksi database dengan PDO.
* Clean url.
* Support url rules/routing. Bisa untuk membangun aplikasi REST.
* Dan lain-lain.


Cara Penggunaan
---------------

# Membuat controller.
Buat class di folder `protected/controllers` dengan nama `HelloController.php`.
```php
class HelloController extends DController
{
    public function actionIndex()
    {
        return $this->render('index',['name'=>'World']);
    }
}
```

* Perhatikan huruf besar huruf kecil. Controller class harus merupakan turunan dari class `DController`.
* Nama class harus diakhiri dengan `Controller`
* Nama class harus sama dengan nama file dengan akhiran `.php`.
* Nama class menggunakan format camel case(huruf besar di awal kata). Misal, routenya adalah `hello`, maka nama classnya
adalah `HelloController`. Jika nama routenya adalah `hello-guys` maka nama classnya adalah `HelloGuysController`.

# Membuat view.
Kemudian di folder `protected/views/hello` kita buat file `index.php`
```php
<div class="hello">
    <div class="jumbotron">
        <h1>Welcome!</h1>

        <p class="lead">Hello <?= $name; ?>.</p>

        <p><a href="https://mdmunir.wordpress.com" class="btn btn-lg btn-success">Get started</a></p>
    </div>
</div>
```

* Folder view dari controller bersesuaian dengan id controller.
* Jika id controller adalah `hello`, maka viewnya ada di folder `protected/views/hello`. Begitu juga jika
id controller adalah `hello-guys`, maka viewnya ada di folder `protected/views/hello-guys`.
* Kita juga bisa menyisipkan kode javascript di view. contoh

```php
<?php
$js = <<<JS
    $('#click-me').click(function(){
        alert('Hello...');
    });
JS;
$this->registerJs($js); // default di register ke jquery ready. 
// opsi lainnya adalah $this->registerJs($js,DView::POS_HEAD); atau $this->registerJs($js,DView::POS_END);
$this->title = 'Contoh JS';
?>
<div>
    <button id="click-me">Click Me</button>
</div>
```

Untuk mengakses halaman yang kita buat, urlnya adalah `localhost/path/app/index.php/hello`

# Clean URL
Untuk membuat url yang lebih bersih (menghilangkan `index.php`) lakukan beberapa langkah berikut.

* Membuat file `.htaccess`.
```
RewriteEngine on
# RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . index.php
```
* Merubah setingan `showScriptName`. Buka file `protected/config/main.php`. Ubah `urlManager->showScriptName` menjadi `false`. 

# REST Url
Rest url dapat dibuat dengan mengisi setingan `urlManager->rules`. Contoh:

```php
'rules' => [
    'GET,HEAD product' => 'product/index',
    'GET,HEAD product/{id:\d+}' => 'product/view',
    'POST product' => 'product/create',
    'PUT product/{id:\d+}' => 'product/update',
    'DELETE product/{id:\d+}' => 'product/delete',
]
```
Setelah itu kita buat controller `ProductController` dan mengimplementasikan action `actionIndex()`, `actionView()` dan seterusnya.

# Koneksi ke Database
Copy file `protected/config/db.example.php`, rename menjadi `protected/config/db.php` kemudian sesuaikan dsn, user dan passwordnya.
Misal untuk konek ke mysql, maka dsnnya adalah 'mysql:host=localhost;dbname=mydb'.
Setelah koneksi terbentuk, maka kita bisa memakainya di kontroller, misalnya.
```php
public function actionTampil()
{
    $sql = 'select * from user';
    $users = Dee::$app->db->queryAll($sql);
    return $this->render('tampil',['users' => $users]);
}

// kemudian di view tampil.php
<table>
    <thead>
        <tr>
            <th>Id</th>
            <th>Username</th>
            <th>Full Name</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($users as $user): ?>
        <tr>
            <td><?= $user['id']?></td>
            <td><?= $user['username']?></td>
            <td><?= $user['fullname']?></td>
        </tr>
        <?php endforeach ?>
    </tbody>
</table>
```

Selain diakses langsung dari controller. Kita juga bisa membuat model untuk menangani input output database.
Buat file `MUser.php` di folder `protected/models`.
```php
class MUser
{
    public function getAll()
    {
        $sql = 'select * from user';
        return Dee::$app->db->queryAll($sql);
    }

    public function addNew($user)
    {
        $sql = 'insert into user(username,fullname) values (:username,:fullname)';
        return Dee::$app->db->execute($sql,[
            ':username' => $user['username], 
            ':fullname' => $user['fullname'],
        ]);
    }
}

// di controller
public function actionCreate()
{
    $model = new MUser();
    $user = $_POST;
    $model->addNew($user);
}
```