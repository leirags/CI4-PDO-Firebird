# CI4-PDO-Firebird
Use Firebird with CodeIgniter v4 and PDO basic operations

#Instructions
Copy the folder Firebird to \Vendor\Codeigniter4\Framework\System\Database\

Add Firebird database to yours database `app\Config\Database`

Note: i use basic to read data from others databases.

Without DSN field
```
public $second_db = [
		'DSN'	=> '',
		'hostname' => '192.168.6.2',
		'port'     => '', // default: '3050',
		'username' => 'MYUSSER',
		'password' => '123ABCD',
		'database' => 'D:/Database/Employees.FDB',
		'DBDriver' => 'Firebird',
		'DBPrefix' => '',
		'pConnect' => FALSE,
		'DBDebug'  => (ENVIRONMENT !== 'production'),
		'cacheOn'  => FALSE,
		'cacheDir' => '',
		'charset'  => 'UTF8',
		'DBCollat' => 'utf8_general_ci',
		'swapPre'  => '',
		'encrypt'  => FALSE,
		'compress' => FALSE,
		'strictOn' => FALSE,
		'failover' => [],
	];
```  
Using DSN variable
```
 public $third_db = [
		'DSN'	=> 'firebird:192.168.3.2:D:/Database/Employees.FDB',
		'hostname' => '',
		'port'     => '',
		'username' => 'MYUSSER',
		'password' => '123ABCD',
		'database' => '',
		'DBDriver' => 'Firebird',
		'DBPrefix' => '',
		'pConnect' => FALSE,
		'DBDebug'  => (ENVIRONMENT !== 'production'),
		'cacheOn'  => FALSE,
		'cacheDir' => '',
		'charset'  => 'UTF8',
		'DBCollat' => 'utf8_general_ci',
		'swapPre'  => '',
		'encrypt'  => FALSE,
		'compress' => FALSE,
		'strictOn' => FALSE,
		'failover' => [],
	];
```

Now you can use in Models, but remember connect before call Model as 
in controller
```
$db2 = \Config\Database::connect('second_db');
```

in model

```
<?php namespace App\Models;

use CodeIgniter\Model;

class FbarticulosModel extends Model
{

    protected $DBGroup = 'second_db';

    protected $table = 'ITEMS';
    protected $primaryKey = 'ID';

    protected $returnType = 'object'; // array, object
    protected $useSoftDeletes = false;
    
    :::
 }
```


