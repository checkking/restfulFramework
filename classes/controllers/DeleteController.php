<?php
require_once dirname(__FILE__) . '/AbstractController.php';
require_once dirname(__FILE__) . '/../../config/PathConfig.php';
include_once ("BmlApiErrcode.php");
include_once ("db.inc");
include_once ("myPDO.inc");
/**
 * @Delete from Database api for BML
 * @version 1.0
 * @date 2014-12-11 11:24:08 
 */
class DeleteController extends AbstractController
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
     *         $request->url_elements[1] = $delete_option
     *         $request->post_data = $delete_algo_parameters
     * @return string
     */
    public function post($request)
    {
        $ret_code = array(
                "code" => BmlApiErrcode::$unknow_err,
                "data" => null,
                );

        if (count($request->url_elements) < 2) {
            $ret_code["code"] = BmlApiErrcode::$delete_para_lost;
            $ret_code["data"] = "please give option patameter to delete";
            return $ret_code;
        }

        if(!is_object(DB::bmlDB())){
            $ret_code["code"] = BmlApiErrcode::$DB_err; 
            $ret_code["data"] = "bml database err";
            return $ret_code;
        } 

        $delete_object = $request->url_elements[1];

        switch ($delete_object) {
            case "dataset":
                $ret_code = self::delete_dataset($request->post_data);
                break;
            case "model":
                $ret_code = self::delete_model($request->post_data);
                break;
            default:
                $ret_code["code"] = BmlApiErrcode::$delete_para_wrong;
                $ret_code["data"] = "parameter to delete is wrong";
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

    private function delete_dataset($post_data) {
        $ret_code = array(
                "code" => BmlApiErrcode::$unknow_err,
                "data" => null,
                );

        $para_key = array("sys_user_name", "dataset_id");
        foreach ($para_key as $each_key) {
            if (!array_key_exists($each_key, $post_data)){
                $ret_code["code"] = BmlApiErrcode::$delete_para_key_lost;
                $ret_code["data"] = "parameter '".$each_key."' must be configured";
                return $ret_code; 
            }

            if (trim($post_data[$each_key]) == "") {
                $ret_code["code"] = BmlApiErrcode::$delete_para_key_null;
                $ret_code["data"] = "parameter '".$each_key."' can't be null";
                return $ret_code; 
            }
        }

        //$sql = "select dataset_name, user_email from bml_dataset ";
        //$sql = $sql."where user_email='".$post_data["sys_user_name"]."' and ";
        //$sql = $sql." dataset_name='".$post_data["dataset_id"]."' and dataset_status=0;";
        $sql = "select bml_dataset.user_email, dataset_name, dataset_status, bml_dataset.job_id";
        $sql = $sql.", job_status from bml_dataset join bml_job on bml_dataset.job_id = bml_job.job_id ";
        $sql = $sql." where bml_dataset.user_email='".$post_data["sys_user_name"]."' ";
        $sql = $sql."and bml_dataset.dataset_name='".$post_data["dataset_id"]."' and job_status<=0;";
        $ret = DB::bmlDB()->allPrepare($sql, $mode=PDO::FETCH_ASSOC);
        if(count($ret->data) == 0){
            $ret_code["code"] = BmlApiErrcode::$delete_no_dataset;
            $ret_code["data"] = "No exist dataset '".$post_data["dataset_id"]."' for user '".$post_data["sys_user_name"]."'";
            return $ret_code;
        }

        //$sql = "delete from bml_dataset ";
        //$sql = $sql."where user_email='".$post_data["sys_user_name"]."' and ";
        //$sql = $sql." dataset_name='".$post_data["dataset_id"]."' and dataset_status=0;";
        $sql = "delete from bml_dataset where job_id in(select job_id from (select bml_dataset.job_id";
        $sql = $sql.", job_status from bml_dataset join bml_job on bml_dataset.job_id = bml_job.job_id ";
        $sql = $sql." where bml_dataset.user_email='".$post_data["sys_user_name"]."' ";
        $sql = $sql."and bml_dataset.dataset_name='".$post_data["dataset_id"]."' and job_status<=0) as job_id_list);";
        $ret = DB::bmlDB()->allPrepare($sql, $mode=PDO::FETCH_ASSOC);
 
        $ret_code["code"] = BmlApiErrcode::$delete_success;
        $ret_code["data"] = "Delete  dataset '".$post_data["dataset_id"];
        $ret_code["data"] = $ret_code["data"]."' for user '".$post_data["sys_user_name"]."' in Database successfully";
        return $ret_code;

    }
    
    private function delete_model($post_data) {
        $ret_code = array(
                "code" => BmlApiErrcode::$unknow_err,
                "data" => null,
                );

        $para_key = array("sys_user_name", "model_id");
        foreach ($para_key as $each_key) {
            if (!array_key_exists($each_key, $post_data)){
                $ret_code["code"] = BmlApiErrcode::$delete_para_key_lost;
                $ret_code["data"] = "parameter '".$each_key."' must be configured";
                return $ret_code; 
            }

            if (trim($post_data[$each_key]) == "") {
                $ret_code["code"] = BmlApiErrcode::$delete_para_key_null;
                $ret_code["data"] = "parameter '".$each_key."' can't be null";
                return $ret_code; 
            }
        }

        //$sql = "select model_name, user_email from bml_model ";
        //$sql = $sql."where user_email='".$post_data["sys_user_name"]."' and ";
        //$sql = $sql." model_name='".$post_data["model_id"]."' and model_status=0;";
        $sql = "select bml_model.user_email, model_name, model_status, bml_model.job_id";
        $sql = $sql.", job_status from bml_model join bml_job on bml_model.job_id = bml_job.job_id ";
        $sql = $sql." where bml_model.user_email='".$post_data["sys_user_name"]."' ";
        $sql = $sql."and bml_model.model_name='".$post_data["model_id"]."' and job_status<=0;";
        $ret = DB::bmlDB()->allPrepare($sql, $mode=PDO::FETCH_ASSOC);
        if(count($ret->data) == 0){
            $ret_code["code"] = BmlApiErrcode::$delete_no_model;
            $ret_code["data"] = "No exist model '".$post_data["model_id"]."' for user '".$post_data["sys_user_name"]."'";
            return $ret_code;
        }

        //$sql = "delete from bml_model ";
        //$sql = $sql."where user_email='".$post_data["sys_user_name"]."' and ";
        //$sql = $sql." model_name='".$post_data["model_id"]."' and model_status=0;";
        $sql = "delete from bml_model where job_id in(select job_id from (select bml_model.job_id";
        $sql = $sql.", job_status from bml_model join bml_job on bml_model.job_id = bml_job.job_id ";
        $sql = $sql." where bml_model.user_email='".$post_data["sys_user_name"]."' ";
        $sql = $sql."and bml_model.model_name='".$post_data["model_id"]."' and job_status<=0) as job_id_list);";
        $ret = DB::bmlDB()->allPrepare($sql, $mode=PDO::FETCH_ASSOC);
    
        $sql = "delete from bml_model_evaluate ";
        $sql = $sql."where user_email='".$post_data["sys_user_name"]."' and ";
        $sql = $sql." model_name='".$post_data["model_id"]."';";
        $ret = DB::bmlDB()->allPrepare($sql, $mode=PDO::FETCH_ASSOC);
 
        $ret_code["code"] = BmlApiErrcode::$delete_success;
        $ret_code["data"] = "Delete  model '".$post_data["model_id"];
        $ret_code["data"] = $ret_code["data"]."' for user '".$post_data["sys_user_name"]."' in Database successfully";
        return $ret_code;

    }
 
}
