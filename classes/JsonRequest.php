<?php

require_once dirname(__FILE__) . '/AbstractRequest.php';
class JsonRequest extends AbstractRequest {
    /**
     * Any parameters sent with the request.
     *
     * @var array
     */
    public $parameters;
}

?>
