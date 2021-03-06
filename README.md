Dee Framework
=============

Framework PHP dengan pola MVC. Cocok untuk media belajar cara kerja framework.

[![Latest Stable Version](https://poser.pugx.org/mdmunir/dee-app/v/stable)](https://packagist.org/packages/mdmunir/dee-app)
[![Latest Unstable Version](https://poser.pugx.org/mdmunir/dee-app/v/unstable)](https://packagist.org/packages/mdmunir/dee-app)
[![Build Status](https://travis-ci.org/mdmunir/dee.svg?branch=master)](https://travis-ci.org/mdmunir/dee)
[![License](https://poser.pugx.org/mdmunir/dee-app/license)](https://packagist.org/packages/mdmunir/dee-app)

Instalasi
---------
Download filenya dari [sini](https://github.com/mdmunir/dee/archive/master.zip), kemudian ekstak.
Setelah itu buka command line, masuk ke folder hasil ekstraksi dan jalankan
```
php init
```

Fitur
------

* Menggunakan pola MVC.
* Menggunakan autoloader. Meminimalkan penggunaan `include` dan `require`.
* `View templating`. Beberapa halaman dapat berbagi `layout` yang sama.
* Koneksi database dengan PDO.
* Register js dan css.
* Asset bundle.
* Clean url.
* Support url rules/routing. Bisa untuk membangun aplikasi REST.
* Aplikasi Console.
* Dan lain-lain.

Cara Penggunaan
---------------

# Membuat controller.
Buat class di folder `protected/controllers` dengan nama `Hello.php`.
```php
namespace app\controllers;

class Hello extends \dee\base\Controller
{
    public function actionIndex()
    {
        return $this->render('index', ['name'=>'World']);
    }
}
```

* Perhatikan huruf besar huruf kecil. Controller class harus merupakan turunan dari class `dee\base\Controller`.
* Nama class harus sama dengan nama file dengan akhiran `.php`.
* Nama class menggunakan format camel case(huruf besar di awal kata). Misal, routenya adalah `hello`, maka nama classnya
adalah `Hello`. Jika nama routenya adalah `hello-guys` maka nama classnya adalah `HelloGuys`.

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
// opsi lainnya adalah $this->registerJs($js,View::POS_HEAD); atau $this->registerJs($js,View::POS_END);
$this->title = 'Contoh JS';
?>
<div>
    <button id="click-me">Click Me</button>
</div>
```

Untuk mengakses halaman yang kita buat, urlnya adalah `localhost/path/app/index.php/hello`

Asset Package
------------
Meregister file js dapat dilakukan dengan mudah lewat `Asset Package`. Caranya, kita daftarkan paket kita di file config
```php
'components' => [
    'views' => [
        'packages' => [
            'bootstrap' => [
                'js' => ['https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js'],
                'css' => ['https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css'],
                'depends' => ['jquery'],
            ],
        ]
    ]
]
```
Setelah itu di view kita, kita bisa menggunakan paket tersebut dengan merigesternya
```php
/* @var $this \dee\base\View */

$this->registerPackage('bootstrap');
```
Saat ini paket inti yang tersedia adalah `jquery` yang mengarah ke `//code.jquery.com/jquery-2.2.4.min.js`.
Anda dapat menggunakan jquery anda sendiri dengan cara menimpah konfigursinya
```php
'components' => [
    'views' => [
        'packages' => [
            ...
            'jquery' => [
                'js' => ['@web/main/jquery.min.js'],
            ],
        ]
    ]
]
```
Paket `jquery` akan otomatis tersedia ketika meregister javascript di `POS_READY` atau `POS_LOAD`. Atau Anda
dapat meregister manual dengan memanggil dari view `$this->registerPackage('jquery')`.


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
* Merubah setingan `showScriptName`. Buka file `protected/config/web.php`. Ubah `showScriptName` menjadi `false`. 

# REST Url
Rest url dapat dibuat dengan mengisi setingan `request->rules`. Contoh:

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
Edit file `protected/config/db.php` kemudian sesuaikan dsn, user dan passwordnya.
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
Buat file `User.php` di folder `protected/models`.
```php
namespace app\models;

class User
{
    public function getAll()
    {
        $sql = 'select * from user';
        return \Dee::$app->db->queryAll($sql);
    }

    public function addNew($user)
    {
        $sql = 'insert into user(username,fullname) values (:username,:fullname)';
        return \Dee::$app->db->execute($sql,[
            ':username' => $user['username'], 
            ':fullname' => $user['fullname'],
        ]);
    }
}

// di controller
public function actionCreate()
{
    $model = new \app\models\User();
    $user = $_POST;
    $model->addNew($user);
}
```
## Sql Builder
Untuk memudahkan penggunaan komponen database, maka beberapa fitur ditambahkan untuk membuat sintak sql sederhana. 

### Where Builder

```php
$where = [
    'colom1' => 'nilai 1',
    'colom2' => [1, 2, 3],
    "colom3 <> 5",
];
$params = [];
$conditions = Dee::$app->db->buildCondition($where, $params);
// maka variable $conditions dan $params akan bernilai
// $conditions = "(colom1 = :p1) AND (colom2 in (:p2,:p3,:p4)) AND (colom3 <> 5)"
// $params = [':p1' => 'nilai 1', ':p2' => 1,  ':p3' => 2,  ':p4' => 3, ];
```

### Insert Builder

```php
Dee::$app->db->insert('user', [
    'username' => 'mdmunir,
    'password' => md5($password),
    'company_id' => 1001,    
]);
// akan memeksekusi sql
// INSERT INTO user(username, password, company_id) values(:p1, :p2, :p3);
// dengan $params = [':p1' => 'mdmunir', ':p2' => 'md5hash',  ':p3' => 1001 ];
```

### Update Builder
```php
Dee::$app->db->update('user', [
    'password' => md5($password),
], ['id' => 1]);
// akan memeksekusi sql
// UPDATE user SET password = :p1 WHERE (id = :p2);
// dengan $params = [':p1' => 'md5hash', ':p2' => 1];
```

### Delete Builder
```php
Dee::$app->db->delete('user', ['id' => 1]);
// akan memeksekusi sql
// DELETE FROM user WHERE (id = :p1);
// dengan $params = [':p1' => 1];
```

# Autoloader
Agar class-class dapat diload dengan benar, maka pastikan class-class yang ada memiliki namesapce yang bersesuaian dengan pathnya.
Untuk class-class yang berada di bawah folder `protected`, maka root namespace-nya adalah `app`. Sub namespace-nya sesuai
dengan folder class tersebut berada. Misal untuk class di bawah folder models, maka namespacenya adalah `app\models`.
