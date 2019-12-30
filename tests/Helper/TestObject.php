<?php

/**
 * Copyright (c) Tony Bogdanov <tonybogdanov@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\DataComparator\Helper;

/**
 * Class TestObject
 *
 * @package Tests\DataComparator\Helper
 */
class TestObject {

    /**
     * @var mixed
     */
    public $byValue = 'NOT_SET';

    /**
     * @var mixed
     */
    public $byReference = 'NOT_SET';

    /**
     * @var mixed
     */
    private $private;

    public static function staticCallback() {}

    public function __clone() {

        if ( is_object( $this->byReference ) ) {

            $this->byReference = clone $this->byReference;

        }

    }

    public function callback() {}

    /**
     * @return mixed
     */
    public function getPrivate() {

        return $this->private;

    }

    /**
     * @param mixed $private
     *
     * @return TestObject
     */
    public function setPrivate( $private ) {

        $this->private = $private;
        return $this;

    }

}
