<?php
require_once dirname(__FILE__) . '/AbstractController.php';
require_once dirname(__FILE__) . '/../../config/PathConfig.php';
include_once ("BmlApiErrcode.php");
include_once ("db.inc");
include_once ("myPDO.inc");
/**
 * @view infomation api for BML
 * @version 1.0
 */
class ViewController extends AbstractController
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
     *         $request->url_elements[1] = $view_option
     *         $request->post_data = $view_algo_parameters
     * @return string
     */
    public function post($request)
    {
        $ret_code = array(
                "code" => BmlApiErrcode::$unknow_err,
                "data" => null,
                );

        if (count($request->url_elements) < 2) {
            $ret_code["code"] = BmlApiErrcode::$view_para_lost;
            $ret_code["data"] = "please give parameter to view";
            return $ret_code;
        }

        if(!is_object(DB::bmlDB())){
            $ret_code["code"] = BmlApiErrcode::$DB_err; 
            $ret_code["data"] = "bml database err";
            return $ret_code;
        } 

        $view_object = $request->url_elements[1];

        switch ($view_object) {
            case "dataset_info":
                $ret_code = self::view_dataset_info($request->post_data);
                break;
            case "model_info":
                $ret_code = self::view_model_info($request->post_data);
                break;
            case "model_evaluate_result":
                $ret_code = self::view_model_evaluate_result($request->post_data);
                break;
            case "job_config":
                $ret_code = self::view_job_config($request->url_elements);
                break;
            case "job_status":
                $ret_code = self::view_job_status($request->url_elements);
                break;
            case "dataset_list":
                $ret_code = self::view_dataset_list($request->post_data);
                break;
            case "model_list":
                $ret_code = self::view_model_list($request->post_data);
                break;
            case "job_list":
                $ret_code = self::view_job_list($request->post_data);
                break;
            default:
                $ret_code["code"] = BmlApiErrcode::$view_para_wrong;
                $ret_code["data"] = "parameter to view is wrong";
        }

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

    private function view_dataset_info($post_data) {
        $ret_code = array(
                "code" => BmlApiErrcode::$unknow_err,
                "data" => null,
                );

        $para_key = array("sys_user_name", "dataset_id");
        foreach ($para_key as $each_key) {
            if (!array_key_exists($each_key, $post_data)){
                $ret_code["code"] = BmlApiErrcode::$view_para_key_lost;
                $ret_code["data"] = "parameter '".$each_key."' must be configured";
                return $ret_code; 
            }

            if (trim($post_data[$each_key]) == "") {
                $ret_code["code"] = BmlApiErrcode::$view_para_key_null;
                $ret_code["data"] = "parameter '".$each_key."' can't be null";
                return $ret_code; 
            }
        }

        $sql = "select dataset_name, user_email, dataset_input_type, feature_num, ";
        $sql = $sql." sample_num, label_num, dataset_path, dataset_source_path, ";
        $sql = $sql." job_id, cdate from bml_dataset where user_email='".$post_data["sys_user_name"]."' and ";
        $sql = $sql." dataset_name='".$post_data["dataset_id"]."' and dataset_status=0;";
        $ret = DB::bmlDB()->allPrepare($sql, $mode=PDO::FETCH_ASSOC);
        if(count($ret->data) == 0){
            $ret_code["code"] = BmlApiErrcode::$view_no_dataset;
            $ret_code["data"] = "No exist dataset '".$post_data["dataset_id"]."' for user '".$post_data["sys_user_name"]."'";
            return $ret_code;
        }

        $ret_code["code"] = BmlApiErrcode::$view_success;
        $ret_code["data"] = $ret->data[0];
        return $ret_code;

    }
    
    private function view_model_info($post_data) {
        $ret_code = array(
                "code" => BmlApiErrcode::$unknow_err,
                "data" => null,
                );

        $para_key = array("sys_user_name", "model_id");
        foreach ($para_key as $each_key) {
            if (!array_key_exists($each_key, $post_data)){
                $ret_code["code"] = BmlApiErrcode::$view_para_key_lost;
                $ret_code["data"] = "parameter '".$each_key."' must be configured";
                return $ret_code; 
            }

            if (trim($post_data[$each_key]) == "") {
                $ret_code["code"] = BmlApiErrcode::$view_para_key_null;
                $ret_code["data"] = "parameter '".$each_key."' can't be null";
                return $ret_code; 
            }
        }

        $sql = "select model_name, user_email, train_dataset, model_path, model_algo_name, job_id, cdate from bml_model ";
        $sql = $sql."where user_email='".$post_data["sys_user_name"]."' and ";
        $sql = $sql." model_name='".$post_data["model_id"]."' and model_status=0;";
        $ret = DB::bmlDB()->allPrepare($sql, $mode=PDO::FETCH_ASSOC);
        if(count($ret->data) == 0){
            $ret_code["code"] = BmlApiErrcode::$view_no_model;
            $ret_code["data"] = "No exist model '".$post_data["model_id"]."' for user '".$post_data["sys_user_name"]."'";
            return $ret_code;
        }
        $ret_code["code"] = BmlApiErrcode::$view_success;
        $ret_code["data"] = $ret->data[0];
        return $ret_code;
    }

    private function view_model_evaluate_result($post_data) {
        $ret_code = array(
                "code" => BmlApiErrcode::$unknow_err,
                "data" => null,
                );

        $para_key = array("sys_user_name", "model_id");
        foreach ($para_key as $each_key) {
            if (!array_key_exists($each_key, $post_data)){
                $ret_code["code"] = BmlApiErrcode::$view_para_key_lost;
                $ret_code["data"] = "parameter '".$each_key."' must be configured";
                return $ret_code; 
            }

            if (trim($post_data[$each_key]) == "") {
                $ret_code["code"] = BmlApiErrcode::$view_para_key_null;
                $ret_code["data"] = "parameter '".$each_key."' can't be null";
                return $ret_code; 
            }
        }

        //$sql = "select model_evaluate from bml_model ";
        //$sql = $sql."where user_email='".$post_data["sys_user_name"]."' and ";
        //$sql = $sql." model_name='".$post_data["model_id"]."' and model_status=0;";
        //$ret = DB::bmlDB()->allPrepare($sql, $mode=PDO::FETCH_ASSOC);
        //if(count($ret->data) == 0){
        //    $ret_code["code"] = BmlApiErrcode::$view_no_model;
        //    $ret_code["data"] = "No exist model '".$post_data["model_id"]."' for user '".$post_data["sys_user_name"]."'";
        //    return $ret_code;
        //}

        $sql = "select dataset_name, job_id, evaluate_result, udate from bml_model_evaluate ";
        $sql = $sql."where user_email='".$post_data["sys_user_name"]."' and ";
        $sql = $sql." model_name='".$post_data["model_id"]."';";
        $ret = DB::bmlDB()->allPrepare($sql, $mode=PDO::FETCH_ASSOC);

        $ret_code["code"] = BmlApiErrcode::$view_success;
        $ret_code["data"] = $ret->data;
 
        return $ret_code;

    }
    
    private function view_job_config($url_elements) {
        $ret_code = array(
                "code" => BmlApiErrcode::$unknow_err,
                "data" => null,
                );

        if (count($url_elements) <3 ){
            $ret_code["code"] = BmlApiErrcode::$view_job_conf_no_jobid;
            $ret_code["data"] = "please give job id to get config";
        }
        $job_id = $url_elements[2];
        $sql = "select job_conf from bml_job where job_id='".$job_id."';";
        $ret = DB::bmlDB()->allPrepare($sql, $mode=PDO::FETCH_ASSOC);
        if(count($ret->data) == 0){
            $ret_code["code"] = BmlApiErrcode::$view_job_conf_jobid_wrong;
            $ret_code["data"] = "BML job id '".$job_id."' not exists!";
            return $ret_code;
        }

        $confstr = str_replace("\\\"", "", $ret->data[0]["job_conf"]);
        $ret_code["code"] = BmlApiErrcode::$view_success;
        $ret_code["data"] = json_decode($confstr);
        return $ret_code;

    }

    private function view_job_status($url_elements) {
        $ret_code = array(
                "code" => BmlApiErrcode::$unknow_err,
                "data" => null,
                );

        if (count($url_elements) <3 ){
            $ret_code["code"] = BmlApiErrcode::$view_job_status_no_jobid;
            $ret_code["data"] = "please give job id to get status";
            return $ret_code;
        }
        $job_id = $url_elements[2];
        $sql = "select job_status, job_status_log from bml_job where job_id='".$job_id."';";
        $ret = DB::bmlDB()->allPrepare($sql, $mode=PDO::FETCH_ASSOC);
        if(count($ret->data) == 0){
            $ret_code["code"] = BmlApiErrcode::$view_job_status_jobid_wrong;
            $ret_code["data"] = "BML job id '".$job_id."' not exists!";
            return $ret_code;
        }

        $ret_code["code"] = $ret->data[0]["job_status"];
        $ret_code["data"] = $ret->data[0]["job_status_log"];
        return $ret_code;

    }

    private function view_dataset_list($post_data) {
        $ret_code = array(
                "code" => BmlApiErrcode::$unknow_err,
                "data" => null,
                );

        $para_key = array("sys_user_name");
        $option_key = array("view_page", "page_num");
        $option_value = array("view_page" => -1, 
                             "page_num" => 20,
                             );
        foreach ($para_key as $each_key) {
            if (!array_key_exists($each_key, $post_data)){
                $ret_code["code"] = BmlApiErrcode::$view_para_key_lost;
                $ret_code["data"] = "parameter '".$each_key."' must be configured";
                return $ret_code; 
            }

            if (trim($post_data[$each_key]) == "") {
                $ret_code["code"] = BmlApiErrcode::$view_para_key_null;
                $ret_code["data"] = "parameter '".$each_key."' can't be null";
                return $ret_code; 
            }
        }

        foreach ($option_key as $each_key) {
            if (array_key_exists($each_key, $post_data)){
                if (trim($post_data[$each_key]) != "") {
                    $option_value[$each_key] = intval(trim($post_data[$each_key]));     
                }
            }
        }


        $sql = "select dataset_id,dataset_name, dataset_status ,dataset_input_type, dataset_path,sample_num, feature_num, label_num, cdate from bml_dataset where user_email='".$post_data["sys_user_name"]."'";
        $sql = $sql." and (dataset_status=0 or dataset_status=1) order by cdate desc ";
        if($option_value["view_page"] > 0) {
            $start_num = ($option_value["view_page"] - 1) * $option_value["page_num"];
            $sql = $sql." limit ".strval($start_num).",".strval($option_value["page_num"])." ";
        }
        $sql = $sql.";";
        $ret = DB::bmlDB()->allPrepare($sql, $mode=PDO::FETCH_ASSOC);

        $ret_code["code"] = BmlApiErrcode::$view_success;
        $ret_code["data"] = $ret->data;
        return $ret_code;

    }
    
    private function view_model_list($post_data) {
        $ret_code = array(
                "code" => BmlApiErrcode::$unknow_err,
                "data" => null,
                );

        $para_key = array("sys_user_name");
        $para_key = array("sys_user_name");
        $option_key = array("view_page", "page_num");
        $option_value = array("view_page" => -1, 
                             "page_num" => 20,
                             );
 
        foreach ($para_key as $each_key) {
            if (!array_key_exists($each_key, $post_data)){
                $ret_code["code"] = BmlApiErrcode::$view_para_key_lost;
                $ret_code["data"] = "parameter '".$each_key."' must be configured";
                return $ret_code; 
            }

            if (trim($post_data[$each_key]) == "") {
                $ret_code["code"] = BmlApiErrcode::$view_para_key_null;
                $ret_code["data"] = "parameter '".$each_key."' can't be null";
                return $ret_code; 
            }
        }
        foreach ($option_key as $each_key) {
            if (array_key_exists($each_key, $post_data)){
                if (trim($post_data[$each_key]) != "") {
                    $option_value[$each_key] = intval(trim($post_data[$each_key]));     
                }
            }
        }

        $sql = "select model_id,model_name, model_status , model_path, model_algo_name , cdate from bml_model where user_email='".$post_data["sys_user_name"]."'";
        $sql = $sql." and (model_status=0 or model_status=1) order by cdate desc ";
        if($option_value["view_page"] > 0) {
            $start_num = ($option_value["view_page"] - 1) * $option_value["page_num"];
            $sql = $sql." limit ".strval($start_num).",".strval($option_value["page_num"])." ";
        }
        $sql = $sql.";";
        $ret = DB::bmlDB()->allPrepare($sql, $mode=PDO::FETCH_ASSOC);
        $ret_code["code"] = BmlApiErrcode::$view_success;
        $ret_code["data"] = $ret->data;
        return $ret_code;

    }

    private function view_job_list($post_data) {
        $ret_code = array(
                "code" => BmlApiErrcode::$unknow_err,
                "data" => null,
                );

        $para_key = array("sys_user_name");
        $option_key = array("view_page", "page_num");
        $option_value = array("view_page" => -1, 
                             "page_num" => 20,
                             );
        foreach ($para_key as $each_key) {
            if (!array_key_exists($each_key, $post_data)){
                $ret_code["code"] = BmlApiErrcode::$view_para_key_lost;
                $ret_code["data"] = "parameter '".$each_key."' must be configured";
                return $ret_code; 
            }

            if (trim($post_data[$each_key]) == "") {
                $ret_code["code"] = BmlApiErrcode::$view_para_key_null;
                $ret_code["data"] = "parameter '".$each_key."' can't be null";
                return $ret_code; 
            }
        }

        foreach ($option_key as $each_key) {
            if (array_key_exists($each_key, $post_data)){
                if (trim($post_data[$each_key]) != "") {
                    $option_value[$each_key] = intval(trim($post_data[$each_key]));     
                }
            }
        }


        $sql = "select job_id, job_type, job_status, job_status_log, cdate from bml_job where user_email='".$post_data["sys_user_name"]."'";
        $sql = $sql." order by cdate desc ";
        if($option_value["view_page"] > 0) {
            $start_num = ($option_value["view_page"] - 1) * $option_value["page_num"];
            $sql = $sql." limit ".strval($start_num).",".strval($option_value["page_num"])." ";
        }
        $sql = $sql.";";
        $ret = DB::bmlDB()->allPrepare($sql, $mode=PDO::FETCH_ASSOC);

        $ret_code["code"] = BmlApiErrcode::$view_success;
        $ret_code["data"] = $ret->data;
        return $ret_code;

    }
 
}
