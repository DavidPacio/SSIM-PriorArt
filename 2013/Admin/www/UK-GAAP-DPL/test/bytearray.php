<?php

/**
 * Memory-efficient integer storage class.
 * Behaves like a normal array.
 *
 * This one uses: BINARY STRING
 * and stores: 8 BIT integer.
 *
 *
 * For example: 32768 entries use just their 32768 bytes
 * (plus 1240 byte for the object instance).
 *
 *
 */
class ByteArray IMPLEMENTS ArrayAccess {

    var $data = "\0";
    var $len = 1;    // 1 byte = 8 bit
    var $pack = "C";

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
            $this->data = str_repeat("\0", $this->len * $from);
        }
    }

    /**
     * Compare array index against data size.
     *
     */
    function offsetExists ( $offset ) {
        return strlen($this->data) - 1 >= $offset;
    }

    /**
     * Retrieve value.
     *
     */
    function offsetGet ( $offset ) {
        return ord($this->data[$offset]);
    }

    /**
     * Update value.
     *
     */
    function offsetSet ( $offset , $value ) {
#        assert($value < 0x100);
        $this->data[$offset] = chr($value);
    }

    /**
     * Unsetting not supported.
     *
     */
    function offsetUnset ( $offset ) {
        assert(false);
    }

}


?>