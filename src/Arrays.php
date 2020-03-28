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

class Arrays {

    public static function keys( array $array, bool $recursive = false ): array {
        if ( $recursive ) {
            if ( preg_match_all( '@(\s*?Array\n*\(\n+)?\s*\[(.*?)\]\s*=\>\s*@m', print_r( $array, 1 ), $mm ) ) {
                $keys = $mm[2];
            }
        } else {
            $keys = array_keys( $array );
        }

        return $keys;
    }

    /**
     * keys_flat().
     */
    public static function keys_flat( $array, $parent_key = '' ) {
        $keys = array_keys( $array );
        foreach ( $array as $parent_key => $i ) {
            if ( \is_array( $i ) ) {
                $nested_keys = self::{__FUNCTION__}( $i, $parent_key );
                foreach ( $nested_keys as $index => $key ) {
                    $nested_keys[ $index ] = $parent_key.'.'.$key;
                }
                $keys = array_merge( $keys, $nested_keys );
            }
        }
        return $keys;
    }

    /**
     * keys_walk().
     */
    public static function keys_walk( array $array, $callback = [] ) {
        $results = [];

        foreach ( $array as $arr_key => $arr_value ) {
            if ( is_callable( $callback ) ) {
                $key = \call_user_func_array( $callback, [ $arr_key, $arr_value ] );
            } else {
                $key = ( $callback[ $arr_key ] ?? $arr_key );
            }

            $results[ $key ] = ( \is_array( $arr_value ) ? self::{__FUNCTION__}( $arr_value, $callback ) : $arr_value );
        }

        return $results;
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
    public static function search( $array_data, $find_value, $find_key = '' ) {
        if ( \is_array( $array_data ) ) {
            foreach ( $array_data as $arr_key => $arr_value ) {
                $current_key = $arr_key;

                if ( ( ! \is_array( $arr_value ) && Func::match_wildcard( $arr_value, $find_value ) )
                    || ( \is_array( $arr_value ) && self::{__FUNCTION__}( $arr_value, $find_value, $find_key ) !== false ) ) {

                    // found value
                    $found = $array_data[ $current_key ];

                    if ( \is_array( $found ) && ! empty( $find_key ) ) {
                        $keys = self::keys( $found, true );
                        if ( ! self::is_empty( $keys ) ) {
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
     * from_object().
     */
    public static function convert_object( $object ) {
        return json_decode( json_encode( $object ), true );
    }

    /**
     * is_empty().
     */
    public static function is_empty( $array, $key = null ): bool {
        if ( ! \is_array( $array ) ) {
            return true;
        }

        return ( null !== $key ? empty( $array[ $key ] ) : empty( $array ) );
    }

    /**
     * is_sequence().
     */
    public static function is_sequential( array $array ): bool {
        return ( self::keys( $array ) === range( 0, \count( $array ) - 1 ) );
    }

    /**
     * is_multi().
     */
    public static function is_multi( array $array ): bool {
        return ! ( \count( $array ) === \count( $array, true ) );
    }

    /**
     * is_numeric().
     */
    public static function is_numeric( array $array ): bool {
        if ( empty( $array ) ) {
            return false;
        }

        foreach ( self::keys( $array ) as &$key ) {
            if ( (int) $key !== $key ) {
                return false;
            }
        }

        return true;
    }

    /**
     * is_assoc().
     */
    public static function is_assoc( array $array, bool $recursive = false ): bool {
        if ( empty( $array ) ) {
            return false;
        }

        foreach ( self::keys( $array, $recursive ) as &$key ) {
            if ( (string) $key === $key ) {
                return true;
            }
        }

        return false;
    }
}
