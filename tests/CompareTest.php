<?php

/**
 * Copyright (c) Tony Bogdanov <tonybogdanov@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\DataComparator;

use DataComparator\Comparator;
use DataComparator\Exceptions\CircularReferenceException;
use DataComparator\Exceptions\ValuesDifferException;
use PHPUnit\Framework\TestCase;
use Tests\DataComparator\Helper\InstanceVariation;
use Tests\DataComparator\Helper\TestObject;

/**
 * Class CompareTest
 *
 * @package App\Tests\Util\Comparator
 */
class CompareTest extends TestCase {

    /**
     * @var array
     */
    protected $resources = [];

    /**
     * @return array
     */
    protected function _null(): array {

        return [ null ];

    }

    /**
     * @return array
     */
    protected function _boolean(): array {

        return [ true, false ];

    }

    /**
     * @return array
     */
    protected function _integer(): array {

        return [ -123, 0, 123 ];

    }

    /**
     * @return array
     */
    protected function _float(): array {

        return [ -123.45, 0.0, 123.45 ];

    }

    /**
     * @return array
     */
    protected function _string(): array {

        return [ '', 'hello', 'world' ];

    }

    /**
     * @return array
     */
    protected function _resource(): array {

        $one = tmpfile();
        $this->resources[] = $one;

        $two = tmpfile();
        $this->resources[] = $two;

        return [ $one, $two ];

    }

    /**
     * @return array
     */
    protected function _simpleObject(): array {

        return [ new InstanceVariation( [ new \stdClass(), new \stdClass() ] ) ];

    }

    /**
     * @param int $depth
     *
     * @return array
     */
    protected function _complexObject( int $depth = 0 ): array {

        if ( 2 < $depth ) {

            return [];

        }

        $values = array_map( [ $this, 'flatten' ], $this->values( $this->types(), $depth + 1 ) );
        $values = 0 < count( $values ) ? array_merge( ...$values ) : [];

        $objects = [

            new InstanceVariation( [ new TestObject(), new TestObject() ] ),
            ( new TestObject() )->setPrivate( 'private' ),
            ( new TestObject() )->setPrivate( 'not_private' ),

        ];

        foreach ( $values as $value ) {

            $object = new TestObject();

            $object->byValue = is_object( $value ) ? clone $value : null;
            $object->byReference = $value;

            $objects[] = $object;

        }

        return $objects;

    }

    /**
     * @param int $depth
     *
     * @return array
     */
    protected function _array( int $depth = 0 ): array {

        if ( 1 < $depth ) {

            return [];

        }

        $values = array_map( [ $this, 'flatten' ], $this->values( $this->types(), $depth + 1 ) );
        $values = 0 < count( $values ) ? array_merge( ...$values ) : [];

        $arrays = [ [] ];

        foreach ( $values as $value ) {

            $arrays[] = [ is_object( $value ) ? clone $value : null, 'byReference' => $value ];

        }

        return $arrays;

    }

    /**
     * @return array
     */
    protected function _callable(): array {

        $object = new TestObject();

        return [

            'substr',
            'strlen',
            TestObject::class . '::staticCallback',
            new InstanceVariation( [ [ $object, '_null' ], [ new TestObject(), '_null' ] ] ),
            [ $object, '_boolean' ],
            function () {},
            function () {},

        ];

    }

    /**
     * @return array
     */
    protected function scalarTypes(): array {

        return [

            '_null',
            '_boolean',
            '_integer',
            '_float',
            '_string',

        ];

    }

    /**
     * @return string[]
     */
    protected function types(): array {

        return array_merge(

            $this->scalarTypes(),

            [

                '_resource',
                '_callable',
                '_simpleObject',
                '_complexObject',
                '_array',

            ]

        );

    }

    /**
     * @param array $types
     * @param int $depth
     *
     * @return array
     */
    protected function values( array $types, int $depth = 0 ): array {

        $result = [];

        foreach ( $types as $type ) {

            $result[] = call_user_func( [ $this, $type ], $depth );

        }

        return $result;

    }

