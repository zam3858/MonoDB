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

class Monodb {

    const VERSION = '1.0.0';
    const NAME = 'MonoDB';
    const DESC = 'A flat-file key-value data structure';
    const URL = 'https://monodb.io';

    protected $config = [];

    private $chain_blob = false;
    private $chain_meta = false;
    private $chain_encrypt = false;
    private $chain_decrypt = false;
    private $errors = [];

    /**
     * Initialize the class and set its properties.
     */
    public function __construct( array $options = [] ) {
        $this->check_dependencies();
        $this->config = new Config( $options );
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
     * Set class options.
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
        $this->config->set_options( $options );
        return $this;
    }

    /**
     * catch_debug().
     *
     * @access private
     */
    private function catch_debug( $caller, $status ) {
        $log = [
            'timestamp' => gmdate( 'Y-m-d H:i:s' ).' UTC',
            'caller' => $caller,
            'status' => $status
        ];

        if ( is_array( $status ) ) {
            $log = array_merge( $log, $status );
        }

        $this->errors[] = $log;
    }

    /**
     * Return errors log.
     *
     * @access public
     * @return array $errors
     */
    public function last_error() {
         return $this->errors;
    }

    /**
     * Check and replace invalid key.
     *
     * @access private
     * @return string sanitized key
     */
    private function sanitize_key( string $key ) {
        $key_r = preg_replace( '@[^A-Za-z0-9.-:]@', '', $key );
        if ( $key_r !== $key ) {
            $key = substr( $key_r.md5( $key.$key_r.mt_srand() ), 0, 12 );
        }
        return substr( $key, 0, $this->config->key_length );
    }

    /**
     * Set and create data file.
     *
     * @access private
     * @param string $key Data key
     * @param boolean $create (Optional) if set to true, data path will create
     * @return string fulle path of data file
     */
    private function key_path( $key, $create = true ) {
        $key = md5( $key );
        $prefix = substr( $key, 0, 2 );
        $path = $this->config->dbdir.$prefix.'/';
        $key = substr( $key, 2 );

        if ( $create && ! is_dir( $path ) && mkdir( $path, $this->config->perm_dir, true ) ) {
            $id = (string) basename( $path );
            $code = $this->data_code( $id );
            $this->data_save( $path.'index.php', $code );
        }

        return $path.$key.'.php';
    }

    /**
     * Convert data to php script.
     *
     * @access private
     * @param array $data Data to save
     * @return string php script
     */
    private function data_code( $data ) {
        $code = '<?php'.PHP_EOL;
        $code .= 'return '.Func::export_var( $data ).';'.PHP_EOL;
        return $code;
    }

    /**
     * Searches the array for a given value and returns the first corresponding array/string if successful.
     *
     * @access private
     * @param array $array_data Array data to search
     * @param array|string $find_value Array value to find
     * @param array|string $find_key (Optional) Array key to find
     * @return array|string|false Returns array or string if found, false otherwise
     */
    private function array_search_index( $array_data, $find_value, $find_key = '' ) {
        if ( \is_array( $array_data ) ) {
            foreach ( $array_data as $arr_key => $arr_value ) {
                $current_key = $arr_key;

                if ( ( \is_string( $arr_value ) && Func::match_wildcard( $arr_value, $find_value ) )
                    || ( \is_array( $arr_value ) && $this->array_search_index( $arr_value, $find_value, $find_key ) !== false ) ) {

                    // found value
                    $found = $array_data[ $current_key ];

                    if ( \is_array( $found ) && ! empty( $find_key ) ) {
                        $kv = print_r( $found, 1 );

                        if ( preg_match_all( '@(\s*?Array\n*\(\n+)?\s*\[(.*?)\]\s*=\>\s*@m', $kv, $mm ) ) {
                            $keys = $mm[2];
                            foreach ( $keys as $k ) {
                                if ( Func::match_wildcard( $k, $find_key ) ) {
                                    return $found;
                                }
                            }
                        }
                        // null to skip
                        return null;
                    }

                    return ( \is_array( $found ) ? $found : [ $current_key => $found ] );
                }
            }
        }
        return false;
    }

    /**
     * Save data.
     *
     * @access private
     * @param string $file File to save
     * @param string $data Data to save
     * @return boolean Returns true if successful, false otherwise
     */
    private function data_save( $file, $data ) {
        if ( file_put_contents( $file, $data, LOCK_EX ) ) {
            chmod( $file, $this->config->perm_file );
            return true;
        }
        return false;
    }

