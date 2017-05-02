<?php

/**
* @brief abstract response class
* @author checkking <checkkiing@foxmail.com>
* @version 1.0
 */

/**
 * Render the response
 *
 * @return string
 */
abstract class AbstractResponse {
    abstract public function render($code, $response_str);
}

?>
