<?php
require_once dirname(__FILE__) . '/AbstractController.php';
require_once dirname(__FILE__) . '/../../config/PathConfig.php';
include_once ("BmlApiErrcode.php");
include_once ("db.inc");
include_once ("myPDO.inc");
/**
 * @submit job api for BML
 * @version 1.0
 * @date 2014-12-11 11:24:08 
 */
class SubmitController extends AbstractController
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
     *         $request->url_elements[1] = $submit_algo_name
     *         $request->post_data = $submit_algo_parameters
     * @return string
     */
    public function post($request)
    {
        $ret_code = array(
                "code" => BmlApiErrcode::$unknow_err,
                "data" => null,
                );

        if(count($request->url_elements) < 2){
            $ret_code["code"] = BmlApiErrcode::$submit_algo_name_lost;
            $ret_code["data"] = "please give algorithm name to submit";
            return $ret_code;
        }

        $algo_name = $request->url_elements[1];

        if(!is_object(DB::bmlDB())){
            $ret_code["code"] = BmlApiErrcode::$DB_err; 
            $ret_code["data"] = "bml database err";
            return $ret_code;
        } 

        //check submit algo name in BML database
        $sql = "select algo_id, algo_type from bml_algo where algo_name='".$algo_name."';";
        $ret = DB::bmlDB()->allPrepare($sql);

        if(count($ret->data) == 0){
            $ret_code["code"] = BmlApiErrcode::$submit_algo_name_wrong;
            $ret_code["data"] = "wrong algorithm name '".$algo_name."' to submit";
            return $ret_code;

        }

        $algo_id = $ret->data[0]["algo_id"];
        $algo_type = $ret->data[0]["algo_type"];

        //get algo parameter info in BML database
        $sql = "select algo_para_name, algo_para_name_alias, algo_para_type, algo_para_option, algo_para_fill, algo_para_default from bml_algo_para where algo_id=".$algo_id.";";
        $ret = DB::bmlDB()->allPrepare($sql);
        $algo_para_list = $ret->data;

        $input_para_data = $request->post_data;
        $job_name = $algo_name;

        //check necessary parameters all configured
        foreach($algo_para_list as $algo_para){
            if($algo_para["algo_para_option"] == 1){
                if(!array_key_exists($algo_para["algo_para_name"], $input_para_data)){
                    $ret_code["code"] = BmlApiErrcode::$submit_algo_para_lost;
                    $ret_code["data"] = "parameter '".$algo_para["algo_para_name"]."' for algorithm '".$algo_name."' must be configured";
                    return $ret_code; 
                }

                if(trim($input_para_data[$algo_para["algo_para_name"]]) == ""){
                    $ret_code["code"] = BmlApiErrcode::$submit_algo_para_null;
                    $ret_code["data"] = "parameter '".$algo_para["algo_para_name"]."' for algorithm '".$algo_name."' can't be null";
                    return $ret_code; 
                }
            }

            //necessary but don't trim
            //parameter can be space or tab
            if($algo_para["algo_para_option"] == 2){
                if(!array_key_exists($algo_para["algo_para_name"], $input_para_data)){
                    $ret_code["code"] = BmlApiErrcode::$submit_algo_para_lost;
                    $ret_code["data"] = "parameter '".$algo_para["algo_para_name"]."' for algorithm '".$algo_name."' must be configured";
                    return $ret_code; 
                }

                if($input_para_data[$algo_para["algo_para_name"]] == ""){
                    $ret_code["code"] = BmlApiErrcode::$submit_algo_para_null;
                    $ret_code["data"] = "parameter '".$algo_para["algo_para_name"]."' for algorithm '".$algo_name."' can't be null";
                    return $ret_code; 
                }

            }


            if($algo_para["algo_para_name_alias"] == "NAME"){
                $job_name = $job_name."_".trim($input_para_data[$algo_para["algo_para_name"]]);
            }
        }

        $sql = "select * from bml_user where user_email='".$input_para_data["sys_user_name"]."';";
        $ret = DB::bmlDB()->allPrepare($sql);

        if (count($ret->data) <= 0) {
            $ret_code["code"] = BmlApiErrcode::$not_register_user;
            $ret_code["data"] = "username:".$input_para_data["sys_user_name"]." is not register in BML!\Please register in http://bml.baidu.com/register.php";
            return $ret_code;
        }

        //TODO dataset_id model_id exist check
        if ($algo_type == "dataset_add") {
            $sql = "select * from bml_dataset where user_email='".$input_para_data["sys_user_name"]."' and ";
            $sql = $sql."dataset_name='".$input_para_data["dataset_id"]."' and dataset_status=0;";
            $ret = DB::bmlDB()->allPrepare($sql);
            if (count($ret->data) > 0) {
                $ret_code["code"] = BmlApiErrcode::$add_dataset_id_exist;
                $ret_code["data"] = "Dataset id '".$input_para_data["dataset_id"]."' for user '".$input_para_data["sys_user_name"];
                $ret_code["data"] = $ret_code["data"]."' already exist! please change dataset id";
                $ret_code["data"] = $ret_code["data"]." or delete the old one using http://bml.baidu.com/api/v1/delete/dataset";
                return $ret_code;
            }

            $sql = "select * from bml_dataset where user_email='".$input_para_data["sys_user_name"]."' and ";
            $sql = $sql."dataset_name='".$input_para_data["dataset_id"]."' and dataset_status=1;";
            $ret = DB::bmlDB()->allPrepare($sql);
            if (count($ret->data) > 0) 
            {
                $running_job_id = $ret->data[0]["job_id"];
                $sql = "select job_id, job_status, job_status_log from bml_job where job_id='".$running_job_id."' and job_status > 0;";
                $ret = DB::bmlDB()->allPrepare($sql);
                if (count($ret->data) > 0)
                {
                    $running_status = $ret->data[0]["job_status"];
                    $running_status_log = $ret->data[0]["job_status_log"];
                    $ret_code["code"] = BmlApiErrcode::$add_dataset_id_running;
                    $ret_code["data"] = "Dataset id '".$input_para_data["dataset_id"]."' for user '".$input_para_data["sys_user_name"];
                    $ret_code["data"] = $ret_code["data"]."' is already running with BML job id='".$running_job_id."'";
                    $ret_code["data"] = $ret_code["data"]." with job status = ".$running_status." : ".$running_status_log."! ";
                    $ret_code["data"] = $ret_code["data"]."please change dataset id or kill previous BML job";
                    return $ret_code;
                } else {
                    $sql = "delete from bml_dataset where dataset_name='".$input_para_data["dataset_id"]."' and job_id='".$running_job_id."';";
                    $ret = DB::bmlDB()->allPrepare($sql);
                }
 
            }
        }

        if ($algo_type == "model_train") {
            $sql = "select * from bml_model where user_email='".$input_para_data["sys_user_name"]."' and ";
            $sql = $sql."model_name='".$input_para_data["model_id"]."' and model_status=0;";
            $ret = DB::bmlDB()->allPrepare($sql);
            if (count($ret->data) > 0) {
                $ret_code["code"] = BmlApiErrcode::$train_model_id_exist;
                $ret_code["data"] = "Model id '".$input_para_data["model_id"]."' for user '".$input_para_data["sys_user_name"];
                $ret_code["data"] = $ret_code["data"]."' already exist! please change model id";
                $ret_code["data"] = $ret_code["data"]." or delete the old one using http://bml.baidu.com/api/v1/delete/model";
                return $ret_code;
            }

            $sql = "select * from bml_model where user_email='".$input_para_data["sys_user_name"]."' and ";
            $sql = $sql."model_name='".$input_para_data["model_id"]."' and model_status=1;";
            $ret = DB::bmlDB()->allPrepare($sql);
            if (count($ret->data) > 0) {
                $running_job_id = $ret->data[0]["job_id"];
                $sql = "select job_id, job_status, job_status_log from bml_job where job_id='".$running_job_id."' and job_status > 0;";
                $ret = DB::bmlDB()->allPrepare($sql);
                if (count($ret->data) > 0)
                {
                    $running_status = $ret->data[0]["job_status"];
                    $running_status_log = $ret->data[0]["job_status_log"];
                    $ret_code["code"] = BmlApiErrcode::$train_model_id_running;
                    $ret_code["data"] = "Model id '".$input_para_data["model_id"]."' for user '".$input_para_data["sys_user_name"];
                    $ret_code["data"] = $ret_code["data"]."' is already running with BML job id='".$running_job_id."'";
                    $ret_code["data"] = $ret_code["data"]." with job status = ".$running_status." : ".$running_status_log."! ";
                    $ret_code["data"] = $ret_code["data"]."please change model id or kill previous BML job";
                    return $ret_code;
                } else {
                    $sql = "delete from bml_model where model_name='".$input_para_data["model_id"]."' and job_id='".$running_job_id."';";
                    $ret = DB::bmlDB()->allPrepare($sql);
                }
            }
        }

        if ($algo_type == "model_evaluate" or $algo_type == "model_batch_predict" ) {
            $sql = "select * from bml_dataset where user_email='".$input_para_data["sys_user_name"]."' and ";
            $sql = $sql."dataset_name='".$input_para_data["dataset_id"]."' and dataset_status=0;";
            $ret = DB::bmlDB()->allPrepare($sql);
            if (count($ret->data) <= 0) {
                $ret_code["code"] = BmlApiErrcode::$submit_dataset_id_not_exist;
                $ret_code["data"] = "Dataset id '".$input_para_data["dataset_id"]."' for user '".$input_para_data["sys_user_name"];
                $ret_code["data"] = $ret_code["data"]."' not exist!";
                return $ret_code;
            }

            $sql = "select * from bml_model where user_email='".$input_para_data["sys_user_name"]."' and ";
            $sql = $sql."model_name='".$input_para_data["model_id"]."' and model_status=0;";
            $ret = DB::bmlDB()->allPrepare($sql);
            if (count($ret->data) <= 0) {
                $ret_code["code"] = BmlApiErrcode::$submit_model_id_not_exist;
                $ret_code["data"] = "Model id '".$input_para_data["model_id"]."' for user '".$input_para_data["sys_user_name"];
                $ret_code["data"] = $ret_code["data"]."' not exist!";
                return $ret_code;
            }
        }

        $job_id = $job_name."_".date("Y-m-d-H-i-s");

        //To make job_id unique, add bml_job id in BML database
        $sql = "insert into bml_job set job_id=\"".$job_id."\", cdate=now(), udate=now();";
        $ret = DB::bmlDB()->allPrepare($sql);
        $bml_id = DB::bmlDB()->lastInsertId();
        $job_id = "BML_JOB_".$bml_id."_".$job_id;

        //make job workspace
        $log_dir = PathConfig::$BML_DIR."/log/".$job_id."/";
        $log_file = $log_dir."alllog";
        $workspace_dir = PathConfig::$BML_DIR."/running/".$job_id."/";

        system("rm -rf ".$log_dir);
        system("mkdir ".$log_dir);
        system("rm -rf ".$workspace_dir);
        system("mkdir ".$workspace_dir);
        system("cp -rf ".PathConfig::$BML_DIR."/common ".$workspace_dir);
        system("cp -rf ".PathConfig::$BML_DIR."/".$algo_name." ".$workspace_dir);
        system("cp -rf ".PathConfig::$BML_DIR."/*.py ".$workspace_dir);

        //make job config file
        //bmlcfg.py generated by "algo_para_name_alias" in bml_algo_para to adapting to some old algorithms
        $cfg_data = "# -*- coding: utf-8 -*-\n";
        $cfg_data = $cfg_data."bml_id=".$bml_id."\njob_id=\"".$job_id."\"\nlog_dir=\"".$log_dir."\"\nworkspace_dir=\"".$workspace_dir."\"\n";
        $cfg_data = $cfg_data."hadoop_client=\"".PathConfig::$HADOOP_DIR."\"\nhpc_client=\"".PathConfig::$HPC_DIR."\"\n";
        foreach($algo_para_list as $algo_para){
            $para_name = $algo_para["algo_para_name_alias"];
            $para_data = "";
            if($algo_para["algo_para_option"] == 1){
                $para_data = $input_para_data[$algo_para["algo_para_name"]];
            } else {
                if(array_key_exists($algo_para["algo_para_name"], $input_para_data)){
                    $para_data = $input_para_data[$algo_para["algo_para_name"]];
                } else {
                    $para_data = $algo_para["algo_para_default"];
                }
            }

            if($algo_para["algo_para_option"] != 2) {
                $para_data = trim($para_data);
            }
            $cfg_data = $cfg_data.trim($para_name)."=\"".$para_data."\"\n";
        }

        $cfg_file = fopen($workspace_dir."/bmlcfg.py", "w");
        fwrite($cfg_file, $cfg_data, strlen($cfg_data));
        fclose($cfg_file);


        //user config file
        //usercfg.py generater by "algo_para_name" in bml_algo_para
        $cfg_data = "# -*- coding: utf-8 -*-\n";
        $cfg_data = $cfg_data."bml_id=".$bml_id."\njob_id=\"".$job_id."\"\nlog_dir=\"".$log_dir."\"\nworkspace_dir=\"".$workspace_dir."\"\n";
        $cfg_data = $cfg_data."hadoop_client=\"".PathConfig::$HADOOP_DIR."\"\nhpc_client=\"".PathConfig::$HPC_DIR."\"\n";
        foreach($algo_para_list as $algo_para){
            $para_name = $algo_para["algo_para_name"];
            $para_data = "";
            if($algo_para["algo_para_option"] == 1){
                $para_data = $input_para_data[$algo_para["algo_para_name"]];
            } else {
                if(array_key_exists($algo_para["algo_para_name"], $input_para_data)){
                    $para_data = $input_para_data[$algo_para["algo_para_name"]];
                } else {
                    $para_data = $algo_para["algo_para_default"];
                }
            }

            $cfg_data = $cfg_data.trim($para_name)."=\"".trim($para_data)."\"\n";
        }

        $cfg_file = fopen($workspace_dir."/usercfg.py", "w");
        fwrite($cfg_file, $cfg_data, strlen($cfg_data));
        fclose($cfg_file);

        $sql = "update bml_job set job_id=\"".$job_id."\" , job_status=1, job_status_log=\"BML job submit success!\" where id=".$bml_id.";";
        $ret = DB::bmlDB()->allPrepare($sql);
        //run bml submit
        $ret_code["code"] = BmlApiErrcode::$submit_success;
        $ret_code["data"] = $job_id;

        $cmd = "cd ".$workspace_dir." ; nohup python excute_submit.py ".$algo_name." ".$job_id." > ".$log_file." 2>&1 &\n"; 
        system($cmd);

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
