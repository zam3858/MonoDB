
# ![MonoDB](https://static.monodb.io/logo-150x150.svg) MonoDB
MonoDB is flat-file key-value data structure store, used as a simple database, cache and message broker.


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
- binary
- json


## Installation

Use [Composer](http://getcomposer.org/) to install package.

```sh
composer require nawawi/monodb:1.0.x-dev
```
Load library using composer autoload.
```php
require 'vendor/autoload.php';
```

Alternatively, if you're not using Composer, download the [files](https://github.com/nawawi/MonoDB/releases) and copy the contents of the MonoDB folder into one of the include_path directories specified in your PHP configuration and load MonoDB class file manually:

```php
require 'path-to-monodb-dir/autoload.php';
```

## Minimum Requirement
- PHP 7.1+
- PHP json extension


## Usage

```php
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


## Config Options

You can configure and change default MonoDB options.

Usage Example (all options)

```php
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
`dir`|string|current directory|The directory where the database are stored.
`dbname`|string|monodb0|The directory where the data files are stored.
`key_length`|int|50|Maximum key length. Larger than this will truncated.
`blob_size`|int|5000000|Maximum size in byte of binary file can be stored.
`key_expiry`|int|0|Default key expiry in timestamp for all keys.
`perm_dir`|int|0755|Default Unix directory permission.
`perm_file`|int|0644|Default Unix file permission.


## How Versions Work

Versions are as follows: Major.Minor.Patch

* Major: Rewrites with completely new code-base.
* Minor: New Features/Changes that breaks compatibility.
* Patch: New Features/Fixes that does not break compatibility.


## Contributions

Anyone can contribute to MonoDB. Please do so by posting issues when you've found something that is unexpected or sending a pull request for improvements.


## License

MonoDB is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
