![MonoDB](https://repository-images.githubusercontent.com/246608460/c6927300-644c-11ea-83a2-dc441b18c022)

# MonoDB
MonoDB is an Open Source Simple Flat File key-value data structure store, used as a database, cache and message broker.


## Features
- Key/Value Data Storing
- Array-based Data Structure
- Key Expires
- Support multiple type of data
- Can store binary file
- Encrypt/Decrypt data
- File locking


## Supported data type
- string
- integer
- float
- array
- object
- resource
- binary
- json


## Installation

Use [Composer](http://getcomposer.org/) to install package.

```sh
composer require nawawi/monodb:^1.0
```

Alternatively, if you're not using Composer, download the files and copy the contents of the MonoDB folder into one of the include_path directories specified in your PHP configuration and load MonoDB class file manually:

```php
<?php
use MonoDB\MonoDB;

require 'path/to/MonoDB/src/MonoDB.php';
```
**Minimum Requirement:**
- PHP 5.6+
- PHP ctype extension
- PHP json extension


## Usage

```php
// Setting the data directory and change default configuration.
// By default, if no directory is specified, MonoDB will create data directory
// in current working directory.
$db = new MonoDB([
    'path'      => 'path/to/data/dir',
    'dbname'    => 'monodb0'
]);

// Store and retrieve string data.
// myname is key and kobayashi is data to store.
$db->set('myname', 'kobayashi maru');

// Will return 'kobayashi maru'
echo $db->get('myname');

// Store array data
$data = [
  'name' => 'mat jargon',
  'status' => 'lalang'
];

$db->set('katahikmat', $data);
print_r($db->get('katahikmat'));

// Will return
Array
(
    [name] => mat jargon
    [status] => lalang
)

// get all keys
$array = $db->keys();

// find data
$data = $db->find('key', 'value');
```


## Config Options

You can configure and change default MonoDB options.

Usage Example (all options)

```php
$db = new MonoDB([
    'path'        => 'path/to/data/dir',
    'dbname'      => 'monodb0',
    'key_length'  => 50,
    'blob_size'   => 5000000,
    'key_expiry'  => 0,
    'perm_dir'    => 0755,
    'perm_file'   => 0644 
]);
```

Name|Type|Default Value|Description
:---|:---|:---|:---
`path`|string|current directory|The path where the data directory will create.
`dbname`|string|monodb0|The directory where the data files are stored.
`key_length`|int|50|Maximum key length. Larger than this will truncated.
`blob_size`|int|5000000|Maximum size in byte of binary file can be stored.
`key_expiry`|int|0|Default key expiry in seconds for all keys.
`perm_dir`|int|0755|Default Unix directory permission.
`perm_file`|int|0644|Default Unix file permission.


## Database Methods

```php
$db = new MonoDB\MonoDB($config);
$db->Method();
```

Method|Details
:---|:---
`set($key, $value, $expiry)`|<p>`set(string $key, mixed $value, (Opt)int $expiry)`</p><p>`$key` Only alphanumeric, hyphen, dot and semicolon considered as valid input.<br>`$value` Accept any data type.<br>`$expiry` *(optional)* If set, the key will expires in seconds.</p>Return `key string` if successful, `false` otherwise.
`get($key)`|<p>`get(string $key)`</p><p>Retrieve data associate with the key `$key`.</p>Return `mixed string` if successful, `false` otherwise.
`delete($key)`|<p>`delete(string $key)`</p><p>Delete data associate with the key`$key`.</p>Return `true` if successful, `false` otherwise.
`keys($key)`|<p>`keys((Optional)string $key)`</p><p>Retrieve all available Keys. Optionally retrieve specified `$key` <br>and possible match it using wildcard `*key*`.</p>Return `mixed string` if successful, `false` otherwise.
`find($key, $value)`|<p>`find(string $key, string $value)`</p><p>Retrieve data based on `$value` and possible match it, using wildcard `*key*`.</p>Return `mixed string` if successful, `false` otherwise.
`exists($key)`|<p>`exists(string $key)`</p><p>Check if key `$key` exists and data file is readable.</p>Return `true` if available, `false` otherwise.
`flush()`|<p>`flush()`</p>Flush database, delete all keys.

Example:
- Store image file
```php
$db = new MonoDB\MonoDB();
$db->set('image', 'file:///path-to-image/image.jpg', 0, ['mime'=>'image/jpg']);
```

- Retrieve image data
```php
$db = new MonoDB\MonoDB();
// binary output
$blob = $db->blob()->get('image');

// array output
$blob = $db->get('image');
if ( is_array($blob) ) {
    echo "<img src="data:".$blob['mime'].";base64,".$blob['data'].">";
}
```

- Store mysql query results
```php
$mysqli = new mysqli("localhost","dbuser","dbpassword","dbname");
$result = $mysqli->query("select * from tables");

$db = new MonoDB\MonoDB();
// key expires after 1 minutes
$db->set('mysqlres', $result, strtotime('+1 minute') );

```


## Left Chain Methods *(optional)*

```php
$db = new MonoDB\MonoDb($config);
$db->Chain()->Method();
```

Chain Method|Details
:---|:---
`options($config)`|<p>`options(array $config)`</p>Set database options.
`meta()`|<p>`meta()`</p>Retrieve key meta data.
`blob()`|<p>`blob()`</p>Output data as binary if data type of Key is binary. By default MonoDB return as base64 encoded data for safety reason.
`encrypt($secret)`|<p>`encrypt(string $secret)`</p>
`decrypt($secret)`|<p>`decrypt(string $secret)`</p>

Example:
- Change dbname
```php
$db = MonoDB\MonoDB();
$db->options(['dbname'=>'db2'])->set('key','value');
```

- Encrypt data
```php
$db->encrypt('123456')->set('key','sangat rahsia');
```
- Decrypt data
```php
$db->decrypt('123456')->get('key');
```


## How Versions Work

Versions are as follows: Major.Minor.Patch

* Major: Rewrites with completely new code-base.
* Minor: New Features/Changes that breaks compatibility.
* Patch: New Features/Fixes that does not break compatibility.


## Contributions

Anyone can contribute to MonoDB. Please do so by posting issues when you've found something that is unexpected or sending a pull request for improvements.


## License

MonoDB is open-sourced software licensed under the [GPL-3.0 license](https://opensource.org/licenses/GPL-3.0).
