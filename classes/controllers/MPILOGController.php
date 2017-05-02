<?php
require_once dirname(__FILE__) . '/AbstractController.php';
require_once dirname(__FILE__) . '/../../config/PathConfig.php';
include_once ("BmlApiErrcode.php");
include_once ("db.inc");
include_once ("myPDO.inc");
/**
* API of MLCloud MPI NODES LOG 
* 2014-8-13 19:57:11
*/
class MPILOGController extends AbstractController
{
    //some common variables in LDA module
    //software path
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
            'code' => BmlApiErrcode::$unknow_err,
            'data' => null,
        );
        
        if(!is_object(DB::bmlDB())){
            $ret_code["code"] = BmlApiErrcode::$DB_err; 
            $ret_code["data"] = "bml database err";
            return $ret_code;
        } 
 
        //check api action, call assicate function
        switch ($request->url_elements[1]){
            case "update":
                $ret_code = $this->updateMPILOG($request);
                break;
            //wrong api name, show remind
            default:
                $ret_code['code']=BmlApiErrcode::$mpi_node_log_para_wrong;
                $ret_code['data']="wrong api_action name [update]!";
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

    /**
     * update MPI LOG
     *
     * @param  $request
     * @return submit status
     */
    protected function updateMPILOG($request){
        $ret_code = array(
            'code' => BmlApiErrcode::$unknow_err,
            'data' => null,
        );
        //get parameters from post request
        $postdata = $request->post_data;
        $neccessary_key = array("job_id", "rank_id", "log_data");
        foreach($neccessary_key as $key){
            if(!array_key_exists($key,$postdata)){
                $ret_code['code'] = BmlApiErrcode::$mpi_node_log_para_key_lost;
                $ret_code['data'] = $key." must be configured!";
                return $ret_code;
            }
            if(trim($postdata[$key]) == ""){
                $ret_code["code"] = BmlApiErrcode::$mpi_node_log_para_key_null;
                $ret_code["data"] = "parameter '".$key."' can't be null";
                return $ret_code; 
            }

        }
        $job_dir = PathConfig::$BML_DIR."/running/".$postdata["job_id"];
        $log_dir = PathConfig::$BML_DIR."/log/".$postdata["job_id"];
        if(!file_exists($job_dir) or !file_exists($log_dir)){
            $ret_code['code'] = BmlApiErrcode::$mpi_node_log_job_not_exist;
            $ret_code['data'] = $postdata["job_id"]." not exists!";
            return $ret_code;
        }
        
        $visual_str = "[BML-LOG][VISUAL]";
        $visual_sep = "[BML-SEP]";
        $visual_length = 3;
        $log_data = $postdata["log_data"];
        if(strpos($log_data, $visual_str) !== false){
            $begin_pos = strpos($log_data, $visual_str) + strlen($visual_str);
            $visual_str = substr($log_data, $begin_pos);
            $visual_array = explode($visual_sep, $visual_str);
            
            if(count($visual_array) != $visual_length){
                $ret_code['code'] = BmlApiErrcode::$mpi_node_log_visual_value_error;
                $ret_code['data'] = "VISUAL log value error, please check:".$visual_str;
                return $ret_code;
            }

            $sql = "select job_type from bml_job where job_id='".$postdata["job_id"]."';";
            $ret = DB::bmlDB()->allPrepare($sql, $mode=PDO::FETCH_ASSOC);
            if(count($ret) <= 0){
                $ret_code['code'] = BmlApiErrcode::$mpi_node_log_job_not_exist;
                $ret_code['data'] = $postdata["job_id"]." not exists!";
                return $ret_code;
            }
            $job_type = $ret->data[0]["job_type"];

            $sql = "insert into bml_job_visual set job_id='".$postdata["job_id"]."', job_node=".$postdata["rank_id"];
            $sql = $sql.", visual_iter=".$visual_array[0].", visual_key='".$visual_array[1]."', visual_value='".$visual_array[2];
            $sql = $sql."', cdate=now(), job_type='".$job_type."';";
            $ret = DB::bmlDB()->allPrepare($sql, $mode=PDO::FETCH_ASSOC);

            $cmdstr = "cd ".$log_dir."; echo \"".$log_data."\" >> visual_log_node_".$postdata["rank_id"];
            system($cmdstr);
        }else{
            $cmdstr = "cd ".$log_dir."; echo \"".$log_data."\" >> mpi_log_node_".$postdata["rank_id"];
            system($cmdstr);
        }
        $ret_code['code'] = BmlApiErrcode::$mpi_node_log_success;
        $ret_code['data'] = "update log success";
        return $ret_code;
    }

}
