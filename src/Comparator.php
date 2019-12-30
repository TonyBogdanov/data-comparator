<?php

/**
 * Copyright (c) Tony Bogdanov <tonybogdanov@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DataComparator;

use DataComparator\Exceptions\CircularReferenceException;
use DataComparator\Exceptions\ValuesDifferException;

/**
 * Class Comparator
 *
 * @package DataComparator
 */
class Comparator {

    /**
     * @var array
     */
    protected static $ignoredKeys = [];

    /**
     * @param $value
     *
     * @return string
     */
    protected static function formatValue( $value ): string {

        if ( is_object( $value ) ) {

            return '(object) ' . get_class( $value ) . '';

        }

        return '(' . gettype( $value ) . ') ' . json_encode( $value );

    }

    /**
     * @param string $leftType
     * @param string $rightType
     * @param array $path
     */
    protected static function typesDiffer( string $leftType, string $rightType, array $path ) {

        throw new ValuesDifferException( sprintf(

            'Value types differ%1$s, left one is: %2$s, right one is: %3$s.',
            0 === count( $path ) ? '' : sprintf( ' at "%1$s"', implode( '.', $path ) ),
            $leftType,
            $rightType

        ) );

    }

    /**
     * @param array $leftKeys
     * @param array $rightKeys
     * @param array $path
     */
    protected static function keysDiffer( array $leftKeys, array $rightKeys, array $path ) {

        throw new ValuesDifferException( sprintf(

            'Array keys / object properties differ%1$s, left ones are: %2$s, right ones are: %3$s.',
            0 === count( $path ) ? '' : sprintf( ' at "%1$s"', implode( '.', $path ) ),
            static::formatValue( $leftKeys ),
            static::formatValue( $rightKeys )

        ) );

    }

    /**
     * @param $left
     * @param $right
     * @param array $path
     */
    protected static function valuesDiffer( $left, $right, array $path ) {

        throw new ValuesDifferException( sprintf(

            'Values differ%1$s, left one is: %2$s, right one is: %3$s.',
            0 === count( $path ) ? '' : sprintf( ' at "%1$s"', implode( '.', $path ) ),
            static::formatValue( $left ),
            static::formatValue( $right )

        ) );

    }

    /**
     * @param bool $left
     * @param array $referencePath
     * @param array $path
     */
    protected static function objectReferencesSelf( bool $left, array $referencePath, array $path ) {

        throw new CircularReferenceException( sprintf(

            '%1$s object at "%2$s" references itself%3$s, comparing cannot continue to avoid infinite recursion.',
            $left ? 'Left' : 'Right',
            implode( '.', $path ),
            0 === count( $referencePath ) ? ' at the root' : sprintf( ' at "%1$s"', implode( '.', $referencePath ) )

        ) );

    }

    /**
     * @param object $object
     *
     * @return array
     */
    protected static function objectToArray( object $object ): array {

        $array = (array) $object;

        return array_combine( array_map( function ( string $key ): string {

            if ( "\0" === $key[0] ) {

                $key = explode( "\0", $key )[2];

            }

            return $key;

        }, array_keys( $array ) ), array_values( $array ) );

    }

    /**
     * @param string $key
     */
    public static function ignoreKey( string $key ) {

        static::$ignoredKeys[] = $key;

    }

    /**
     * @param string $key
     */
    public static function unignoreKey( string $key ) {

        $index = array_search( $key, static::$ignoredKeys );
        if ( false !== $index ) {

            array_splice( static::$ignoredKeys, $index, 1 );

        }

    }

    /**
     * @param $left
     * @param $right
     * @param array $leftStack
     * @param array $rightStack
     * @param array $path
     */
    public static function compare( $left, $right, array $leftStack = [], array $rightStack = [], array $path = [] ) {

        if ( $left === $right ) {

            return;

        }

        $leftType = is_null( $left ) ? 'NULL' : ( is_object( $left ) ? get_class( $left ) : gettype( $left ) );
        $rightType = is_null( $right ) ? 'NULL' : ( is_object( $right ) ? get_class( $right ) : gettype( $right ) );

        if ( $leftType !== $rightType ) {

            static::typesDiffer( $leftType, $rightType, $path );

        }

        if (

            ( is_scalar( $left ) && is_scalar( $right ) ) ||
            ( is_resource( $left ) && is_resource( $right ) ) ||
            ( $left instanceof \Closure && $right instanceof \Closure )

        ) {

            if ( $left !== $right ) {

                static::valuesDiffer( $left, $right, $path );

            }

        } else if ( is_array( $left ) && is_array( $right ) ) {

            $leftKeys = array_keys( $left );
            $rightKeys = array_keys( $right );

            sort( $leftKeys );
            sort( $rightKeys );

            if ( $leftKeys !== $rightKeys ) {

                static::keysDiffer( $leftKeys, $rightKeys, $path );

            }

            foreach ( $left as $key => $value ) {

                if ( in_array( $key, static::$ignoredKeys ) ) {

                    continue;

                }

                static::compare( $value, $right[ $key ], $leftStack, $rightStack, array_merge( $path, [ $key ] ) );

            }

        } else if ( is_object( $left ) && is_object( $right ) ) {

            $leftHash = spl_object_id( $left );
            $rightHash = spl_object_id( $right );

            if ( array_key_exists( $leftHash, $leftStack ) && array_key_exists( $rightHash, $rightStack ) ) {

                if ( 1 === count( array_slice( $path, count( $leftStack[ $leftHash ] ) ) ) ) {

                    static::objectReferencesSelf( true, $leftStack[ $leftHash ], $path );

                }

                if ( 1 === count( array_slice( $path, count( $rightStack[ $rightHash ] ) ) ) ) {

                    static::objectReferencesSelf( true, $rightStack[ $rightHash ], $path );

                }

                if ( $leftStack[ $leftHash ] === $rightStack[ $rightHash ] ) {

                    return;

                }

            }

            $leftStack[ $leftHash ] = $path;
            $rightStack[ $rightHash ] = $path;

            $left = static::objectToArray( $left );
            $right = static::objectToArray( $right );

            static::compare( $left, $right, $leftStack, $rightStack, $path );

        }

    }

}
