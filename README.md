# CI4-PDO-Firebird
Use Firebird with CodeIgniter v4 and PDO basic operations

#Instructions
Copy the folder Firebird to \Vendor\Codeigniter4\Framework\System\Database\

Add Firebird database to yours database `app\Config\Database`

Note: i use basic to read data from others databases.

##Using .env and DSN
```
database.default.DSN = firebird:dbname=192.168.6.2:alias_or_path_of_database;charset=UTF8;dialect=3
database.default.username = MYUSER
database.default.password = 123ABCD
database.default.DBDriver = Firebird
```
##Without DSN field
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
##Using DSN variable
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

##Now you can use in Models, but remember connect before call Model as 
in controller
```
$db2 = \Config\Database::connect('second_db');
```

##in model

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

##Now you ca use

```public function articulos(){
      $db2 = \Config\Database::connect('second_db');
        if (!$db2) {

        } else {
          $FbitemsModel = new FbitemsModel();
          $data['items'] = $FbitemsModel->findAll();
        }
        return view('items_view', $data);
    }
```

##Get all tables in database

```public function tables() {
      $db2 = \Config\Database::connect('second_db');
      if (!$db2) {

      } else {

        $tables = $db2->listTables();

        sort( $tables );
        foreach ($tables as $table)
        {
            echo $table.'<br />';
        }
      }
    }
```

##Get columns of an table

```
public function table_columns($table = null) {
      $db2 = \Config\Database::connect('second_db');
      if (!$db2) {
	//
      } else {
        $table_name = !empty($table) ? $table : 'STORES';
        $columns = $db2->_fieldData($table_name);
        echo '<table>';
        echo "<tr><td colspan=4>{$table_name}</td></tr>";
        echo '<tr><td>NAME</td><td>TYPE</td><td>MAX-LENGTH</td><td>DEFAULT</td></tr>';
        $sql_insert = '';
        foreach ($columns as $col)
        {
          echo '<tr>';
          echo '<td>'.$col->name.'</td>';
          echo '<td>'.$col->type.'</td>';
          echo '<td>'.$col->max_length.'</td>';
          echo '<td>'.$col->default.'</td>';
          echo '</tr>';
          $sql_insert .= "'".trim($col->name)."' => '', \n";
        }
        echo '</table>';
        echo '<hr/>';
	echo 'Use to create php array column to value';
        echo "<pre>{$sql_insert}</pre>";
        echo '<hr/>';
        echo "end";
      }
    }
```

##Find index in tables

```
public function all_indexes() {
      $db2 = \Config\Database::connect('second_db');
      if (!$db2) {

      } else {
        $table_name = !empty($table) ? $table : 'STORES';
        $indexes = $db2->_indexData($table_name);
        echo '<table>';
        echo '<tr><td>RELATION_NAME</td><td>INDEX_NAME</td><td>INDEX_ID</td><td>FOREIGN_KEY</td></tr>';
        foreach ($indexes as $idx)
        {
          echo '<tr>';
          echo '<td>'.$idx->RELATION_NAME.'</td>';
          echo '<td>'.$idx->INDEX_NAME.'</td>';
          echo '<td>'.$idx->INDEX_ID.'</td>';
          echo '<td>'.$idx->FOREIGN_KEY.'</td>';
          echo '</tr>';
        }
        echo '</table>';
      }
    }
```


I hope this can help somebody.

I need use a this on a webpage, using MySQL on frontend, and Firebird in the backend, basicaly to read the data of items.


