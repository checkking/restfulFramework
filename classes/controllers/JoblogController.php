<?php
require_once dirname(__FILE__) . '/AbstractController.php';
require_once dirname(__FILE__) . '/../../config/PathConfig.php';
include_once ("BmlApiErrcode.php");
include_once ("db.inc");
include_once ("myPDO.inc");

class JoblogController extends AbstractController
{

    /**
     * GET method.
     *
     * @param  Request $request
     * @return string
     */
    public function get($request)
    {
        return self::post($request);
    }
    /**
     * POST action.
     * @param  $request
     * @return null
     */
    public function post($request)
    {
        $ret_code = array(
            "code" => BmlApiErrcode::$unknow_err,
            "data" => null,
        );
        
        if(count($request->url_elements) < 2){
            $ret_code["code"] = BmlApiErrcode::$log_jobid_lost;
            $ret_code["data"] = "please give job_id to showlog";
            return $ret_code;
        }

        $log_type = "";
        $job_id = "";
        if (count($request->url_elements) == 2) {
            $log_type = "all";
            $job_id = $request->url_elements[1];
        } else if (count($request->url_elements) == 3) {
            $log_type = $request->url_elements[1];
            $job_id = $request->url_elements[2];
        }
        $log_dir = PathConfig::$BML_DIR."/log/".$job_id."/";
        $log_file = $log_dir.$log_type."log";
        
        if (!file_exists($log_dir)) {
            $ret_code["code"] = BmlApiErrcode::$log_jobid_wrong;
            $ret_code["data"] = "Job id ".$job_id." not Exists!";
            return $ret_code;
        }
        $log_data = "";
        if (!file_exists($log_file)){
            $log_data = "Empty Log";
        } else {
            $log_data = file_get_contents($log_file);
        }
        $ret_code["code"] = BmlApiErrcode::$log_success;
        $ret_code["data"] = $log_data;
        return $ret_code;
    }
    /**
     * PUT action.
     *
     * @param  $request
     * @return null
     */
    public function put($request){}

    /**
     * delete action.
     *
     * @param  $request
     * @return null
     */
    public function delete($request){}
}
