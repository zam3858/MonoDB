<?php
/*
    MonoDB - A simple flat-file key-value data structure store, used as a database,
    cache and message broker.

    Copyright 2020 Nawawi Jamili <nawawi@rutweb.com>
    All rights reserved.

    MonoDB is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/
namespace MonoDB;
class MonoDB {
    private $chain_blob = false;
    private $chain_meta = false;
    private $chain_encrypt = false;
    private $chain_decrypt = false;
    private $errors = [];
    private $config = [];

    /**
     * Initialize the class and set its properties.
     */
    public function __construct( $options = [] ) {
        $this->check_dependencies();

        $root_path = $this->normalize_path( getcwd().'/' );
        $this->config = [
            'data_path' => $root_path,
            'db_path' => $root_path.'monodb0/',
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

    private function check_dependencies() {
        $php_version = '5.6';
        if ( version_compare( PHP_VERSION, $php_version, '<' ) ) {
            throw new \Exception( 'MonoDB requires PHP Version '.$php_version.' and above.' );
        }

        foreach ( [ 'ctype','json' ] as $ext ) {
            if ( ! extension_loaded( $ext ) ) {
                throw new \Exception( 'MonoDB requires '.$ext.' extension.' );
            }
        }
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
                $this->config['data_path'] = $this->normalize_path( $options['path'].'/' );
            }

            if ( ! empty( $options['dbname'] ) && is_string( $options['dbname'] ) ) {
                $this->config['db_path'] = $this->config['data_path'].'/'.$options['dbname'].'/';
            }

            if ( ! empty( $options['key_length'] ) && $this->is_num( $options['key_length'] ) ) {
                $key_length = (int) $options['key_length'];
                if ( $key_length > 0 ) {
                    $this->config['key_length'] = $key_length;
                }
                $this->config['key_length'] = (int) $options['key_length'];
            }

            if ( ! empty( $options['blob_size'] ) && $this->is_num( $options['blob_size'] ) ) {
                $blob_size = (int) $options['blob_size'];
                if ( $blob_size > 0 ) {
                    $this->config['blob_size'] = $blob_size;
                }
            }

            if ( ! empty( $options['key_expiry'] ) && $this->is_time( $options['key_expiry'] ) ) {
                $key_expiry = (int) $options['key_expiry'];
                if ( $key_expiry > 0 ) {
                    $this->config['key_expiry'] = $key_expiry;
                }
            }

            if ( ! empty( $options['perm_dir'] ) && $this->is_num( $options['perm_dir'] ) ) {
                $this->config['perm_dir'] = $options['perm_dir'];
            }

            if ( ! empty( $options['perm_file'] ) && $this->is_num( $options['perm_file'] ) ) {
                $this->config['perm_file'] = $options['perm_file'];
            }
        }

        $this->config['db_path'] = $this->normalize_path( $this->config['db_path'] );
        $this->config['db_index'] = $this->config['db_path'].'index.php';
        $this->create_data_dir();

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
     * has_with().
     *
     * @access private
     */
    private function has_with( $haystack, $needles ) {
        foreach ( (array) $needles as $needle ) {
            if ( false !== strpos( $haystack, (string) $needle ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * end_with().
     *
     * @access private
     */
    private function end_with( $haystack, $needles ) {
        foreach ( (array) $needles as $needle ) {
            if ( substr( $haystack, -strlen( $needle ) ) === (string) $needle ) {
                return true;
            }
        }
        return false;
    }

    /**
     * start_with().
     *
     * @access private
     */
    private function start_with( $haystack, $needles ) {
        foreach ( (array) $needles as $needle ) {
            if ( '' !== $needle && 0 === strpos( $haystack, $needle ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * is_file_readable().
     *
     * @access private
     */
    private function is_file_readable( $file ) {
        if ( is_file( $file ) && is_readable( $file ) ) {
            clearstatcache( true, $file );
            return true;
        }
        return false;
    }

    /**
     * is_file_writable().
     *
     * @access private
     */
    private function is_file_writable( $file ) {
        if ( is_file( $file ) && is_writable( $file ) ) {
            clearstatcache( true, $file );
            return true;
        }
        return false;
    }

    /**
     * is_binary().
     *
     * @access private
     */
    private function is_binary( $blob ) {
        if ( is_null( $blob ) || is_integer( $blob ) ) {
            return false;
        }
        return ! ctype_print( $blob );
    }

    /**
     * is_json().
     *
     * @access private
     */
    private function is_json( $string ) {
        return (
            is_string( $string )
            && is_array( json_decode( $string, true ) )
            && ( JSON_ERROR_NONE === json_last_error() ) ? true : false
        );
    }

    /**
     * is_num().
     *
     * @access private
     */
    private function is_num( $num ) {
        return preg_match( '@^\d+$@', (string) $num );
    }

    /**
     * is_int().
     *
     * @access private
     */
    private function is_int( $num ) {
        return preg_match( '@^(\-)?\d+$@', (string) $num );
    }

    /**
     * is_time().
     *
     * @access private
     */
    private function is_time( $num ) {
        if ( $this->is_num( $num ) && $num < PHP_INT_MAX ) {
            if ( false !== date( 'Y-m-d H:i:s', (int) $num ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * is_stdclass().
     *
     * @access private
     */
    private function is_stdclass( $object ) {
        if ( $object instanceof stdClass ) {
            return true;
        }

        if ( preg_match( '@^stdClass\:\:@', var_export( $object, 1 ) ) ) {
            return true;
        }

        return false;
    }

    /**
     * strip_scheme().
     *
     * @access private
     */
    private function strip_scheme( $string ) {
        return preg_replace( '@^(file://|https?://|//)@', '', trim( $string ) );
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
     * base64_encrypt().
     *
     * @access private
     */
    private function base64_encrypt( $string, $epad = '!!$$@#%^&!!' ) {
        $mykey = '!!$'.$epad.'!!';
        $pad = base64_decode( $mykey );
        $encrypted = '';
        for ( $i = 0; $i < strlen( $string ); $i++ ) {
            $encrypted .= chr( ord( $string[ $i ] ) ^ ord( $pad[ $i ] ) );
        }
        return strtr( base64_encode( $encrypted ), '=/', '$@' );
    }

    /**
     * base64_decrypt().
     *
     * @access private
     */
    private function base64_decrypt( $string, $epad = '!!$$@#%^&!!' ) {
        $mykey = '!!$'.$epad.'!!';
        $pad = base64_decode( $mykey );
        $encrypted = base64_decode( strtr( $string, '$@', '=/' ) );
        $decrypted = '';
        for ( $i = 0; $i < strlen( $encrypted ); $i++ ) {
            $decrypted .= chr( ord( $encrypted[ $i ] ) ^ ord( $pad[ $i ] ) );
        }
        return $decrypted;
    }

    /**
     * sanitize_key().
     *
     * @access private
     */
    private function sanitize_key( $key ) {
        if ( is_array( $key ) || is_object( $key ) || is_resource( $key ) ) {
            return md5( serialize( $key ) );
        }

        if ( $this->is_binary( $key ) ) {
            return md5( base64_encode( $key ) );
        }

        $key_r = preg_replace( '@[^A-Za-z0-9.-:]@', '', $key );
        if ( $key_r !== $key ) {
            return md5( $key );
        }

        return substr( $key, 0, $this->config['key_length'] );
    }

    /**
     * key_path().
     *
     * @access private
     */
    private function key_path( $key ) {
        $key = md5( $key );
        $prefix = substr( $key, 0, 2 );
        $path = $this->config['db_path'].$prefix.'/';
        $key = substr( $key, 2 );

        if ( ! is_dir( $path ) && mkdir( $path, $this->config['perm_dir'], true ) ) {
            touch( $path.'index.php' );
            chmod( $path.'index.php', $this->config['perm_file'] );
        }

        return $path.$key.'.php';
    }

    /**
     * match_wildcard(().
     *
     * @access private
     */
    private function match_wildcard( $string, $matches ) {
        if ( is_string( $string ) ) {
            foreach ( (array) $matches as $match ) {
                if ( is_string( $match ) ) {

                    if ( $this->has_with( $match, [ '*', '?' ] ) ) {
                        $wildcard_chars = [ '\*', '\?' ];
                        $regexp_chars = [ '.*', '.' ];
                        $regex = str_replace( $wildcard_chars, $regexp_chars, preg_quote( $match, '@' ) );

                        if ( preg_match( '@^'.$regex.'$@is', $string ) ) {
                            return true;
                        }
                    } elseif ( $string === $match ) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * normalize_path().
     *
     * @access private
     */
    private function normalize_path( $path ) {
        $path = str_replace( '\\', '/', $path );
        $path = '/'.$path.'/';
        return preg_replace( '@[/]+@', '/', $path );
    }

    /**
     * object_to_array().
     *
     * @access private
     */
    private function object_to_array( $object ) {
        return json_decode( json_encode( $object ), true );
    }

    /**
     * data_type().
     *
     * @access private
     */
    private function data_type( $data ) {
        $type = gettype( $data );

        switch ( $type ) {
            case 'object':
                if ( $this->is_stdclass( $data ) ) {
                    $type = 'stdClass';
                }
                break;
            case 'string':
                if ( $this->is_json( $data ) ) {
                    $type = 'json';
                } elseif ( $this->is_binary( $data ) ) {
                    $type = 'binary';
                }
                break;
        }

        return $type;
    }

    /**
     * data_serialize().
     *
     * @access private
     */
    private function data_serialize( $data, $type, &$meta ) {
        switch ( $type ) {
            case 'stdClass':
            case 'object':
            case 'resource':
                $data = serialize( $data );
                $meta['serialized'] = strlen( $data );
                break;
            case 'binary':
                $data = base64_encode( $data );
                $meta['encoded'] = strlen( $data );
                break;
        }

        return $data;
    }

    /**
     * data_unserialize().
     *
     * @access private
     */
    private function data_unserialize( $data, $type ) {
        $chain_blob = $this->chain_blob;
        $this->chain_blob = false;

        switch ( $type ) {
            case 'stdClass':
            case 'object':
            case 'resource':
                $data = unserialize( $data );
                break;
            case 'binary':
                if ( $chain_blob ) {
                    $data = base64_decode( $data );
                }
                break;
        }

        return $data;
    }

    /**
     * data_size().
     *
     * @access private
     */
    private function data_size( $data ) {
        return ( is_array( $data ) || is_object( $data ) ? sizeof( (array) $data ) : strlen( $data ) );
    }

    /**
     * data_code().
     *
     * @access private
     */
    private function data_code( $data ) {
        $code = '<?php'.PHP_EOL;
        $code .= 'return '.$this->array_export( $data ).';'.PHP_EOL;
        return $code;
    }

    /**
     * array_export().
     *
     * @access private
     */
    private function array_export( $data ) {
        if ( ! is_array( $data ) ) {
            return '[]';
        }

        $data = $this->object_to_array( $data );
        $data_e = var_export( $data, true );
        $data_e = preg_replace( '/^([ ]*)(.*)/m', '$1$1$2', $data_e );
        $data_r = preg_split( "/\r\n|\n|\r/", $data_e );

        $data_r = preg_replace( [ '/\s*array\s\($/', '/\)(,)?$/', '/\s=>\s$/' ], [ null, ']$1', ' => [' ], $data_r );
        $data_r = join( PHP_EOL, array_filter( [ '[' ] + $data_r ) );
        if ( '[' === $data_r ) {
            $data_r = '[]';
        }
        return $data_r;
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

                if ( $this->match_wildcard( $arr_value, $find_value )
                    || ( is_array( $arr_value ) && $this->array_search_index( $arr_value, $find_value, $find_key ) !== false ) ) {

                    // found value
                    $found = $array_data[ $current_key ];

                    if ( is_array( $found ) && ! empty( $find_key ) ) {
                        $kv = print_r( $found, 1 );

                        if ( preg_match_all( '@(\s*?Array\n*\(\n+)?\s*\[(.*?)\]\s*=\>\s*@m', $kv, $mm ) ) {
                            $keys = $mm[2];
                            foreach ( $keys as $k ) {
                                if ( $this->match_wildcard( $k, $find_key ) ) {
                                    return $found;
                                }
                            }
                        }
                        // null to skip
                        return null;
                    }

                    return ( is_array($found) ? $found : [$current_key=>$found] );
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
                $file = $this->key_path( $key );

                if ( $this->is_file_writable( $file ) ) {
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
        if ( is_string( $data ) && $this->start_with( $data, 'file://' ) ) {
            $src = $data;
            $fi = $this->strip_scheme( $src );
            if ( ! $this->start_with( $fi, [ '.','/' ] ) ) {
                $fi = getcwd().'/'.$fi;
            }
            if ( $this->is_file_readable( $fi ) ) {
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
     * set().
     *
     * @access public
     */
    public function set( $key, $data, $expiry = 0, $extra_meta = [] ) {
        $key = $this->sanitize_key( $key );
        $file = $this->key_path( $key );

        $data = $this->fetch_file( $data, $extra_meta );

        $meta = [
            'key' => $key,
            'timestamp' => gmdate( 'Y-m-d H:i:s' ).' UTC',
            'type' => $this->data_type( $data ),
            'size' => $this->data_size( $data )
        ];

        if ( 'binary' === $meta['type'] ) {
            $blob_size = (int) $meta['size'];
            if ( $blob_size >= $this->config['blob_size'] ) {
                $this->catch_error( __METHOD__, 'Maximum binary size exceeded: '.$blob_size );
                return false;
            }
        }

        if ( ! empty( $expiry ) && $this->is_time( $expiry ) ) {
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

        $data = $this->data_serialize( $data, $meta['type'], $meta );
        if ( false !== $this->chain_encrypt && is_string( $this->chain_encrypt ) ) {
            $data = $this->base64_encrypt( $data, $this->chain_encrypt );
            $meta['chain_encrypt'] = 1;
            $meta['length'] = strlen( $data );
        }
        $this->chain_encrypt = false;

        $meta['data'] = $data;
        $code = $this->data_code( $meta );

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
    public function get( $key ) {
        $key = $this->sanitize_key( $key );
        $file = $this->key_path( $key );

        $chain_meta = $this->chain_meta;
        $this->chain_meta = false;

        if ( $this->is_file_readable( $file ) ) {
            $meta = $this->data_read( $file );
            if ( ! is_array( $meta ) || empty( $meta ) || empty( $meta['data'] ) ) {
                $this->delete( $key );
                return false;
            }

            if ( ! empty( $meta['expiry'] ) && $this->is_num( $meta['expiry'] ) ) {
                if ( time() >= (int) $meta['expiry'] ) {
                    $this->delete( $key );
                    $this->catch_error( __METHOD__, 'expired: '.$key );
                    return false;
                }
            }

            $data = $meta['data'];
            if ( false !== $this->chain_decrypt && is_string( $this->chain_decrypt ) ) {
                $data = $this->base64_decrypt( $data, $this->chain_decrypt );
                unset( $meta['chain_encrypt'] );
            }
            $this->chain_decrypt = false;

            $meta['data'] = $this->data_unserialize( $data, $meta['type'] );

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
    public function delete( $key ) {
        $key = $this->sanitize_key( $key );
        $file = $this->key_path( $key );
        if ( unlink( $file ) ) {
            $this->unset_index( $key );
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
     * _find().
     *
     * @access private
     */
    private function _find_helper( $key, $match ) {
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
                $data = $this->object_to_array( $data );
                if ( is_array( $match ) ) {
                    $found = $this->array_search_index( $data, $match[1], $match[0] );
                    return ( !empty($found) ? $found : false );
                }

                // single
                $found = $this->array_search_index( $data, $match );
                return ( !empty($found) ? $found : false );
            }

            if ( $this->match_wildcard( $data, $match ) ) {
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
        if ( !empty($keys) && is_array($keys) ) {
            foreach($keys as $key) {
                $found = $this->_find_helper($key, $match);
                if ( !empty($found) ) {
                    $results[$key] = $found;
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
    public function find( $key, $match ) {
        if ( '*' === $key ) {
            return $this->find_all($match);
        }
        return $this->_find_helper($key, $match);
    }

    /**
     * exists().
     *
     * @access public
     */
    public function exists( $key ) {
        $key = $this->sanitize_key( $key );
        $file = $this->key_path( $key );
        return $this->is_file_readable( $file );
    }

    /**
     * keys().
     *
     * @access public
     */
    public function keys( $key = '' ) {
        $file = $this->config['db_index'];
        $chain_meta = $this->chain_meta;
        $this->chain_meta = false;

        if ( $this->is_file_readable( $file ) ) {
            $index = $this->data_read( $file );
            if ( ! empty( $index ) && is_array( $index ) ) {
                if ( ! empty( $key ) ) {
                    $rindex = [];
                    foreach ( $index as $k => $v ) {
                        if ( $this->match_wildcard( $k, $key ) ) {
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
    public function select( $dbname ) {
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
    public function incr( $key, $num = '' ) {
        $num = ( ! empty( $num ) ? $num : 1 );
        if ( $this->exists( $key ) ) {
            $data = $this->get( $key );
            if ( ! empty( $data ) && $this->is_int( $data ) && $this->is_int( $num ) ) {
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
    public function decr( $key, $num = '' ) {
        $num = ( ! empty( $num ) ? $num : 1 );
        if ( $this->exists( $key ) ) {
            $data = $this->get( $key );
            if ( ! empty( $data ) && $this->is_int( $data ) && $this->is_int( $num ) ) {
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
    public function expire( $key, $expiry = 0 ) {
        $data = $this->meta()->get( $key );
        if ( ! empty( $data ) && is_array( $data ) && ! empty( $data['key'] ) && ! empty( $expiry ) && $this->is_time( $expiry ) ) {
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
    public function encrypt( $secret = '' ) {
        $this->chain_encrypt = $secret;
        return $this;
    }

    /**
     * chain decrypt().
     *
     * @access public
     */
    public function decrypt( $secret = '' ) {
        $this->chain_decrypt = $secret;
        return $this;
    }
}