    /**
     * @param array $values
     *
     * @return array
     */
    protected function flatten( array $values ): array {

        $result = [];

        foreach ( $values as $value ) {

            if ( $value instanceof InstanceVariation ) {

                $result[] = $value[0];
                continue;

            }

            $result[] = $value;

        }

        return $result;

    }

    /**
     * @param array $values
     *
     * @return array
     */
    protected function vary( array $values ): array {

        $value = array_shift( $values );
        $result = [];

        foreach ( $values as $next ) {

            $result[] = [ $value, $next ];

        }

        if ( 1 < count( $values ) ) {

            $result = array_merge( $result, $this->vary( $values ) );

        }

        return $result;

    }

    /**
     * @return array
     */
    public function validProvider(): array {

        return array_merge( ...array_map( function ( array $values ): array {

            $result = [];

            foreach ( $values as $value ) {

                if ( ! ( $value instanceof InstanceVariation ) ) {

                    $result[] = [ $value, $value ];
                    continue;

                }

                // Different instances, but same value.
                foreach ( $value as $instance ) {

                    $result[] = [ $instance, $instance ];

                }

                $result = array_merge( $result, $this->vary( (array) $value ) );

            }

            return $result;

        }, $this->values( $this->types() ) ) );

    }

    /**
     * @return array
     */
    public function invalidProvider(): array {

        return $this->vary( array_merge(

            ...array_map( [ $this, 'flatten' ], array_map( function ( string $type ): array {

                return call_user_func( [ $this, $type ] );

            }, $this->types() ) )

        ) );

    }

    protected function tearDown(): void {

        foreach ( $this->resources as $resource ) {

            fclose( $resource );

        }

        parent::tearDown();

    }

    /**
     * @dataProvider validProvider
     *
     * @param $left
     * @param $right
     */
    public function testValid( $left, $right ) {

        Comparator::compare( $left, $right );
        $this->assertTrue( true ); // Just assert no exception was thrown.

    }

    /**
     * @dataProvider invalidProvider
     *
     * @param $left
     * @param $right
     */
    public function testInvalid( $left, $right ) {

        $this->expectException( ValuesDifferException::class );
        Comparator::compare( $left, $right );

    }

    public function testObjectDirectSelfReference() {

        $this->expectException( CircularReferenceException::class );

        $left = new TestObject();
        $left->byReference = $left;

        $right = new TestObject();
        $right->byReference = $right;

        Comparator::compare( $left, $right );

    }

    public function testObjectDeepSelfReference() {

        $left = new TestObject();
        $left->byReference = [ 'deep' => [ 'deeper' => $left ] ];

        $right = new TestObject();
        $right->byReference = [ 'deep' => [ 'deeper' => $right ] ];

        Comparator::compare( $left, $right );
        $this->assertTrue( true ); // Just assert no exception was thrown.

    }

    public function testObjectDifferentDepthSelfReference() {

        $this->expectException( CircularReferenceException::class );

        $leftDeep = new TestObject();

        $left = new TestObject();
        $left->byReference = $leftDeep;
        $leftDeep->byReference = $left;

        $right = new TestObject();
        $right->byReference = $right;

        Comparator::compare( $left, $right );

    }

    public function testObjectCrossReference() {

        $left = new TestObject();
        $right = new TestObject();

        $left->byReference = $right;
        $right->byReference = $left;

        Comparator::compare( $left, $right );
        $this->assertTrue( true ); // Just assert no exception was thrown.

    }

    public function testObjectDeepCrossReference() {

        $left = new TestObject();
        $right = new TestObject();

        $left->byReference = [ 'deep' => [ 'deeper' => $right ] ];
        $right->byReference = [ 'deep' => [ 'deeper' => $left ] ];

        Comparator::compare( $left, $right );
        $this->assertTrue( true ); // Just assert no exception was thrown.

    }

}
