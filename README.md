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
// Setting the database directory and other configuration.
// By default if no path specified, MonoDB will create database directory 'monodb' same path
// with MonoDB class file.
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

| Name            | Type      | Default Value	      | Description                                           |
|-----------------|-----------|---------------------|-------------------------------------------------------|
| `dir`           | string    | monodb              | The directory where the data files are stored.        |
| `key_length`    | int       | 50                  | Maximum key length. Longer than this will truncate.   |
| `blob_size`     | int       | 5000000             | Maximum size in byte for binary file can be store.    |
| `key_expiry`    | int       | 0                   | Default key expiry in seconds for all keys.           |
| `perm_dir`      | int       | 0755                | Default Unix directory permission.                    |
| `perm_file`     | int       | 0644                | Default Unix file permission.                         |
