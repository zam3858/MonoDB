<?php
/*
 * This file is part of the MonoDB package.
 *
 * (c) Nawawi Jamili <nawawi@rutweb.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monodb;

use Monodb\Functions as Func;
use Monodb\Arrays as Arr;

class Config {
    public $dir = '';
    public $dbdir = '';
    public $dbname = 'db0';
    public $dbindex = '';
    public $key_length = 150;
    public $key_expiry = 0;
    public $blob_size = 5000000;
    public $perm_dir = 0755;
    public $perm_file = 0644;

    public function __construct( array $options ) {
        $this->dir = sys_get_temp_dir().'/_monodb_/';
        $this->dbdir = $this->dir.$this->dbname.'/';

        $config = $this->read_file_config();
        if ( ! empty( $config ) && is_array( $config ) ) {
            $config = Arr::keys_walk(
                $config,
                function( $key, $value ) {
                    return strtolower( $key );
                }
            );
            $options = array_merge( $options, $config );
        }

        $options = $this->set_options( $options );

        foreach ( $options as $key => $value ) {
            $this->{$key} = $value;
        }

        $this->dbdir = Func::normalize_path( $this->dbdir );
        $this->dbindex = $this->dbdir.'index.php';
    }

    protected function set_options( array $options ) {
        if ( ! empty( $options['dir'] ) && \is_string( $options['dir'] ) ) {
            $this->dir = Func::resolve_path( $options['dir'].'/_monodb_/' );
            $options['dbdir'] = $this->dir.$this->dbname.'/';
        }

        if ( ! empty( $options['dbname'] ) && \is_string( $options['dbname'] ) ) {
            $options['dbdir'] = $this->dir.'/'.$options['dbname'].'/';
        }

        if ( ! empty( $options['key_length'] ) && Func::is_var_num( $options['key_length'] ) ) {
            $key_length = (int) $options['key_length'];
            if ( $key_length > 0 ) {
                $options['key_length'] = $key_length;
            }
            $options['key_length'] = (int) $options['key_length'];
        }

        if ( ! empty( $options['blob_size'] ) && Func::is_var_num( $options['blob_size'] ) ) {
            $blob_size = (int) $options['blob_size'];
            if ( $blob_size > 0 ) {
                $options['blob_size'] = $blob_size;
            }
        }

        if ( ! empty( $options['key_expiry'] ) && Func::is_var_num( $options['key_expiry'] ) ) {
            $key_expiry = (int) $options['key_expiry'];
            if ( $key_expiry > 0 ) {
                $options['key_expiry'] = $key_expiry;
            }
        }

        if ( ! empty( $options['perm_dir'] ) && Func::is_var_num( $options['perm_dir'] ) ) {
            $options['perm_dir'] = $options['perm_dir'];
        }

        if ( ! empty( $options['perm_file'] ) && Func::is_var_num( $options['perm_file'] ) ) {
            $options['perm_file'] = $options['perm_file'];
        }

        return $options;
    }

    protected function read_file_config() {
        $config = [];
        if ( 'cli' === php_sapi_name() && ! empty( $_SERVER['HOME'] ) ) {
            $config = $this->parse_config( $_SERVER['HOME'].'/.monodb.env' );
            if ( ! empty( $config ) && is_array( $config ) ) {
                return $config;
            }
        } elseif ( ! empty( $_SERVER['DOCUMENT_ROOT'] ) ) {
            $config = $this->parse_config( $_SERVER['DOCUMENT_ROOT'].'/.monodb.env' );
            if ( ! empty( $config ) && is_array( $config ) ) {
                return $config;
            }
        }

        $file = getenv( 'MONODB_ENV', true );
        $config = $this->parse_config( $file );
        if ( ! empty( $config ) && is_array( $config ) ) {
            return $config;
        }

        return false;
    }

    private function parse_config( $file ) {
        $config = [];
        if ( ! empty( $file ) && Func::is_file_readable( $file ) ) {
            $buff = file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
            if ( ! empty( $buff ) && \is_array( $buff ) ) {
                foreach ( $buff as $line ) {
                    if ( '#' === $line{0} ) {
                        continue;
                    }

                    $line = str_replace( [ '"', "'" ], '', $line );
                    if ( Func::has_with( $line, '=' ) ) {
                        list($key, $value) = explode( '=', trim( $line ) );
                        $key = strtolower( trim( $key ) );
                        $value = trim( $value );
                        if ( ! empty( $key ) && property_exists( $this, $key ) && ! empty( $value ) ) {
                            $config[ $key ] = $value;
                        }
                    }
                }
                if ( ! empty( $config ) ) {
                    $config['env'] = $file;
                }
            }
        }
        return $config;
    }
}
