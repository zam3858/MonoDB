<?php
/*
 * This file is part of the MonoDB package.
 *
 * (c) Nawawi Jamili <nawawi@rutweb.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MonoDB;

use MonoDB\Helper as C;
use \Symfony\Component\VarExporter\VarExporter;

class MonoDB {
    private $chain_blob = false;
    private $chain_meta = false;
    private $chain_encrypt = false;
    private $chain_decrypt = false;
    private $errors = [];
    private $config = [];
    public $hook_path = '';
    public $current_path = '';

    /**
     * Initialize the class and set its properties.
     */
    public function __construct( $options = [] ) {
        $this->check_dependencies();

        $this->hook_path = dirname( realpath( __FILE__ ) ).'/';
        $this->current_path = C::normalize_path( getcwd().'/' );

        $this->config = [
            'data_path' => $this->current_path,
            'db_path' => $this->current_path.'monodb0/',
            'key_length' => 50,
            'key_expiry' => 0,
            'blob_size' => 5000000,
            'perm_dir' => 0755,
            'perm_file' => 0644
        ];

        $this->options( $options );
        $this->errors = [];
    }

    /**
     * Destructor: Will run when object is destroyed.
     *
     * @return bool Always true
     */
    public function __destruct() {
        $this->errors = [];
        return true;
    }

    /**
     * Dependecnises check for non composer installation.
     *
     * @return mixed Throw error when failed, true otherwise.
     */
    private function check_dependencies() {
        $php_version = '7.1';
        if ( version_compare( PHP_VERSION, $php_version, '<' ) ) {
            throw new \Exception( 'MonoDB requires PHP Version '.$php_version.' and above.' );
        }

        if ( ! extension_loaded( 'json' ) ) {
            throw new \Exception( 'MonoDB requires json extension.' );
        }

        return true;
    }

    /**
     * Set class options and create data dir.
     *
     * @access public
     * @param array $options
     * @return object class object
     */
    public function options( $options = [] ) {
        $this->chain_blob = false;
        $this->chain_meta = false;
        $this->chain_encrypt = false;
        $this->chain_decrypt = false;

        if ( ! empty( $options ) && is_array( $options ) ) {
            if ( ! empty( $options['path'] ) && is_string( $options['path'] ) ) {
                $this->config['data_path'] = C::normalize_path( $options['path'].'/' );
                $this->config['db_path'] = $this->config['data_path'].'monodb0/';
            }

            if ( ! empty( $options['dbname'] ) && is_string( $options['dbname'] ) ) {
                $this->config['db_path'] = $this->config['data_path'].'/'.$options['dbname'].'/';
            }

            if ( ! empty( $options['key_length'] ) && C::is_var_num( $options['key_length'] ) ) {
                $key_length = (int) $options['key_length'];
                if ( $key_length > 0 ) {
                    $this->config['key_length'] = $key_length;
                }
                $this->config['key_length'] = (int) $options['key_length'];
            }

            if ( ! empty( $options['blob_size'] ) && C::is_var_num( $options['blob_size'] ) ) {
                $blob_size = (int) $options['blob_size'];
                if ( $blob_size > 0 ) {
                    $this->config['blob_size'] = $blob_size;
                }
            }

            if ( ! empty( $options['key_expiry'] ) && C::is_var_time( $options['key_expiry'] ) ) {
                $key_expiry = (int) $options['key_expiry'];
                if ( $key_expiry > 0 ) {
                    $this->config['key_expiry'] = $key_expiry;
                }
            }

            if ( ! empty( $options['perm_dir'] ) && C::is_var_num( $options['perm_dir'] ) ) {
                $this->config['perm_dir'] = $options['perm_dir'];
            }

            if ( ! empty( $options['perm_file'] ) && C::is_var_num( $options['perm_file'] ) ) {
                $this->config['perm_file'] = $options['perm_file'];
            }
        }

        $this->config['db_path'] = C::normalize_path( $this->config['db_path'] );
        $this->config['db_index'] = $this->config['db_path'].'index.php';
        return $this;
    }

    /**
     * catch_error().
     *
     * @access private
     */
    private function catch_error( $name, $text ) {
        $this->errors[] = [
            'timestamp' => gmdate( 'Y-m-d H:i:s' ).' UTC',
            'caller' => $name,
            'status' => $text
        ];
    }

    /**
     * last_error().
     *
     * @access private
     */
    public function last_error() {
        return $this->errors;
    }

    /**
     * create_data_dir().
     *
     * @access private
     */
    private function create_data_dir() {
        if ( ! is_dir( $this->config['db_path'] ) && ! mkdir( $this->config['db_path'], $this->config['perm_dir'], true ) ) {
            return false;
        }
        return true;
    }

    /**
     * sanitize_key().
     *
     * @access private
     */
    private function sanitize_key( string $key ) {
        $key_r = preg_replace( '@[^A-Za-z0-9.-:]@', '', $key );
        if ( $key_r !== $key ) {
            $key = md5( $key );
        }
        return substr( $key, 0, $this->config['key_length'] );
    }

    /**
     * key_path().
     *
     * @access private
     */
    private function key_path( $key, $create = true ) {
        $key = md5( $key );
        $prefix = substr( $key, 0, 2 );
        $path = $this->config['db_path'].$prefix.'/';
        $key = substr( $key, 2 );

        if ( $create && ! is_dir( $path ) && mkdir( $path, $this->config['perm_dir'], true ) ) {
            $id = (int) basename( $path );
            $code = $this->data_code( $id );
            $this->data_save( $path.'index.php', $code );
        }

        return $path.$key.'.php';
    }

    /**
     * data_code().
     *
     * @access private
     */
    private function data_code( $data ) {
        $code = '<?php'.PHP_EOL;
        $code .= 'return '.$this->export_var( $data ).';'.PHP_EOL;
        return $code;
    }

    /**
     * var_export().
     *
     * @access private
     */
    private function export_var( $data ) {
        $code = '';
        try {
            $code = VarExporter::export( $data );
        } catch ( \Exception $e ) {
            $this->catch_error( __METHOD__, $e->getMessage() );
            return false;
        }
        return $code;
    }

    /**
     * array_search_index().
     *
     * @access private
     */
    private function array_search_index( $array_data, $find_value, $find_key = '' ) {
        if ( is_array( $array_data ) ) {

            foreach ( $array_data as $arr_key => $arr_value ) {
                $current_key = $arr_key;

                if ( C::match_wildcard( $arr_value, $find_value )
                    || ( is_array( $arr_value ) && $this->array_search_index( $arr_value, $find_value, $find_key ) !== false ) ) {

                    // found value
                    $found = $array_data[ $current_key ];

                    if ( is_array( $found ) && ! empty( $find_key ) ) {
                        $kv = print_r( $found, 1 );

                        if ( preg_match_all( '@(\s*?Array\n*\(\n+)?\s*\[(.*?)\]\s*=\>\s*@m', $kv, $mm ) ) {
                            $keys = $mm[2];
                            foreach ( $keys as $k ) {
                                if ( C::match_wildcard( $k, $find_key ) ) {
                                    return $found;
                                }
                            }
                        }
                        // null to skip
                        return null;
                    }

                    return ( is_array( $found ) ? $found : [ $current_key => $found ] );
                }
            }
        }
        return false;
    }

    /**
     * data_save().
     *
     * @access private
     */
    private function data_save( $file, $data ) {
        if ( file_put_contents( $file, $data, LOCK_EX ) ) {
            chmod( $file, $this->config['perm_file'] );
            return true;
        }
        return false;
    }

    /**
     * data_update().
     *
     * @access private
     */
    private function data_update( $key, $data ) {
        if ( ! empty( $data ) && is_array( $data ) && ! empty( $data['timestamp'] ) ) {
            if ( $this->exists( $key ) ) {
                $file = $this->key_path( $key, false );

                if ( C::is_file_writable( $file ) ) {
                    $data['timestamp'] = gmdate( 'Y-m-d H:i:s' ).' UTC';

                    $code = $this->data_code( $data );
                    if ( $this->data_save( $file, $code ) ) {
                        $this->set_index( $key, $file, $data );
                        return $key;
                    }
                }
            }
        }
        return false;
    }

    /**
     * data_read().
     *
     * @access private
     */
    private function data_read( $file ) {
        $data = false;
        try {
            $data = include( $file );
        } catch ( \Exception $e ) {
            $this->catch_error( __METHOD__, $e->getMessage() );
        }
        return $data;
    }

    /**
     * set_index().
     *
     * @access private
     */
    private function set_index( $key, $path, $item ) {
        $file = $this->config['db_index'];
        $index = [];
        if ( file_exists( $file ) ) {
            $index = $this->data_read( $file );
            if ( empty( $index ) || ! is_array( $index ) ) {
                $index = [];
            }
        }

        $index[ $key ]['timestamp'] = $item['timestamp'];
        if ( ! empty( $item['expiry'] ) ) {
            $index[ $key ]['expiry'] = $item['expiry'];
        }
        $index[ $key ]['index'] = str_replace( $this->config['db_path'], '', trim( $path, '.php' ) );
        $index[ $key ]['size'] = $item['size'];
        $index[ $key ]['type'] = $item['type'];
        if ( ! empty( $item['serialized'] ) ) {
            $index[ $key ]['serialized'] = $item['serialized'];
        }
        if ( ! empty( $item['encoded'] ) ) {
            $index[ $key ]['encoded'] = $item['encoded'];
        }
        $code = $this->data_code( $index );
        return $this->data_save( $file, $code );
    }

    /**
     * unset_index().
     *
     * @access private
     */
    private function unset_index( $key ) {
        $file = $this->config['db_index'];
        $index = [];
        if ( file_exists( $file ) ) {
            $index = $this->data_read( $file );
            if ( ! empty( $index ) && is_array( $index ) ) {
                if ( ! empty( $index[ $key ] ) ) {
                    unset( $index[ $key ] );
                }

                $code = $this->data_code( $index );
                return $this->data_save( $file, $code );
            }
        }
        return false;
    }

    /**
     * fetch_file().
     *
     * @access private
     */
    private function fetch_file( $data, &$extra_meta = [] ) {
        if ( is_string( $data ) && C::start_with( $data, 'file://' ) ) {
            $src = $data;
            $fi = C::strip_scheme( $src );
            if ( ! C::start_with( $fi, [ '.','/' ] ) ) {
                $fi = getcwd().'/'.$fi;
            }
            if ( C::is_file_readable( $fi ) ) {
                if ( empty( $extra_meta['mime'] ) ) {
                    $mime = mime_content_type( $fi );
                    if ( ! empty( $mime ) ) {
                        $extra_meta['mime'] = $mime;
                    }
                }
                $extra_meta['fetch'] = $src;
                try {
                    $data = file_get_contents( $fi );
                } catch ( \Exception $e ) {
                    $this->catch_error( __METHOD__, $e->getMessage() );
                }
            }
        }
        return $data;
    }

    /**
     * flush_key_path().
     *
     * @access private
     */
    private function flush_key_path( $file ) {
        $dir = dirname( $file );
        if ( empty( $dir ) || '/' === $dir || ! is_dir( $dir ) ) {
            return false;
        }

        $fc = glob( $dir.'/*.php' );
        if ( empty( $fc ) ) {
            return rmdir( $dir.'/' );
        }

        if ( ! file_exists( $dir.'/index.php' ) ) {
            touch( $dir.'/index.php' );
        }

        if ( count( $fc ) <= 1 ) {
            array_map( 'unlink', $fc );
            return rmdir( $dir.'/' );
        }

        return false;
    }

    /**
     * set().
     *
     * @access public
     */
    public function set( string $key, $data, $expiry = 0, $extra_meta = [] ) {
        $key = $this->sanitize_key( $key );
        $data = $this->fetch_file( $data, $extra_meta );

        $meta = [
            'key' => $key,
            'timestamp' => gmdate( 'Y-m-d H:i:s' ).' UTC',
            'type' => C::get_type( $data ),
            'size' => C::get_size( $data )
        ];

        if ( 'closure' === $meta['type'] || 'resource' === $meta['type'] ) {
            $this->catch_error( __METHOD__, 'Data type not supported: '.$meta['type'] );
            return false;
        }

        if ( 'binary' === $meta['type'] ) {
            $blob_size = (int) $meta['size'];
            if ( $blob_size >= $this->config['blob_size'] ) {
                $this->catch_error( __METHOD__, 'Maximum binary size exceeded: '.$blob_size );
                return false;
            }

            $data = base64_encode( $data );
            $meta['encoded'] = strlen( $data );
        }

        if ( ! empty( $expiry ) && C::is_var_time( $expiry ) ) {
            $expiry = (int) $expiry;
            if ( $expiry > 0 ) {
                $meta['expiry'] = $expiry;
            }
        } elseif ( ! empty( $this->config['key_expiry'] ) ) {
            $meta['expiry'] = (int) $this->key_expiry;
        }

        if ( ! empty( $extra_meta ) && is_array( $extra_meta ) ) {
            $meta = array_merge( $meta, $extra_meta );
        }

        if ( false !== $this->chain_encrypt && is_string( $this->chain_encrypt ) ) {
            $data = C::base64_encrypt( $data, $this->chain_encrypt );
            $meta['chain_encrypt'] = 1;
            $meta['length'] = strlen( $data );
        }
        $this->chain_encrypt = false;

        $meta['data'] = $data;
        $code = $this->data_code( $meta );

        $file = $this->key_path( $key, true );
        if ( $this->data_save( $file, $code ) ) {
            $this->set_index( $key, $file, $meta );
            return $key;
        }

        $this->catch_error( __METHOD__, 'Failed to set '.$key );
        return false;
    }

    /**
     * get().
     *
     * @access public
     */
    public function get( string $key ) {
        $key = $this->sanitize_key( $key );

        if ( ! $this->exists( $key ) ) {
            return false;
        }

        $file = $this->key_path( $key, false );

        $chain_meta = $this->chain_meta;
        $this->chain_meta = false;

        $chain_blob = $this->chain_blob;
        $this->chain_blob = false;

        if ( C::is_file_readable( $file ) ) {
            $meta = $this->data_read( $file );
            if ( ! is_array( $meta ) || empty( $meta ) || empty( $meta['data'] ) ) {
                $this->delete( $key );
                return false;
            }

            if ( ! empty( $meta['expiry'] ) && C::is_var_time( $meta['expiry'] ) ) {
                if ( time() >= (int) $meta['expiry'] ) {
                    $this->delete( $key );
                    $this->catch_error( __METHOD__, 'expired: '.$key );
                    return false;
                }
            }

            $data = $meta['data'];
            if ( false !== $this->chain_decrypt && is_string( $this->chain_decrypt ) ) {
                $data = C::base64_decrypt( $data, $this->chain_decrypt );
                unset( $meta['chain_encrypt'] );
            }
            $this->chain_decrypt = false;

            if ( 'binary' === $meta['type'] && $chain_blob ) {
                $data = base64_decode( $data );
            }

            $meta['data'] = $data;

            return ( ! $chain_meta ? $meta['data'] : $meta );
        }

        return false;
    }

    /**
     * mget().
     *
     * @access public
     */
    public function mget( ...$keys ) {
        $results = [];
        foreach ( $keys as $key ) {
            $results[ $key ] = $this->get( $key );
        }
        return $results;
    }

    /**
     * delete().
     *
     * @access public
     */
    public function delete( string $key ) {
        $key = $this->sanitize_key( $key );
        $file = $this->key_path( $key, false );
        if ( C::is_file_writable( $file ) && unlink( $file ) ) {
            $this->unset_index( $key );
            $this->flush_key_path( $file );
            return true;
        }
        return false;
    }

    /**
     * mdelete().
     *
     * @access public
     */
    public function mdelete( ...$keys ) {
        $results = [];
        foreach ( $keys as $key ) {
            $results[ $key ] = ( $this->delete( $key ) ? 'true' : 'false' );
        }
        return $results;
    }

    /**
     * flush().
     *
     * @access public
     */
    public function flush() {
        $keys = $this->keys();
        $num = 0;
        if ( ! empty( $keys ) && is_array( $keys ) ) {
            foreach ( $keys as $key ) {
                if ( $this->delete( $key ) ) {
                    $num++;
                }
            }
        }
        return $num;
    }

    /**
     * find_data().
     *
     * @access private
     */
    private function find_data( string $key, $match ) {
        $meta = $this->meta()->get( $key );
        if ( ! empty( $meta ) && is_array( $meta ) ) {

            $func_is_invalid = function( $match, $type ) {
                if ( ! is_string( $match ) && ! is_array( $match ) ) {
                    return true;
                }

                if ( is_array( $match ) && ( empty( $match ) || count( $match ) !== 2 ) ) {
                    return true;
                }

                if ( 'resource' === $type || 'object' === $type || 'binary' === $type ) {
                    return true;
                }

                return false;
            };

            $func_is_array = function( $type ) {
                return ( 'array' === $type || 'stdClass' === $type );
            };

            $type = $meta['type'];
            $data = $meta['data'];

            if ( $func_is_invalid( $match, $type ) ) {
                return false;
            }

            if ( $func_is_array( $type ) ) {
                $data = C::object_to_array( $data );
                if ( is_array( $match ) ) {
                    $found = $this->array_search_index( $data, $match[1], $match[0] );
                    return ( ! empty( $found ) ? $found : false );
                }

                // single
                $found = $this->array_search_index( $data, $match );
                return ( ! empty( $found ) ? $found : false );
            }

            if ( C::match_wildcard( $data, $match ) ) {
                return $data;
            }
        }
        return false;
    }

    /**
     * find_all().
     *
     * @access public
     */
    public function find_all( $match ) {
        $results = [];
        $keys = $this->keys();
        if ( ! empty( $keys ) && is_array( $keys ) ) {
            foreach ( $keys as $key ) {
                $found = $this->find_data( $key, $match );
                if ( ! empty( $found ) ) {
                    $results[ $key ] = $found;
                }
            }
        }
        return $results;
    }

    /**
     * find().
     *
     * @access public
     */
    public function find( string $key, $match ) {
        if ( '*' === $key ) {
            return $this->find_all( $match );
        }
        return $this->find_data( $key, $match );
    }

    /**
     * exists().
     *
     * @access public
     */
    public function exists( string $key ) {
        $key = $this->sanitize_key( $key );
        $file = $this->key_path( $key, false );
        return C::is_file_readable( $file );
    }

    /**
     * keys().
     *
     * @access public
     */
    public function keys( string $key = '' ) {
        $file = $this->config['db_index'];
        $chain_meta = $this->chain_meta;
        $this->chain_meta = false;

        if ( C::is_file_readable( $file ) ) {
            $index = $this->data_read( $file );
            if ( ! empty( $index ) && is_array( $index ) ) {
                if ( ! empty( $key ) ) {
                    $rindex = [];
                    foreach ( $index as $k => $v ) {
                        if ( C::match_wildcard( $k, $key ) ) {
                            if ( $chain_meta ) {
                                $rindex[ $k ] = $v;
                            } else {
                                $rindex[] = $k;
                            }
                        }
                    }
                    if ( ! empty( $rindex ) ) {
                        return $rindex;
                    }
                    return false;
                }

                if ( ! $chain_meta ) {
                    $index = array_keys( $index );
                }

                return $index;
            }
        }
        return false;
    }

    /**
     * chain select().
     *
     * @access public
     */
    public function select( string $dbname ) {
        return $this->options( [ 'dbname' => $dbame ] );
    }

    /**
     * info().
     *
     * @access public
     */
    public function info() {
        $config = (object) $this->config;
        return [
            'data_path' => $config->data_path,
            'db_path'   => $config->db_path,
            'db_index'  => $config->db_index
        ];
    }

    /**
     * incr().
     *
     * @access public
     */
    public function incr( string $key, $num = '' ) {
        $num = ( ! empty( $num ) ? $num : 1 );
        if ( $this->exists( $key ) ) {
            $data = $this->get( $key );
            if ( ! empty( $data ) && C::is_var_int( $data ) && C::is_var_int( $num ) ) {
                $data = (int) $data + (int) $num;
                if ( $data < 0 ) {
                    $data = 1;
                }
                if ( $this->set( $key, $data ) ) {
                    return $data;
                }
            }
        } else {
            if ( false !== $this->set( $key, 1 ) ) {
                return 1;
            }
        }
        return false;
    }

    /**
     * decr().
     *
     * @access public
     */
    public function decr( string $key, $num = '' ) {
        $num = ( ! empty( $num ) ? $num : 1 );
        if ( $this->exists( $key ) ) {
            $data = $this->get( $key );
            if ( ! empty( $data ) && C::is_var_int( $data ) && C::is_var_int( $num ) ) {
                $data = (int) $data - (int) $num;
                if ( $data < 0 ) {
                    $data = 0;
                }
                if ( $this->set( $key, $data ) ) {
                    return $data;
                }
            }
        } else {
            if ( false !== $this->set( $key, 0 ) ) {
                return 0;
            }
        }
        return false;
    }

    /**
     * expire().
     *
     * @access public
     */
    public function expire( string $key, $expiry = 0 ) {
        $data = $this->meta()->get( $key );
        if ( ! empty( $data ) && is_array( $data ) && ! empty( $data['key'] ) && ! empty( $expiry ) && C::is_var_time( $expiry ) ) {
            $expiry = (int) $expiry;
            if ( $expiry > 0 ) {
                $data['expiry'] = $expiry;
                return $this->data_update( $key, $data );
            }
        }
        return false;
    }

    /**
     * chain meta().
     *
     * @access public
     */
    public function meta( $enable = null ) {
        $this->chain_meta = ( is_bool( $enable ) ? $enable : true );
        return $this;
    }

    /**
     * chain blob().
     *
     * @access public
     */
    public function blob( $enable = null ) {
        $this->chain_blob = ( is_bool( $enable ) ? $enable : true );
        return $this;
    }

    /**
     * chain encrypt().
     *
     * @access public
     */
    public function encrypt( string $secret = '' ) {
        $this->chain_encrypt = $secret;
        return $this;
    }

    /**
     * chain decrypt().
     *
     * @access public
     */
    public function decrypt( string $secret = '' ) {
        $this->chain_decrypt = $secret;
        return $this;
    }
}
