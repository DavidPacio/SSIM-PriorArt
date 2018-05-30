<?php

/**
 * Memory-efficient integer storage class.
 * Behaves like a normal array.
 *
 * This one uses: BINARY DATA
 * and stores: 16 BIT integer.
 *
 *
 * For example: 32768 entries use
 *  - 66520 bytes for HexArray
 *  - but 6817000 bytes for a PHP array()
 * So it takes less than 1% of the memory size of a normal array.
 *
 *
 */
class WordArray IMPLEMENTS ArrayAccess {

    var $data = "\0\0";
    var $len = 2;    // 2 bytes = 16 bit
    var $pack = "S";

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
        return (strlen($this->data) / $this->len) - 1 >= $offset;
    }

    /**
     * Retrieve value.
     *
     */
    function offsetGet ( $offset ) {
        return current(unpack($this->pack, substr($this->data, $offset * $this->len, $this->len)));
    }

    /**
     * Update value.
     *
     */
    function offsetSet ( $offset , $value ) {

        $bin = pack($this->pack, $value);# . "\0\0\0\0\0\0\0";

        for ($i=0; $i<$this->len; $i++) {
            $this->data[$offset * $this->len + $i] = $bin[$i];
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



/**
 * stores: 32 BIT integer
 *
 *
 */
class DWordArray extends WordArray {
    var $len = 4;    // 4 bytes = 32 bit
    var $pack = "L";
}


/**
 * stores: 64 BIT integer
 *
 *
 */
class QWordArray extends WordArray {
    var $len = 8;    // 8 bytes = 64 bit
    var $pack = "L";   // Q not supported, workaround:
    function offsetGet ( $offset ) {
        return parent::offsetGet( $offset ) + parent::offsetGet( $offset + 0.5 ) << 32;
    }
    function offsetSet ( $offset, $value ) {
        parent::offsetSet( $offset, $value & 0xFFFFFFFF );
        parent::offsetSet( $offset + 0.5, $value >> 32 );
    }

}


?>

