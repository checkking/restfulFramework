<?php
/**
* @brief json type of response
* @author checkking <checkkiing@foxmail.com>
* @version 1.0
 */
require_once dirname(__FILE__) . '/AbstractResponse.php';
require_once dirname(__FILE__) . '/HttpHeader.php';

class JsonResponse extends AbstractResponse {
    /**
     * Render the response as JSON.
     *
     * @return string
     */
    public function render($code, $response_str) {
        header(HttpHeader::headerCode(200));
        header('Content-Type: application/json');
        echo stripslashes(json_encode(array("code"=>$code,"data"=>$response_str)));
        //echo json_encode($array);
    }
    /**
     * Render the response as JSON with flat.
     *
     * @return string
     */
    public function render_flat($code,$response_str) {
        header(HttpHeader::headerCode(200));
        header('Content-Type: application/json');
        echo $response_str; 
    }

    public function render_view($code, $response_str) {
        header(HttpHeader::headerCode(200));
        header('Content-Type: application/json');
        #echo "{\"code\":$code,\"data\":".$response_str."}"; 
        echo stripslashes(json_encode(array("code"=>$code,"data"=>$response_str)));
        
    }
}

?>