    /**
     * Update data.
     *
     * @access private
     * @param string $key Data key
     * @param array $data Data to update
     * @return string|false Returns key string if successful, false otherwise
     */
    private function data_update( $key, $data ) {
        if ( ! empty( $data ) && \is_array( $data ) && ! empty( $data['timestamp'] ) ) {
            if ( $this->exists( $key ) ) {
                $file = $this->key_path( $key, false );

                if ( Func::is_file_writable( $file ) ) {
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
     * Read data.
     *
     * @access private
     * @param string $file File to read
     * @return array|false Returns array if successful, false otherwise
     */
    private function data_read( $file ) {
        $data = false;
        try {
            $data = include( $file );
        } catch ( \Exception $e ) {
            $this->catch_debug( __METHOD__, $e->getMessage() );
        }
        return $data;
    }

    /**
     * Save index data.
     *
     * @access private
     * @param string $key Data key
     * @param string $path Data file
     * @param array $item Data item
     * @return boolean Returns true if successful, false otherwise
     */
    private function set_index( $key, $path, $item ) {
        $file = $this->config->dbindex;
        $index = [];
        if ( Func::is_file_readable( $file ) ) {
            $index = $this->data_read( $file );
            if ( empty( $index ) || ! \is_array( $index ) ) {
                $index = [];
            }
        }

        $index[ $key ]['key'] = $key;
        $index[ $key ]['index'] = ltrim( str_replace( ltrim( $this->config->dbdir, './' ), '', trim( $path, '.php' ) ), '/' );
        $index[ $key ]['timestamp'] = $item['timestamp'];
        $index[ $key ]['expiry'] = ( ! empty( $item['expiry'] ) ? $item['expiry'] : 0 );
        $index[ $key ]['type'] = $item['type'];
        $index[ $key ]['size'] = $item['size'];
        $index[ $key ]['encoded'] = ( ! empty( $item['encoded'] ) ? $item['encoded'] : 0 );

        $code = $this->data_code( $index );
        return $this->data_save( $file, $code );
    }

    /**
     * Remove index data.
     *
     * @access private
     * @param string $key Data key
     * @param string $path Data file
     * @return boolean Returns true if successful, false otherwise
     */
    private function unset_index( $key ) {
        $file = $this->config->dbindex;
        $index = [];
        if ( file_exists( $file ) ) {
            $index = $this->data_read( $file );
            if ( ! empty( $index ) && \is_array( $index ) ) {
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
     * Get file content.
     *
     * @access private
     * @param string $data File fullpath
     * @param array $extra_meta (Optional) Meta data to add
     * @return string Returns File content if successful, File fullpath otherwise
     */
    private function fetch_file( $data, &$extra_meta = [] ) {
        if ( \is_string( $data ) && Func::start_with( $data, 'file://' ) ) {
            $src = $data;
            $fi = Func::strip_scheme( $src );
            if ( ! Func::start_with( $fi, [ '.','/' ] ) ) {
                $fi = getcwd().'/'.$fi;
            }
            if ( Func::is_file_readable( $fi ) ) {
                if ( empty( $extra_meta['mime'] ) ) {
                    $mime = mime_content_type( $fi );
                    if ( ! empty( $mime ) ) {
                        $extra_meta['mime'] = $mime;
                    }
                }
                $extra_meta['source'] = $src;
                try {
                    $data = file_get_contents( $fi );
                } catch ( \Exception $e ) {
                    $this->catch_debug( __METHOD__, $e->getMessage() );
                }
            }
        }
        return $data;
    }

    /**
     * Remove data directory if empty.
     *
     * @access private
     * @param string $file Data file
     * @return boolean Returns true if successful, false otherwise
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

        if ( \count( $fc ) <= 1 ) {
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
            'timestamp' => gmdate( 'Y-m-d H:i:s' ).' UTC',
            'key' => $key,
            'type' => Func::get_type( $data ),
            'size' => Func::get_size( $data )
        ];

        if ( 'closure' === $meta['type'] || 'resource' === $meta['type'] ) {
            $this->catch_debug( __METHOD__, 'Data type not supported: '.$meta['type'] );
            return false;
        }

        if ( 'binary' === $meta['type'] ) {
            $blob_size = (int) $meta['size'];
            if ( $blob_size >= $this->config->blob_size ) {
                $this->catch_debug( __METHOD__, 'Maximum binary size exceeded: '.$blob_size );
                return false;
            }

            $data = base64_encode( $data );
            $meta['size'] = \strlen( $data );
            $meta['encoded'] = 1;
        }

        if ( ! empty( $expiry ) && Func::is_var_num( $expiry ) ) {
            $expiry = (int) $expiry;
            if ( $expiry > 0 ) {
                $meta['expiry'] = time() + $expiry;
            }
        } elseif ( ! empty( $this->config->key_expiry ) ) {
            $meta['expiry'] = (int) $this->key_expiry;
        }

        if ( ! empty( $extra_meta ) && \is_array( $extra_meta ) ) {
            $meta = array_merge( $meta, $extra_meta );
        }

        if ( false !== $this->chain_encrypt && \is_string( $this->chain_encrypt ) ) {
            $data = Func::encrypt( $data, $this->chain_encrypt );
            $meta['size'] = \strlen( $data );
            $meta['encoded'] = ( ! empty( $meta['encoded'] ) ? 3 : 2 );
        }
        $this->chain_encrypt = false;

        $meta['value'] = $data;
        $code = $this->data_code( $meta );

        $file = $this->key_path( $key, true );
        if ( $this->data_save( $file, $code ) ) {
            $this->set_index( $key, $file, $meta );
            return $key;
        }

        $this->catch_debug( __METHOD__, 'Failed to set '.$key );
        return false;
    }

    /**
     * get().
     *
     * @access public
     */
    public function get( string $key, &$debug = [] ) {
        $key = $this->sanitize_key( $key );

        if ( ! $this->exists( $key ) ) {
            $debug[] = 'Key '.$key.' not exists';
            return false;
        }

        $file = $this->key_path( $key, false );

        $chain_meta = $this->chain_meta;
        $this->chain_meta = false;

        $chain_blob = $this->chain_blob;
        $this->chain_blob = false;

        if ( Func::is_file_readable( $file ) ) {
            $meta = $this->data_read( $file );
            if ( ! \is_array( $meta ) || empty( $meta ) || ( empty( $meta['value'] ) && 0 !== (int)$meta['value']) ) {
                $this->delete( $key );
                $debug[] = 'Delete Invalid data';
                return false;
            }

            if ( ! empty( $meta['expiry'] ) && Func::is_var_num( $meta['expiry'] ) ) {
                if ( time() >= (int) $meta['expiry'] ) {
                    $this->delete( $key );
                    $debug = [
                            'status' => 'expired',
                            'Key' => $key,
                            'Expiry' => gmdate(
                                'Y-m-d H:i:s',
                                $meta['expiry']
                            )
                        ];
                    $this->catch_debug(__METHOD__, $debug);
                    return false;
                }
            }

            $data = $meta['value'];
            $data_plain = true;
            if ( false !== $this->chain_decrypt && \is_string( $this->chain_decrypt ) && ! empty( $meta['encoded'] ) && ( 2 === (int) $meta['encoded'] || 3 === (int) $meta['encoded'] ) ) {
                $data_r = Func::decrypt( $data, $this->chain_decrypt );
                if ( empty( $data_r ) || ! ctype_print( $data_r ) ) {
                    $data_plain = false;
                } else {
                    $data = $data_r;
                    $meta['encoded'] = ( 3 === (int) $meta['encoded'] ? 1 : 0 );
                }
            }
            $this->chain_decrypt = false;

            if ( $data_plain && $chain_blob && 'binary' === $meta['type'] && ! empty( $meta['encoded'] ) && ( 1 === (int) $meta['encoded'] || 3 === (int) $meta['encoded'] ) ) {
                $data = base64_decode( $data );
                $meta['encoded'] = ( 3 === (int) $meta['encoded'] ? 2 : 0 );
            }

            $meta['value'] = $data;

            return ( ! $chain_meta ? $meta['value'] : $meta );
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
        if ( Func::is_file_writable( $file ) && unlink( $file ) ) {
            $this->unset_index( $key );
            $this->flush_key_path( $file );
            return $key;
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
            $key = $this->delete( $key );
            if ( false !== $key ) {
                $results[] = $key;
            }
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
        if ( ! empty( $keys ) && \is_array( $keys ) ) {
            foreach ( $keys as $key ) {
                if ( false !== $this->delete( $key ) ) {
                    $num++;
                }
            }
        }
        return ( $num > 0 ? $num : false );
    }

    /**
     * find_data().
     *
     * @access private
     */
    private function find_data( string $key, $match ) {
        $meta = $this->meta()->get( $key );
        if ( ! empty( $meta ) && \is_array( $meta ) ) {
            $func_is_invalid = function ( $match, $type ) {
                if ( ! \is_string( $match ) && ! \is_array( $match ) ) {
                    return true;
                }

                if ( \is_array( $match ) && ( empty( $match ) || \count( $match ) !== 2 ) ) {
                    return true;
                }

                if ( 'resource' === $type || 'object' === $type || 'binary' === $type ) {
                    return true;
                }

                return false;
            };

            $func_is_array = function ( $type ) {
                return ( 'array' === $type || 'stdClass' === $type || 'json' === $type );
            };

            $type = $meta['type'];
            $data = $meta['value'];

            if ( $func_is_invalid( $match, $type ) ) {
                return false;
            }

            if ( $func_is_array( $type ) ) {
                if ( 'json' === $type && Func::is_var_json( $data ) ) {
                    $data = json_decode( $data, true );
                } else {
                    $data = Func::object_to_array( $data );
                }
                if ( \is_array( $match ) ) {
                    $found = $this->array_search_index( $data, $match[1], $match[0] );
                    return ( ! empty( $found ) ? $found : false );
                }

                // single
                $found = $this->array_search_index( $data, $match );
                return ( ! empty( $found ) ? $found : false );
            }

            if ( Func::match_wildcard( $data, $match ) ) {
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
        if ( ! empty( $keys ) && \is_array( $keys ) ) {
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
        return Func::is_file_readable( $file );
    }

    /**
     * keys().
     *
     * @access public
     */
    public function keys( string $key = '' ) {
        $file = $this->config->dbindex;
        $chain_meta = $this->chain_meta;
        $this->chain_meta = false;

        if ( Func::is_file_readable( $file ) ) {
            $index = $this->data_read( $file );
            if ( ! empty( $index ) && \is_array( $index ) ) {
                if ( ! empty( $key ) ) {
                    $rindex = [];
                    foreach ( $index as $k => $v ) {
                        if ( Func::match_wildcard( $k, $key ) ) {
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
    public function info( $name = '' ) {
        $info['name'] = $this->name();
        $info['version'] = $this->version();
        foreach ( $this->config as $k => $v ) {
            $info['config'][ $k ] = $v;
        }

        if ( Func::has_with( $name, 'config:' ) ) {
            $info = $info['config'];
            $name = str_replace( 'config:', '', $name );
        }

        return ( ! empty( $info[ $name ] ) ? $info[ $name ] : $info );
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
            if ( ! empty( $data ) && 0 !== (int)$data && Func::is_var_int( $data ) && Func::is_var_int( $num ) ) {
                if ( $num > PHP_INT_MAX ) {
                    return 1;
                }

                $data = ( $data + $num );

                if ( $data > PHP_INT_MAX ) {
                    return PHP_INT_MAX;
                }

                if ( $this->set( $key, $data ) ) {
                    return $data;
                }
            }
        }

        if ( false !== $this->set( $key, 1 ) ) {
            return 1;
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
            if ( ! empty( $data ) && Func::is_var_int( $data ) && Func::is_var_int( $num ) ) {
                if ( $num > PHP_INT_MAX ) {
                    return 1;
                }

                $data = ( $data - $num );
                if ( $data < -PHP_INT_MAX ) {
                    $data = -PHP_INT_MAX;
                }
                if ( $this->set( $key, $data ) ) {
                    return $data;
                }
            }
        }
        
        if ( false !== $this->set( $key, 0 ) ) {
            return 0;
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
        if ( ! empty( $data ) && \is_array( $data ) && ! empty( $data['key'] ) ) {
            if ( ! empty( $expiry ) && Func::is_var_num( $expiry ) ) {
                $expiry = (int) $expiry;
                if ( $expiry > 0 ) {
                    $data['expiry'] = ( time()+$expiry );
                    if ( false !== $this->data_update( $key, $data ) ) {
                        return ['key' => $key, 'expiry' => gmdate('Y-m-d H:i:s', $data['expiry']).' UTC' ];
                    }
                }
            }

            // reset
            $data['expiry'] = 0;
            if ( false !== $this->data_update( $key, $data ) ) {
                return ['key' => $key, 'expiry' => $data['expiry'] ];
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
        $this->chain_meta = ( \is_bool( $enable ) ? $enable : true );
        return $this;
    }

    /**
     * chain blob().
     *
     * @access public
     */
    public function blob( $enable = null ) {
        $this->chain_blob = ( \is_bool( $enable ) ? $enable : true );
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

    public function version() {
        return self::VERSION;
    }

    public function name() {
        return self::NAME;
    }

    public function desc() {
        return self::DESC;
    }

    public function url() {
        return self::URL;
    }
}
