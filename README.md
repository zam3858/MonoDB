# MonoDB
MonoDB is an Open Source Flat File key-value data structure store, used as a simple database, cache and message broker.

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
## Usage

```php
// Setting the data directory and change default configuration.
// By default, if no directory is specified, MonoDB will create a 'monodb' directory in the same 
// directory as the MonoDB file.
$db = new MonoDB([
    'dir' => 'path/to/database/dir'
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
    'dir'         => 'path/to/database/dir',
    'key_length'  => 50,
    'blob_size'   => 5000000,
    'key_expiry'  => 0,
    'perm_dir'    => 0755,
    'perm_file'   => 0644 
]);
```

Name | Type | Default Value | Description
:---|:---|:---|:---
`dir`           | string    | monodb              | The directory where the data files are stored.
`key_length`    | int       | 50                  | Maximum key length. Larger than this will truncated.
`blob_size`     | int       | 5000000             | Maximum size in byte of binary file can be stored.
`key_expiry`    | int       | 0                   | Default key expiry in seconds for all keys.
`perm_dir`      | int       | 0755                | Default Unix directory permission.
`perm_file`     | int       | 0644                | Default Unix file permission.


## Database Methods

```php
$db = new MonoDB($config);
$db->Method();
```

Method|Details
:---|:---
`set($key, $value, $expiry)`|<p>`set(string $key, mixed $value, (Optional)int $expiry)`</p><p>`$key` Only alphanumeric, hyphen, dot and semicolon considered as valid input.<br>`$value` Accept any data type.<br>`$expiry` *(optional)* The key will expires in seconds if set.</p>Return `key string` if successful, `false` otherwise.
`get($key)`|<p>`get(string $key)`</p><p>Retrieve data associate with the key `$key`.</p>Return `mixed string` if successful, `false` otherwise.
`delete($key)`|<p>`delete(string $key)`</p><p>Delete data associate with the key`$key`.</p>Return `true` if successful, `false` otherwise.
`keys($key)`|<p>`keys((Optional)string $key)`</p><p>Retrieve all available Keys. Optionally retrieve specified `$key` <br>and possible match it using wildcard `*key*`.</p>Return `mixed string` if successful, `false` otherwise.
`find($key, $value)`|<p>`find(string $key, string $value)`</p><p>Retrieve data based on `$value` and possible match it, using wildcard `*key*`.</p>Return `mixed string` if successful, `false` otherwise.
`flush()`|<p>`flush()`</p>Flush database, delete all keys.


## Left Chain Methods *(optional)*

```php
$db = new MonoDb($config);
$db->Chain()->Method();
```

Chain Method|Details
:---|:---
`options($config)`|<p>`options(array $config)`</p>Set database options.
`raw()`|<p>`raw()`</p>Retrieve additional data for each key.


## How Versions Work

Versions are as follows: Major.Minor.Patch

* Major: Rewrites with completely new code-base.
* Minor: New Features/Changes that breaks compatibility.
* Patch: New Features/Fixes that does not break compatibility.


## Contributions

Anyone can contribute to MonoDB. Please do so by posting issues when you've found something that is unexpected or sending a pull request for improvements.


## License

MonoDB is open-sourced software licensed under the [GPL-3.0 license](https://opensource.org/licenses/GPL-3.0).
