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
    private $data_index = '';
    private $key_length = 50;
    private $key_expiry = 0;
    private $blob_size = 5000000;
    private $is_blob = false;
    private $is_meta = false;
    private $perm_dir = 0755;
    private $perm_file = 0644;
    private $is_encrypt = false;
    private $is_decrypt = false;

    public $errors = [];

    /**
     * Initialize the class and set its properties.
     */
    public function __construct( $options = [] ) {
        $this->data_path = getcwd().'/monodb0/';
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
        $this->is_blob = true;
        $this->is_meta = false;
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
                $this->data_path = $options['path'].'/';
            }

            if ( ! empty( $options['dbname'] ) && is_string( $options['dbname'] ) ) {
                $this->data_path = $this->data_path.'/'.$options['dbname'].'/';
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

        $this->data_path = $this->normalize_path( $this->data_path );
        $this->data_index = $this->data_path.'index.php';
        $this->create_data_dir();

        return $this;
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
        return ( is_string( $string ) && is_array( json_decode( $string, true ) ) && ( json_last_error() === JSON_ERROR_NONE ) ? true : false );
    }

    /**
     * is_wildcard().
     *
     * @access private
     */
    private function is_wildcard( $string ) {
        if ( is_string( $string ) && $this->has_with( $string, '*' ) || $this->has_with( $string, '?' ) ) {
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
        if ( ! @is_dir( $this->data_path ) && ! @mkdir( $this->data_path, $this->perm_dir, true ) ) {
            $this->errors[] = 'failed to create '.$this->data_path;
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
            $encrypted .= @chr( ord( $string[ $i ] ) ^ ord( $pad[ $i ] ) );
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
            $decrypted .= @chr( ord( $encrypted[ $i ] ) ^ ord( $pad[ $i ] ) );
        }
        return $decrypted;
    }

    /**
     * format_key().
     *
     * @access private
     */
    private function format_key( $key ) {
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
        $path = $this->data_path.$prefix.'/';
        $key = substr( $key, 2 );
        if ( ! @is_dir( $path ) ) {
            if ( @mkdir( $path, $this->perm_dir, true ) ) {
                @touch( $path.'index.php' );
                @chmod( $path.'index.php', $this->perm_file );
            }
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
            if ( ! $this->is_wildcard( $match ) ) {
                if ( $this->has_with( $string, $match ) ) {
                    return true;
                }
                continue;
            }

            $wildcard_chars = [ '\*', '\?' ];
            $regexp_chars = [ '.*', '.' ];
            $regex = str_replace( $wildcard_chars, $regexp_chars, preg_quote( $match, '@' ) );
            if ( @preg_match( '@^'.$regex.'$@is', $string ) ) {
                return true;
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
        return preg_replace( '@[/]+@', '/', $path );
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
     * data_type().
     *
     * @access private
     */
    private function data_type( $data ) {
        $type = gettype( $data );
        if ( 'string' === $type && $this->is_binary( $data ) ) {
            $type = 'binary';

        } elseif ( 'string' === $type && $this->is_json( $data ) ) {
            $type = 'json';

        } elseif ( 'object' === $type && ( $data instanceof stdClass ) ) {
            $type = 'stdClass';
        }
        return $type;
    }

    /**
     * wrap_data().
     *
     * @access private
     */
    private function wrap_data( $data, $type ) {
        if ( 'stdClass' === $type ) {
            $data = (array) $data;

        } elseif ( 'object' === $type || 'resource' === $type ) {
            $data = serialize( $data );

        } elseif ( 'json' === $type ) {
            $data = json_decode( $data, true );

        } elseif ( 'binary' === $type ) {
            $data = base64_encode( $data );
        }

        return $data;
    }

    /**
     * unwrap_data().
     *
     * @access private
     */
    private function unwrap_data( $data, $type ) {
        $is_blob = $this->is_blob;
        $this->is_blob = false;

        if ( 'stdClass' === $type ) {
            $data = (object) $data;

        } elseif ( 'object' === $type || 'resource' === $type ) {
            $data = unserialize( $data );

        } elseif ( 'json' === $type ) {
            $data = json_encode( $data );

        } elseif ( 'binary' === $type && $is_blob ) {
            $data = base64_decode( $data );
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

        $data_e = var_export( $data, true );
        $data_e = str_replace( 'Requests_Utility_CaseInsensitiveDictionary::__set_state(', '', $data_e );

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
     * store().
     *
     * @access private
     */
    private function store( $file, $data ) {
        if ( @file_put_contents( $file, $data, LOCK_EX ) ) {
            @chmod( $file, $this->perm_file );
            return true;
        }

        return false;
    }

    /**
     * unset_index().
     *
     * @access private
     */
    private function unset_index( $key ) {
        $file = $this->data_index;
        $index = [];
        if ( file_exists( $file ) ) {
            $index = @include( $file );
            if ( ! empty( $index ) && is_array( $index ) ) {
                if ( ! empty( $index[ $key ] ) ) {
                    unset( $index[ $key ] );
                }

                $code = $this->data_code( $index );
                return $this->store( $file, $code );
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
        $file = $this->data_index;
        $index = [];
        if ( file_exists( $file ) ) {
            $index = @include( $file );
            if ( empty( $index ) || ! is_array( $index ) ) {
                $index = [];
            }
        }

        $index[ $key ]['timestamp'] = $item['timestamp'];
        $index[ $key ]['file'] = str_replace( $this->data_path, '', $path );
        $index[ $key ]['size'] = $item['size'];
        $index[ $key ]['type'] = $item['type'];
        $code = $this->data_code( $index );
        return $this->store( $file, $code );
    }

    /**
     * set().
     *
     * @access public
     */
    public function set( $key, $data, $expiry = 0, $extra_meta = [] ) {
        $key = $this->format_key( $key );
        $file = $this->key_path( $key );

        if ( $this->start_with( $data, 'file://' ) ) {
            $fi = $this->strip_scheme( $data );
            if ( ! $this->start_with( $fi, '.' ) ) {
                $fi = '/'.$fi;
            }
            if ( $this->is_file_readable( $fi ) ) {
                $data = file_get_contents( $fi );
            }
        }

        $meta = [
            'key' => $key,
            'timestamp' => gmdate( 'Y-m-d H:i:s' ).' UTC',
            'type' => $this->data_type( $data ),
            'size' => $this->data_size( $data )
        ];

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

        if ( 'binary' === $meta['type'] ) {
            $blob_size = (int) $meta['size'];
            if ( $blob_size >= $this->blob_size ) {
                $this->errors[] = 'Maximum binary size exceeded';
                return false;
            }
        }

        $data = $this->wrap_data( $data, $meta['type'] );
        if ( false !== $this->is_encrypt && is_string( $this->is_encrypt ) ) {
            $data = $this->base64_encrypt( $data, $this->is_encrypt );
            $meta['is_encrypt'] = 1;
        }
        $this->is_encrypt = false;

        $meta['data'] = $data;
        $code = $this->data_code( $meta );

        if ( $this->store( $file, $code ) ) {
            $this->set_index( $key, $file, $meta );
            return $key;
        }

        $this->errors[] = 'Failed to set '.$key;
        return false;
    }

    /**
     * get().
     *
     * @access public
     */
    public function get( $key ) {
        $key = $this->format_key( $key );
        $file = $this->key_path( $key );

        $is_meta = $this->is_meta;
        $this->is_meta = false;

        if ( $this->is_file_readable( $file ) ) {
            $meta = @include( $file );
            if ( ! is_array( $meta ) || empty( $meta ) || empty( $meta['data'] ) ) {
                $this->delete( $key );
                return false;
            }

            if ( ! empty( $meta['expiry'] ) && $this->is_num( $meta['expiry'] ) ) {
                if ( time() >= (int) $meta['expiry'] ) {
                    $this->delete( $key );
                    return false;
                }
            }

            $data = $meta['data'];
            if ( false !== $this->is_decrypt && is_string( $this->is_decrypt ) ) {
                $data = $this->base64_decrypt( $data, $this->is_decrypt );
                unset( $meta['is_encrypt'] );
            }
            $this->is_decrypt = false;

            $meta['data'] = $this->unwrap_data( $data, $meta['type'], $is_meta );

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
        $key = $this->format_key( $key );
        $file = $this->key_path( $key );
        if ( @unlink( $file ) ) {
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
    public function find( $key, $value ) {
        $data = $this->get( $key );
        if ( ! empty( $data ) && is_array( $data ) ) {
            return $this->array_search_r( $value, $data );
        }
        return false;
    }

    /**
     * exists().
     *
     * @access public
     */
    public function exists( $key ) {
        $key = $this->format_key( $key );
        $file = $this->key_path( $key );
        return $this->is_file_readable( $file );
    }

    /**
     * keys().
     *
     * @access public
     */
    public function keys( $key = '' ) {
        $file = $this->data_path.'index.php';
        $is_meta = $this->is_meta;
        $this->is_meta = false;

        if ( $this->is_file_readable( $file ) ) {
            $index = @include( $file );
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
