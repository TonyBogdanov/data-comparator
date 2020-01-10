<?php

/**
 * Copyright (c) Tony Bogdanov <tonybogdanov@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\DataComparator;

use DataComparator\Comparator;
use DataComparator\Exceptions\ValuesDifferException;
use PHPUnit\Framework\TestCase;

/**
 * Class IgnoreKeyTest
 *
 * @package App\Tests\Util\Comparator
 */
class IgnoreKeyTest extends TestCase {

    public function testIgnore() {

        $left = [ 'a' => 'b', 'c' => 'd' ];
        $right = [ 'a' => 'e', 'c' => 'd' ];

        Comparator::ignoreKey( 'a' );
        Comparator::compare( $left, $right );

        $this->assertTrue( true ); // Just assert no exception was thrown.

        Comparator::unignoreKey( 'a' ); // Cleanup.

    }

    public function testUnIgnore() {

        $this->expectException( ValuesDifferException::class );

        $left = [ 'a' => 'b', 'c' => 'd' ];
        $right = [ 'a' => 'e', 'c' => 'd' ];

        Comparator::ignoreKey( 'a' );
        Comparator::unignoreKey( 'a' );
        Comparator::compare( $left, $right );

        $this->assertTrue( true ); // Just assert no exception was thrown.

    }

}
