<?php

/**
 * Memory-efficient integer storage class.
 * Behaves like a normal array.
 *
 * This one uses: a HEX-STRING
 * and stores: 16 BIT integer.
 *
 *
 * For example: 32768 entries use
 *  - 132056 bytes for HexArray
 *  - but 6817000 bytes for a PHP array()
 *  - which is almost 50 times as much
 *
 *
 */
class HexArray IMPLEMENTS ArrayAccess {

    var $data = "0000";
    const LEN = 4;    // 4 characters = 16 bit

    /**
     *   Initialize data.
     *
     *   @param integer/string   create either a fixed size empty array,
     *                            or use source data (hexstring)
     */
    function __construct($from=NULL) {
        if (is_string($from)) {
            $this->data = $from;
        }
        elseif (is_int($from)) {
            $this->data = str_repeat("0", self::LEN * $from);
        }
    }

    /**
     * Compare array index against data size.
     *
     */
    function offsetExists ( $offset ) {
        return (strlen($this->data) / self::LEN) - 1 >= $offset;
    }

    /**
     * Retrieve value.
     *
     */
    function offsetGet ( $offset ) {
        return hexdec(substr($this->data, $offset * self::LEN, self::LEN));
    }

    /**
     * Update value.
     *
     */
    function offsetSet ( $offset , $value ) {

        $hex = dechex($value);
        if ($fill = self::LEN - strlen($hex)) {
            $hex = str_repeat("0",  $fill) . $hex;
        }

        for ($i=0; $i<self::LEN; $i++) {
            $this->data[$offset * self::LEN + $i] = $hex[$i];
        }
    }

    /**
     * Unsetting not supported.
     *
     */
    function offsetUnset ( $offset ) {
        assert(false);
    }

}


?
