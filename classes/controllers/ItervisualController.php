<?php
require_once dirname(__FILE__) . '/AbstractController.php';
require_once dirname(__FILE__) . '/../../config/PathConfig.php';
include_once ("BmlApiErrcode.php");
include_once ("db.inc");
include_once ("myPDO.inc");

class ItervisualController extends AbstractController
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
        
        if(!is_object(DB::bmlDB())){
            $ret_code["code"] = BmlApiErrcode::$DB_err; 
            $ret_code["data"] = "bml database err";
            return $ret_code;
        } 
 
        //check api action, call assicate function
        switch ($request->url_elements[1]){
            case "job":
                $ret_code = $this->visual_job($request->post_data);
                break;
            case "model":
                $ret_code = $this->visual_model($request->post_data);
                break;
            //wrong api name, show remind
            default:
                $ret_code['code']=BmlApiErrcode::$itervisual_para_wrong;
                $ret_code['data']="wrong visual parameter!";
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

    public function visual_job($post_data){
        $ret_code = array(
            "code" => BmlApiErrcode::$unknow_err,
            "data" => null,
            );

        $para_key = array("job_id", "sys_user_name");
        foreach ($para_key as $each_key) {
            if (!array_key_exists($each_key, $post_data)){
                $ret_code["code"] = BmlApiErrcode::$itervisual_para_key_lost;
                $ret_code["data"] = "parameter '".$each_key."' must be configured";
                return $ret_code; 
            }

            if (trim($post_data[$each_key]) == "") {
                $ret_code["code"] = BmlApiErrcode::$itervisual_para_key_null;
                $ret_code["data"] = "parameter '".$each_key."' can't be null";
                return $ret_code; 
            }
        }

        $sql = "select job_id from bml_job";
        $sql = $sql." where job_id='".$post_data["job_id"]."' and user_email='".$post_data["sys_user_name"]."';";
        $ret = DB::bmlDB()->allPrepare($sql, $mode=PDO::FETCH_ASSOC);
        if(count($ret->data) <= 0){
            $ret_code["code"] = BmlApiErrcode::$itervisual_no_jobid;
            $ret_code["data"] = "user '".$post_data["sys_user_name"]."' don't have BML job: '".$post_data["job_id"]."'!";
            return $ret_code;
        }


        //$sql = "select job_id, job_type, job_node, count(*) as iter_num,";
        //$sql = $sql." visual_key, group_concat(visual_value order by visual_iter) as visual_value_str from ";
        //$sql = $sql." bml_job_visual where job_id='".$post_data["job_id"]."' ";
        //$sql = $sql." group by job_id, job_type, job_node, visual_key;";
        $sql = "select job_id, job_type, job_node, visual_key, visual_value from bml_job_visual where job_id='".$post_data["job_id"]."' ";
        $sql = $sql." order by job_id, job_type, job_node, visual_key, visual_iter;";
        $ret = DB::bmlDB()->allPrepare($sql, $mode=PDO::FETCH_ASSOC);
        if(count($ret->data) <= 0){
            $ret_code["code"] = BmlApiErrcode::$itervisual_no_iterdata;
            $ret_code["data"] = "No iter visual data for BML job: '".$post_data["job_id"]."'!";
            return $ret_code;
        }
        
        $result = array();
        $pre_job_id = "";
        $pre_job_type = "";
        $pre_job_node = "";
        $pre_visual_key = "";
        $visual_value_str = "";
        $iter_num = 0;
        
        foreach ($ret->data as $eachline){
            if (($pre_job_id != "" and $eachline["job_id"] != $pre_job_id) or
                ($pre_job_type != "" and $eachline["job_type"] != $pre_job_type) or
                ($pre_job_node != "" and $eachline["job_node"] != $pre_job_node) or
                ($pre_visual_key != "" and $eachline["visual_key"] != $pre_visual_key)) {
                $temp = array(
                        "job_id" => $pre_job_id,
                        "job_type" => $pre_job_type,
                        "job_node" => $pre_job_node,
                        "visual_key" => $pre_visual_key,
                        "iter_num" => $iter_num,
                        "visual_value_str" => $visual_value_str,
                        );
                array_push($result, $temp);
                $visual_value_str = "";
                $iter_num = 0;
            }
            $iter_num = $iter_num + 1;
            if($visual_value_str == ""){
                $visual_value_str = trim($eachline["visual_value"]);
            } else {
                $visual_value_str = $visual_value_str.",".trim($eachline["visual_value"]);
            }
            $pre_job_id = $eachline["job_id"];
            $pre_job_type = $eachline["job_type"];
            $pre_job_node = $eachline["job_node"];
            $pre_visual_key = $eachline["visual_key"];
        }

        $temp = array(
                "job_id" => $pre_job_id,
                "job_type" => $pre_job_type,
                "job_node" => $pre_job_node,
                "visual_key" => $pre_visual_key,
                "iter_num" => $iter_num,
                "visual_value_str" => $visual_value_str,
                );
        array_push($result, $temp);

        $ret_code["code"] = BmlApiErrcode::$itervisual_success;
        $ret_code["data"] = $result;
 
        return $ret_code;
    }

    public function visual_model($post_data){
        $ret_code = array(
            "code" => BmlApiErrcode::$unknow_err,
            "data" => null,
            );

        $para_key = array("sys_user_name", "model_id");
        foreach ($para_key as $each_key) {
            if (!array_key_exists($each_key, $post_data)){
                $ret_code["code"] = BmlApiErrcode::$itervisual_para_key_lost;
                $ret_code["data"] = "parameter '".$each_key."' must be configured";
                return $ret_code; 
            }

            if (trim($post_data[$each_key]) == "") {
                $ret_code["code"] = BmlApiErrcode::$itervisual_para_key_null;
                $ret_code["data"] = "parameter '".$each_key."' can't be null";
                return $ret_code; 
            }
        }

        $sql = "select job_id , user_email as sys_user_name from bml_model where user_email='".$post_data["sys_user_name"]."'";
        $sql = $sql." and model_name='".$post_data["model_id"]."';";
        $ret = DB::bmlDB()->allPrepare($sql, $mode=PDO::FETCH_ASSOC);
        if(count($ret->data) <= 0){
            $ret_code["code"] = BmlApiErrcode::$itervisual_no_modelid;
            $ret_code["data"] = "No exist or running model: '".$post_data["model_id"]."' ";
            $ret_code["data"] = $ret_code["data"]." for user '".$post_data["sys_user_name"]."'!";
            return $ret_code;
        }
 
        return $this->visual_job($ret->data[0]);
    }
}
