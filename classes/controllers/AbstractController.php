<?php
/**
* @file AbstractController.php
* @brief abstract class for all resources
* @author checkking <checkking@foxmail.com>
* @version 1.0
 */

abstract class AbstractController {
    /**
        * @brief handle GET api request
        *
        * @param $requet, type of AbstractRequest class
        *
        * @return array(code, data)
        * @version 1.0
        * @date 2014-01-09 17:43:02
     */
    abstract public function get($requet);

    /**
        * @brief handle POST api request
        *
        * @param $requet, type of AbstractRequest class
        *
        * @return array(code, data)
        * @version 1.0
     */
    abstract public function post($requet);

    /**
        * @brief handle PUT api request
        *
        * @param $requet, type of AbstractRequest class
        *
        * @return array(code, data)
        * @version 1.0
     */
    abstract public function put($requet);

    /**
        * @brief handle delete request
        *
        * @param $requet, type of AbstractRequest class
        *
        * @return array(code, data)
        * @version 1.0
     */
    abstract public function delete($requet);
}

?>
