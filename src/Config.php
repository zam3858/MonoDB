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

class Config {
    public $dir = '';
    public $dbdir = '';
    public $dbname = 'monodb0';
    public $dbindex = '';
    public $key_length = 150;
    public $key_expiry = 0;
    public $blob_size = 5000000;
    public $perm_dir = 0755;
    public $perm_file = 0644;

    public function __construct( array $options ) {
        $this->dir = Func::resolve_path( './' );
        $this->dbdir = $this->dir.$this->dbname;

        $options = $this->set_options( $options );

        foreach ( $options as $key => $value ) {
            $this->{$key} = $value;
        }

        $this->dbdir = Func::normalize_path( $this->dbdir );
        $this->dbindex = $this->dbdir.'index.php';
    }

    protected function set_options( array $options ) {
        if ( ! empty( $options['dir'] ) && \is_string( $options['dir'] ) ) {
            $options['dir'] = Func::normalize_path( $options['dir'].'/' );
            $options['dbdir'] = $options['dir'].'monodb0/';
        }

        if ( ! empty( $options['dbname'] ) && \is_string( $options['dbname'] ) ) {
            $options['dbdir'] = $options['dir'].'/'.$options['dbname'].'/';
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
}
