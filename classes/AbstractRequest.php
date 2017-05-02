<?php
/**
* @brief abstract request class
* @author checkking <checkkiing@foxmail.com>
* @version 1.0
*/

abstract class AbstractRequest {
    /**
     * URL elements.
     *
     * @var array
     */
    public $url_elemenet = array();

    /**
     * The HTTP method used.
     *
     * @var string
     */
    public $method;
}

?>
