
# ![MonoDB](https://static.monodb.io/logo-150x150.svg) MonoDB
MonoDB is flat-file key-value data structure store, used as a database, cache and message broker. 

Simple by design, included with console command, intends to provide as alternative options when you need quick and reliable way to store and retrieve data without require a server-based application.

- [API Reference](https://github.com/nawawi/MonoDB/wiki/Base-Methods)
- [Console Commands](https://github.com/nawawi/MonoDB/wiki/Command-Set)
- [Report issues](https://github.com/nawawi/MonoDB/issues)
- [Send Pull requests](https://github.com/nawawi/MonoDB/pulls)

![Console](https://static.monodb.io/console.jpg)
## Features
- Key/Value Data Storing
- Array-based Data Structure
- Key Expires
- Support multiple data structure
- Can store content of file
- Encrypt/Decrypt data
- File locking


## Supported data type

- string
- integer
- float
- array
- object
- binary
- json


## Installation
To use MonoDB require minimum PHP 7.1 and json extension installed.

Use [Composer](http://getcomposer.org/) to install package.

```sh
composer require nawawi/monodb dev-master
```
- Load library using composer autoload.
```php
require 'vendor/autoload.php';
```
- Accessing Console
```
./vendor/bin/monodb
```

Alternatively, if you're not using Composer, download the [file](https://github.com/nawawi/MonoDB/archive/master.zip), unzip and load autoload file.

```php
require 'path-to-monodb-dir/autoload.php';
```
- Accessing Console
```
./path-to-monodb-dir/bin/monodb
```


## Basic Usage
- Library
```php
<?php
use Monodb;

// Setting the data directory and database name.
$db = new Monodb(
    [
        'dir'       => 'path/to/data/dir',
        'dbname'    => 'monodb0'
    ]
);

// Store the value of "hello world!" with the key "greeting",
// will return the key string if success, false otherwise.
$response = $db->set( 'greeting', 'hello world!' );
echo $response;

// Retrieve and display the value of "greeting" key.
echo $db->get( 'greeting' );
```
- Console
  
```
monodb set greeting 'hello world!'
+----------+
| Key      |
+----------+
| greeting |
+----------+
```

```
monodb get greeting
+----------+--------------+
| Key      | Value        |
+----------+--------------+
| greeting | hello world! |
+----------+--------------+
```
## Config Options

You can configure and change default MonoDB options.

Usage Example (all options)

```php
<?php
use Monodb;

$db = new Monodb(
    [
     	'dir'        => 'path/to/data/dir',
        'dbname'      => 'monodb0',
        'key_length'  => 50,
        'blob_size'   => 5000000,
        'key_expiry'  => 0,
        'perm_dir'    => 0755,
        'perm_file'   => 0644
    ]
);

```

Name|Type|Default Value|Description
:---|:---|:---|:---
`dir`|string|<temp-dir\>/\_monodb\_|The directory where the database are stored.
`dbname`|string|db0|The directory where the data files are stored.
`key_length`|int|50|Maximum key length. Larger than this will truncated.
`blob_size`|int|5000000|Maximum size in byte of binary file can be stored.
`key_expiry`|int|0|Default key expiry in timestamp for all keys.
`perm_dir`|int|0755|Default Unix directory permission.
`perm_file`|int|0644|Default Unix file permission.


By default, console command will read the configuration from '.monodb.env' file located in HOME, DOCUMENT_ROOT and same working directory with the console.
```
DIR=
DBNAME=
KEY_LENGTH=
BLOB_SIZE=
PERM_DIR=
PERM_FILE=
```

## How Versions Work

Versions are as follows: Major.Minor.Patch

* Major: Rewrites with completely new code-base.
* Minor: New Features/Changes that breaks compatibility.
* Patch: New Features/Fixes that does not break compatibility.


## Contributions

Anyone can contribute to MonoDB. Please do so by posting issues when you've found something that is unexpected or sending a pull request for improvements.


## License

MonoDB is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
