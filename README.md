
# ![MonoDB](https://static.monodb.io/logo-150x150.svg) MonoDB

MonoDB is flat-file key-value data structure store, used as a database, cache and message broker. 

Simple by design, included with console command, intends to provide as alternative options when you need quick and reliable solution to store and retrieve data without require a server-based application.

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
composer require nawawi/monodb
```
- Load library using composer autoload.
```php
require 'vendor/autoload.php';
```
- Accessing Console
```
./vendor/bin/monodb
```

Alternatively, if you're not using Composer, download the [MonoDB-dist](https://github.com/nawawi/MonoDB/releases/) file, extract and load autoload file.

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
        'keylength'  => 50,
        'blobsize'   => 5000000,
        'keyexpiry'  => 0,
        'dirmode'    => 0755,
        'filemode'   => 0644
    ]
);

```

Name|Type|Default Value|Description
:---|:---|:---|:---
`dir`|string|<temp-dir\>/\_monodb\_|The directory where the database are stored.
`dbname`|string|db0|The directory where the data files are stored.
`keylength`|int|50|Maximum key length. Larger than this will truncated.
`blobsize`|int|5000000|Maximum size in byte of binary file can be stored.
`keyexpiry`|int|0|Default key expiry in timestamp for all keys.
`dirmode`|int|0755|Default Unix directory permission.
`filemode`|int|0644|Default Unix file permission.


By default, console command will read the configuration from '.monodb.env' file located in HOME, DOCUMENT_ROOT and same working directory with the console.
```
DIR=
DBNAME=
KEYLENGTH=
BLOBSIZE=
DIRMODE=
FILEMODE=
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
