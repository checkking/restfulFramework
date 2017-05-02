<?php
require_once dirname(__FILE__) . '/AbstractController.php';
require_once dirname(__FILE__) . '/../../config/PathConfig.php';
include_once ("BmlApiErrcode.php");
include_once ("db.inc");
include_once ("myPDO.inc");
/**
 * @Job command api for BML
 * @version 1.0
 */
class JobcmdController extends AbstractController
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
            $ret_code["code"] = BmlApiErrcode::$jobcmd_para_lost;
            $ret_code["data"] = "please give parameter to jobcmd";
            return $ret_code;
        }

        if(!is_object(DB::bmlDB())){
            $ret_code["code"] = BmlApiErrcode::$DB_err; 
            $ret_code["data"] = "bml database err";
            return $ret_code;
        } 

        $cmd_object = $request->url_elements[1];

        switch ($cmd_object) {
            case "kill":
                $ret_code = self::kill_job($request->post_data);
                break;
            case "rerun":
                $ret_code = self::rerun_job($request->post_data);
                break;
            default:
                $ret_code["code"] = BmlApiErrcode::$jobcmd_para_wrong;
                $ret_code["data"] = "parameter to jobcmd is wrong";
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

    private function check_job_end($log_file) { 
        $log_data = file_get_contents($log_file);
        $end_pos = strpos($log_data, PathConfig::$bml_job_end);
        if ($end_pos == false) {
            return false;
        }
        return true;
    }

    private function get_mpi_job_id($jobid) {
        $job_id_list = array(); 
        $log_file = PathConfig::$BML_DIR."/log/".$jobid."/alllog";
        $log_data = file_get_contents($log_file);
        $log_pos = -1;
        $log_end = strlen($log_data);
        while($log_pos < $log_end) {
            $log_pos = strpos($log_data, PathConfig::$qdel_str, $log_pos+1);
            if ($log_pos == false) {
                break;
            }
            $cmdid_begin = $log_pos + strlen(PathConfig::$qdel_str);
            $cmdid_end = strpos($log_data, "\n", $cmdid_begin);
            $mpi_job_id = substr($log_data, $cmdid_begin, $cmdid_end - $cmdid_begin);
            array_push($job_id_list, $mpi_job_id);
        }
        return $job_id_list;
    }

    private function kill_job($post_data, $kill_source="kill_job") {
        $ret_code = array(
                "code" => BmlApiErrcode::$unknow_err,
                "data" => null,
                );

        $para_key = array("sys_user_name", "job_id");
        foreach ($para_key as $each_key) {
            if (!array_key_exists($each_key, $post_data)){
                $ret_code["code"] = BmlApiErrcode::$jobcmd_para_key_lost;
                $ret_code["data"] = "parameter '".$each_key."' must be configured";
                return $ret_code; 
            }
            if (trim($post_data[$each_key]) == "") {
                $ret_code["code"] = BmlApiErrcode::$jobcmd_key_null;
                $ret_code["data"] = "parameter '".$each_key."' can't be null";
                return $ret_code; 
            }
        }

        $sql = "select job_status from bml_job ";
        $sql = $sql."where user_email='".$post_data["sys_user_name"]."' and job_id='".$post_data["job_id"]."';";
        $ret = DB::bmlDB()->allPrepare($sql, $mode=PDO::FETCH_ASSOC);

        if(count($ret->data) == 0) {
            $ret_code["code"] = BmlApiErrcode::$jobcmd_no_job;
            $ret_code["data"] = "No BML job '".$post_data["job_id"]."' for user '".$post_data["sys_user_name"]."'";
            return $ret_code;
        }

        $job_status = $ret->data[0]["job_status"];

        if ($kill_source == "kill_job") {
            if ($job_status < 0) {
                $ret_code["code"] = BmlApiErrcode::$jobcmd_jobid_error;
                $ret_code["data"] = "BML job ".$post_data["job_id"]." is already finished with error";
                return $ret_code;
            }

            if ($job_status == 0) {
                $ret_code["code"] = BmlApiErrcode::$jobcmd_jobid_success;
                $ret_code["data"] = "BML job ".$post_data["job_id"]." is already finished successfully";
                return $ret_code;
            }
        }

        $log_dir = PathConfig::$BML_DIR."/log/".$post_data["job_id"]."/";
        $workspace_dir = PathConfig::$BML_DIR."/running/".$post_data["job_id"]."/";
        $log_file = $log_dir."alllog";
        if (!file_exists($log_dir) or !file_exists($workspace_dir)) {
            $ret_code["code"] = BmlApiErrcode::$jobcmd_jobid_too_old;
            $ret_code["data"] = "BML job ".$post_data["job_id"]." is too old, has been auto cleared!";
            return $ret_code;
        } 
        
        //update bml_job database with killed status
        if ($kill_source == "kill_job") {
           $status_cmd =  "cd ".$workspace_dir." ; python excute_specific_error.py error_job_killed";
           system($status_cmd);
        }

       
        $jobcmd_try = 0;
        while (self::check_job_end($log_file) == false) {
            $jobcmd_try = $jobcmd_try + 1;
            if ($jobcmd_try >= PathConfig::$jobcmd_maxtry) {
                $ret_code["code"] = BmlApiErrcode::$jobcmd_maxtry;
                $ret_code["data"] = "Kill BML job fail for max try! Please wait and retry!";
                return $ret_code;
            }
            $mpi_job_id_list = self::get_mpi_job_id($post_data["job_id"]);
            foreach($mpi_job_id_list as $mpi_job_id) {
                $kill_cmd = PathConfig::$HPC_DIR."/bin/qdel -p ".$mpi_job_id."\n";
                system($kill_cmd);
            }
            sleep(PathConfig::$jobcmd_timestamp);
        }

        $ret_code["code"] = BmlApiErrcode::$jobcmd_success;
        $ret_code["data"] = "Kill BML job Successfull";
        return $ret_code;

    }

    private function rerun_job($post_data) {
        $ret_code = array(
                "code" => BmlApiErrcode::$unknow_err,
                "data" => null,
                );

        $para_key = array("sys_user_name", "job_id");

        foreach ($para_key as $each_key) {
            if (!array_key_exists($each_key, $post_data)){
                $ret_code["code"] = BmlApiErrcode::$jobcmd_para_key_lost;
                $ret_code["data"] = "parameter '".$each_key."' must be configured";
                return $ret_code; 
            }
            if (trim($post_data[$each_key]) == "") {
                $ret_code["code"] = BmlApiErrcode::$jobcmd_key_null;
                $ret_code["data"] = "parameter '".$each_key."' can't be null";
                return $ret_code; 
            }
        }
        $sql = "select job_status, job_type from bml_job ";
        $sql = $sql."where user_email='".$post_data["sys_user_name"]."' and job_id='".$post_data["job_id"]."';";
        $ret = DB::bmlDB()->allPrepare($sql, $mode=PDO::FETCH_ASSOC);
        if(count($ret->data) == 0) {
            $ret_code["code"] = BmlApiErrcode::$jobcmd_no_job;
            $ret_code["data"] = "No BML job '".$post_data["job_id"]."' for user '".$post_data["sys_user_name"]."'";
            return $ret_code;
        }

        $job_status = $ret->data[0]["job_status"];
        $job_type = $ret->data[0]["job_type"];
        
        $log_dir = PathConfig::$BML_DIR."/log/".$post_data["job_id"]."/";
        $workspace_dir = PathConfig::$BML_DIR."/running/".$post_data["job_id"]."/";
        if (!file_exists($log_dir) or !file_exists($workspace_dir)) {
            $ret_code["code"] = BmlApiErrcode::$jobcmd_jobid_too_old;
            $ret_code["data"] = "BML job ".$post_data["job_id"]." is too old, has been auto cleared!";
            return $ret_code;
        } 

        $status_cmd =  "cd ".$workspace_dir." ; python excute_specific_error.py error_job_rerun";
        system($status_cmd);
        
        if($job_status > 0) {
            $kill_ret_code = self::kill_job($post_data, "rerun");

            if($kill_ret_code["code"] != BmlApiErrcode::$jobcmd_success) {
                return $kill_ret_code;
            }

        }
        $sql = "update bml_job set job_id_running_count = job_id_running_count+1 ";
        $sql = $sql."where user_email='".$post_data["sys_user_name"]."' and job_id='".$post_data["job_id"]."';";
        $ret = DB::bmlDB()->allPrepare($sql, $mode=PDO::FETCH_ASSOC);
 
        system("rm ".$log_dir."/*");
        $cmd = "cd ".$workspace_dir." ; nohup python excute_submit.py ".$job_type." ".$post_data["job_id"]." > ".$log_dir."/alllog 2>&1 &\n"; 
        system($cmd);

        $ret_code["code"] = BmlApiErrcode::$jobcmd_success;
        $ret_code["data"] = $post_data["job_id"]." rerun successfully";
        return $ret_code;

    }
}
