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
    private $data_path = '';
    private $db_path = '';
    private $db_index = '';
    private $key_length = 50;
    private $key_expiry = 0;
    private $blob_size = 5000000;
    private $is_blob = false;
    private $is_meta = false;
    private $is_encrypt = false;
    private $is_decrypt = false;
    private $perm_dir = 0755;
    private $perm_file = 0644;
    private $errors = [];

    /**
     * Initialize the class and set its properties.
     */
    public function __construct( $options = [] ) {
        $this->data_path = $this->normalize_path( getcwd().'/' );
        $this->db_path = $this->data_path.'monodb0/';
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
     * options().
     *
     * @access public
     */
    public function options( $options = [] ) {
        $this->is_blob = false;
        $this->is_meta = false;
        $this->is_encrypt = false;
        $this->is_decrypt = false;

        if ( ! empty( $options ) && is_array( $options ) ) {
            if ( ! empty( $options['path'] ) && is_string( $options['path'] ) ) {
                $this->data_path = $this->normalize_path( $options['path'].'/' );
            }

            if ( ! empty( $options['dbname'] ) && is_string( $options['dbname'] ) ) {
                $this->db_path = $this->data_path.'/'.$options['dbname'].'/';
            }

            if ( ! empty( $options['key_length'] ) && $this->is_num( $options['key_length'] ) ) {
                $key_length = (int) $options['key_length'];
                if ( $key_length > 0 ) {
                    $this->key_length = $key_length;
                }
                $this->key_length = (int) $options['key_length'];
            }

            if ( ! empty( $options['blob_size'] ) && $this->is_num( $options['blob_size'] ) ) {
                $blob_size = (int) $options['blob_size'];
                if ( $blob_size > 0 ) {
                    $this->blob_size = $blob_size;
                }
            }

            if ( ! empty( $options['key_expiry'] ) && $this->is_num( $options['key_expiry'] ) ) {
                $key_expiry = (int) $options['key_expiry'];
                if ( $key_expiry > 0 ) {
                    $this->key_expiry = $key_expiry;
                }
            }

            if ( ! empty( $options['perm_dir'] ) && $this->is_num( $options['perm_dir'] ) ) {
                $this->perm_dir = $options['perm_dir'];
            }

            if ( ! empty( $options['perm_file'] ) && $this->is_num( $options['perm_file'] ) ) {
                $this->perm_file = $options['perm_file'];
            }
        }

        $this->db_path = $this->normalize_path( $this->db_path );
        $this->db_index = $this->db_path.'index.php';
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
            if ( (string) $needle === substr( $haystack, -strlen( $needle ) ) ) {
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
        if ( is_file( $file ) && wp_is_writable( $file ) ) {
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
        return preg_match( '@^\d+$@', $num );
    }

    /**
     * is_wildcard().
     *
     * @access private
     */
    private function is_wildcard( $string ) {
        return $this->has_with( $string, [ '*', '?' ] );
    }

    /**
     * is_multi_array().
     *
     * @access private
     */
    private function is_multi_array( $array ) {
        foreach ( $array as $value ) {
            if ( is_array( $value ) ) {
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
        $ok = true;
        try {
            if ( ! is_dir( $this->db_path ) && ! mkdir( $this->db_path, $this->perm_dir, true ) ) {
                $ok = false;
            }
        } catch ( \Exception $e ) {
            $this->catch_error( __METHOD__, $e->getMessage() );
            $ok = false;
        }
        return $ok;
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
            try {
                $encrypted .= chr( ord( $string[ $i ] ) ^ ord( $pad[ $i ] ) );
            } catch ( \Exception $e ) {
                $this->catch_error( __METHOD__, $e->getMessage() );
            }
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
            try {
                $decrypted .= chr( ord( $encrypted[ $i ] ) ^ ord( $pad[ $i ] ) );
            } catch ( \Exception $e ) {
                $this->catch_error( __METHOD__, $e->getMessage() );
            }
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

        return substr( $key, 0, $this->key_length );
    }

    /**
     * key_path().
     *
     * @access private
     */
    private function key_path( $key ) {
        $key = md5( $key );
        $prefix = substr( $key, 0, 2 );
        $path = $this->db_path.$prefix.'/';
        $key = substr( $key, 2 );

        try {
            if ( ! is_dir( $path ) && mkdir( $path, $this->perm_dir, true ) ) {
                touch( $path.'index.php' );
                chmod( $path.'index.php', $this->perm_file );
            }
        } catch ( \Exception $e ) {
            $this->catch_error( __METHOD__, $e->getMessage() );
        }

        return $path.$key.'.php';
    }

    /**
     * match_wildcard(().
     *
     * @access private
     */
    private function match_wildcard( $string, $matches ) {
        foreach ( (array) $matches as $match ) {
            if ( is_string( $match ) && $this->is_wildcard( $match ) ) {
                $wildcard_chars = [ '\*', '\?' ];
                $regexp_chars = [ '.*', '.' ];
                $regex = str_replace( $wildcard_chars, $regexp_chars, preg_quote( $match, '@' ) );

                try {
                    if ( preg_match( '@^'.$regex.'$@is', $string ) ) {
                        return true;
                    }
                } catch ( \Exception $e ) {
                    $this->catch_error( __METHOD__, $e->getMessage() );
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
     * data_read_type().
     *
     * @access private
     */
    private function data_read_type( $data ) {
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
    private function data_serialize( $data, $type ) {
        switch ( $type ) {
            case 'stdClass':
            case 'object':
            case 'resource':
                $data = serialize( $data );
                break;
            case 'binary':
                $data = base64_encode( $data );
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
        $is_blob = $this->is_blob;
        $this->is_blob = false;

        switch ( $type ) {
            case 'stdClass':
            case 'object':
            case 'resource':
                $data = unserialize( $data );
                break;
            case 'binary':
                if ( $is_blob ) {
                    $data = base64_decode( $data );
                }
                break;
        }

        return $data;
    }

    /**
     * data_read_size().
     *
     * @access private
     */
    private function data_read_size( $data ) {
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
     * array_search_r().
     *
     * @access private
     */
    private function array_search_r( $needle, $haystack ) {
        if ( is_array( $haystack ) ) {
            foreach ( $haystack as $key => $value ) {
                $current_key = $key;
                if ( $this->is_wildcard( $needle ) && $this->match_wildcard( $value, $needle ) ) {
                    return $haystack[ $current_key ];
                }
                if ( $needle === $value || ( is_array( $value ) && $this->array_search_r( $needle, $value ) !== false ) ) {
                    return $haystack[ $current_key ];
                }
            }
        }
        return false;
    }

    /**
     * data_store().
     *
     * @access private
     */
    private function data_store( $file, $data ) {
        try {
            if ( file_put_contents( $file, $data, LOCK_EX ) ) {
                chmod( $file, $this->perm_file );
                return true;
            }
        } catch ( \Exception $e ) {
            $this->catch_error( __METHOD__, $e->getMessage() );
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
     * unset_index().
     *
     * @access private
     */
    private function unset_index( $key ) {
        $file = $this->db_index;
        $index = [];
        if ( file_exists( $file ) ) {
            $index = $this->data_read( $file );
            if ( ! empty( $index ) && is_array( $index ) ) {
                if ( ! empty( $index[ $key ] ) ) {
                    unset( $index[ $key ] );
                }

                $code = $this->data_code( $index );
                return $this->data_store( $file, $code );
            }
        }
        return false;
    }

    /**
     * set_index().
     *
     * @access private
     */
    private function set_index( $key, $path, $item ) {
        $file = $this->db_index;
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
        $index[ $key ]['file'] = str_replace( $this->db_path, '', $path );
        $index[ $key ]['size'] = $item['size'];
        $index[ $key ]['type'] = $item['type'];
        $code = $this->data_code( $index );
        return $this->data_store( $file, $code );
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

        //$data = $this->fetch_file( $data, $extra_meta );

        $meta = [
            'key' => $key,
            'timestamp' => gmdate( 'Y-m-d H:i:s' ).' UTC',
            'type' => $this->data_read_type( $data ),
            'size' => $this->data_read_size( $data )
        ];

        if ( 'binary' === $meta['type'] ) {
            $blob_size = (int) $meta['size'];
            if ( $blob_size >= $this->blob_size ) {
                $this->catch_error( __METHOD__, 'Maximum binary size exceeded: '.$blob_size );
                return false;
            }
        }

        if ( ! empty( $expiry ) && $this->is_num( $expiry ) ) {
            $expiry = (int) $expiry;
            if ( $expiry > 0 ) {
                $meta['expiry'] = $expiry;
            }
        } elseif ( ! empty( $this->key_expiry ) ) {
            $meta['expiry'] = (int) $this->key_expiry;
        }

        if ( ! empty( $extra_meta ) && is_array( $extra_meta ) ) {
            $meta = array_merge( $meta, $extra_meta );
        }

        $data = $this->data_serialize( $data, $meta['type'] );
        if ( false !== $this->is_encrypt && is_string( $this->is_encrypt ) ) {
            $data = $this->base64_encrypt( $data, $this->is_encrypt );
            $meta['is_encrypt'] = 1;
        }
        $this->is_encrypt = false;

        $meta['data'] = $data;
        $code = $this->data_code( $meta );

        if ( $this->data_store( $file, $code ) ) {
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

        $is_meta = $this->is_meta;
        $this->is_meta = false;

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
            if ( false !== $this->is_decrypt && is_string( $this->is_decrypt ) ) {
                $data = $this->base64_decrypt( $data, $this->is_decrypt );
                unset( $meta['is_encrypt'] );
            }
            $this->is_decrypt = false;

            $meta['data'] = $this->data_unserialize( $data, $meta['type'] );

            return ( ! $is_meta ? $meta['data'] : $meta );
        }

        return false;
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
     * find().
     *
     * @access public
     */
    public function find( $key, $match ) {
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

            $func_match_wildcard = function( $data, $match ) {
                return ( $this->is_wildcard( $match ) && $this->match_wildcard( $data, $match ) );
            };

            $type = $meta['type'];
            $data = $meta['data'];

            if ( $func_is_invalid( $match, $type ) ) {
                return false;
            }

            if ( $func_is_array( $type ) ) {
                $data = $this->object_to_array( $data );
                $is_multi_array = $this->is_multi_array( $data );
                if ( is_array( $match ) ) {
                    $k = $match[0];
                    $v = $match[1];

                    $data_check = $data;

                    check_arr:
                    foreach ( $data_check as $key => $value ) {
                        if ( is_array( $value ) ) {
                            $data_check = $value;
                            goto check_arr;
                        }
                        if ( $key === $k || $func_match_wildcard( $key, $k ) ) {
                            if ( $value === $v || $func_match_wildcard( $value, $v ) ) {
                                return ( $is_multi_array ? $data_check : $data );
                            }
                        }
                    }
                    return false;
                }

                $data_check = $data;

                check_var:
                foreach ( $data_check as $key => $value ) {
                    if ( is_array( $value ) ) {
                        $data_check = $value;
                        goto check_var;
                    }
                    if ( $value === $match || $func_match_wildcard( $value, $match ) ) {
                        return ( $is_multi_array ? $data_check : $data );
                    }
                }

                return false;
            }

            if ( $match === $data || $func_match_wildcard( $data, $match ) ) {
                return $data;
            }
		}
        return false;
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
        $file = $this->db_index;
        $is_meta = $this->is_meta;
        $this->is_meta = false;

        if ( $this->is_file_readable( $file ) ) {
            $index = $this->data_read( $file );
            if ( ! empty( $index ) && is_array( $index ) ) {
                if ( ! empty( $key ) ) {
                    $rindex = [];
                    foreach ( $index as $k => $v ) {
                        if ( $key === $k || ( $this->is_wildcard( $key ) && $this->match_wildcard( $k, $key ) ) ) {
                            if ( $is_meta ) {
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

                if ( ! $is_meta ) {
                    $index = array_keys( $index );
                }

                return $index;
            }
        }
        return false;
    }

    /**
     * select().
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

    }

    /**
     * incr_by().
     *
     * @access public
     */
    public function incr_by() {

    }

    /**
     * set_meta().
     *
     * @access public
     */
    public function decr_by() {

    }

    /**
     * set_meta().
     *
     * @access public
     */
    public function set_expire( $key, $expiry = 0 ) {

    }

    /**
     * meta().
     *
     * @access public
     */
    public function meta( $enable = null ) {
        $this->is_meta = ( is_bool( $enable ) ? $enable : true );
        return $this;
    }

    /**
     * blob().
     *
     * @access public
     */
    public function blob( $enable = null ) {
        $this->is_blob = ( is_bool( $enable ) ? $enable : true );
        return $this;
    }

    /**
     * encrypt().
     *
     * @access public
     */
    public function encrypt( $secret = '' ) {
        $this->is_encrypt = $secret;
        return $this;
    }

    /**
     * decrypt().
     *
     * @access public
     */
    public function decrypt( $secret = '' ) {
        $this->is_decrypt = $secret;
        return $this;
    }
}
