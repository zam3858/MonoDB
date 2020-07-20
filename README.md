
# ![MonoDB](https://static.monodb.io/logo-150x150.svg) MonoDB

MonoDB is flat-file key-value data structure store, used as a database, cache and message broker. 

Simple by design, includes with console command, intended to provide an alternative when you need a quick and reliable data storage and retrieval solution without requiring a server-based application.

- [API Reference](https://github.com/nawawi/MonoDB/wiki/Base-Methods)
- [Console Commands](https://github.com/nawawi/MonoDB/wiki/Command-Set)
- [Report issues](https://github.com/nawawi/MonoDB/issues)
- [Send Pull requests](https://github.com/nawawi/MonoDB/pulls)

![Console](https://static.monodb.io/console.jpg)

## Features

- Key/Value Data Storage
- Array-based Data Structure
- Key Expires
- Supports multiple data structure
- Can store content of files
- Encrypt/Decrypt data
- File locking


## Supported data types

- string
- integer
- float
- array
- object
- binary
- json


## Installation

MonoDB requires PHP 7.1 and above with json extension enabled.

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

MonoDB comes with PHAR files. Download the [MonoDB-phar](https://github.com/nawawi/MonoDB/releases/) file, either library file or CLI file.

- Load library
```php
require 'path-to-monodb-phar-lib/monodb-lib.phar';
```
- Accessing Console
```
./path-to-monodb-phar-bin/monodb-cli.phar
```

## Basic Usage

`Using Library:`
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

`Using CLI`
  
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
`dir`|string|<temp-dir\>/\_monodb\_|Path to where the database is stored.
`dbname`|string|db0|Path to where data files are stored.
`keylength`|int|50|Maximum key length. Larger than this will be truncated.
`blobsize`|int|5000000|Maximum size in byte of binary file can be stored.
`keyexpiry`|int|0|Default key expiry in timestamp for all keys.
`dirmode`|int|0755|Default Unix directory permission.
`filemode`|int|0644|Default Unix file permission.


## Config File
By default, MonoDB will read configuration options from `.monodb` file locates in $HOME directory. You can overwrite it by set MONODB_CONFIG environment variable.

`PHP example:`
```php
<?php
putenv('MONODB_CONFIG=/fullpath-to-config-file');
$db = new Monodb\Monodb();
$response = $db->info('config:monodb_config');
echo $response;
```

`Bash example:`
```
#!/usr/bin/env bash
export MONODB_CONFIG=/fullpath-to-config-file
/path-to-monodb-bin/monodb-cli.phar $@
```

`Configuration example:`

All options are case-insensitive.

```
# this is comment
DIR=/tmp/_monodb_
DBNAME=db0
KEYLENGTH=50
BLOBSIZE=5000000
KEYEXPIRY=0
DIRMODE=0755
FILEMODE=0644
```

## How Versions Work

Versions are as follows: Major.Minor.Patch

* Major: Rewrites with completely new code-base.
* Minor: New Features/Changes that breaks compatibility.
* Patch: New Features/Fixes that does not break compatibility.


## Contributions

Anyone can contribute to MonoDB. Please do so by posting issues when you've found something unexpected or sending pull requests for improvements.


## License

MonoDB is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
