<?php
require_once dirname(__FILE__) . '/AbstractController.php';
require_once dirname(__FILE__) . '/../../config/PathConfig.php';
include_once ("BmlApiErrcode.php");
include_once ("db.inc");
include_once ("myPDO.inc");
/**
 * @Datasource api for BML
 * @version 1.0
 * @date 2014-12-11 11:24:08 
 */
class DatasourceController extends AbstractController
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
     *         $request->url_elements[1] = $datasource_option
     *         $request->post_data = $datasource_parameters
     * @return string
     */
    public function post($request)
    {
        $ret_code = array(
                "code" => BmlApiErrcode::$unknow_err,
                "data" => null,
                );

        if (count($request->url_elements) < 2) {
            $ret_code["code"] = BmlApiErrcode::$datasource_para_lost;
            $ret_code["data"] = "please give parameter to view";
            return $ret_code;
        }

        if(!is_object(DB::bmlDB())){
            $ret_code["code"] = BmlApiErrcode::$DB_err; 
            $ret_code["data"] = "bml database err";
            return $ret_code;
        } 

        $datasource_object = $request->url_elements[1];
        $sql = "select * from bml_user where user_email='".$request->post_data["sys_user_name"]."';";
        $ret = DB::bmlDB()->allPrepare($sql);

        if (count($ret->data) <= 0) {
            $ret_code["code"] = BmlApiErrcode::$not_register_user;
            $ret_code["data"] = "username:".$request->post_data["sys_user_name"]." is not register in BML!\nPlease register in http://bml.baidu.com/register.php";
            return $ret_code;
        }


        switch ($datasource_object) {
            case "add":
                $ret_code = self::add_datasource($request->post_data);
                break;
            case "view":
                $ret_code = self::view_datasource($request->post_data);
                break;
            case "delete":
                $ret_code = self::delete_datasource($request->post_data);
                break;
            case "list":
                $ret_code = self::list_datasource($request->post_data);
                break;
            default:
                $ret_code["code"] = BmlApiErrcode::$datasource_para_wrong;
                $ret_code["data"] = "parameter to datasource is wrong";
                break;
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

    private function add_datasource($post_data) {
        $ret_code = array(
                "code" => BmlApiErrcode::$unknow_err,
                "data" => null,
                );

        $para_key = array("sys_user_name", "sys_hadoop_name", "sys_hadoop_ugi", "sys_hadoop_tracker", "datasource_id", "datasource_path");
        foreach ($para_key as $each_key) {
            if (!array_key_exists($each_key, $post_data)){
                $ret_code["code"] = BmlApiErrcode::$datasource_para_key_lost;
                $ret_code["data"] = "parameter '".$each_key."' must be configured";
                return $ret_code; 
            }

            if (trim($post_data[$each_key]) == "") {
                $ret_code["code"] = BmlApiErrcode::$datasource_para_key_null;
                $ret_code["data"] = "parameter '".$each_key."' can't be null";
                return $ret_code; 
            }
        }

        $sql = "select * from bml_datasource where user_email='".$post_data["sys_user_name"]."' and ";
        $sql = $sql."datasource_id='".$post_data["datasource_id"]."';";
        $ret = DB::bmlDB()->allPrepare($sql);
        if (count($ret->data) > 0) {
            $ret_code["code"] = BmlApiErrcode::$datasource_add_exist_id;
            $ret_code["data"] = "datasouce id '".$post_data["datasource_id"]."' for user '".$post_data["sys_user_name"];
            $ret_code["data"] = $ret_code["data"]."' already exist! please change datasource id";
            $ret_code["data"] = $ret_code["data"]." or delete the old one";
            return $ret_code;
        }

        $sql = "insert into bml_datasource set user_email='".$post_data["sys_user_name"]."', ";
        $sql = $sql."datasource_id='".$post_data["datasource_id"]."', datasource_path='".$post_data["datasource_path"]."', ";
        $sql = $sql."datasource_hadoop_name='".$post_data["sys_hadoop_name"]."', datasource_hadoop_ugi='".$post_data["sys_hadoop_ugi"]."', ";
        $sql = $sql." datasource_hadoop_tracker='".$post_data["sys_hadoop_tracker"]."', ";
        $sql = $sql."cdate=now(), udate=now();";
        $ret = DB::bmlDB()->allPrepare($sql, $mode=PDO::FETCH_ASSOC);
        $ret_code["code"] = BmlApiErrcode::$datasource_success;
        $ret_code["data"] = "Add datasource success";
        return $ret_code;

    }

    private function delete_datasource($post_data) {
        $ret_code = array(
                "code" => BmlApiErrcode::$unknow_err,
                "data" => null,
                );

        $para_key = array("sys_user_name", "datasource_id");
        foreach ($para_key as $each_key) {
            if (!array_key_exists($each_key, $post_data)){
                $ret_code["code"] = BmlApiErrcode::$datasource_para_key_lost;
                $ret_code["data"] = "parameter '".$each_key."' must be configured";
                return $ret_code; 
            }

            if (trim($post_data[$each_key]) == "") {
                $ret_code["code"] = BmlApiErrcode::$datasource_para_key_null;
                $ret_code["data"] = "parameter '".$each_key."' can't be null";
                return $ret_code; 
            }
        }

        $sql = "select * from bml_datasource where user_email='".$post_data["sys_user_name"]."' and ";
        $sql = $sql."datasource_id='".$post_data["datasource_id"]."';";
        $ret = DB::bmlDB()->allPrepare($sql);
        if (count($ret->data) <= 0) {
            $ret_code["code"] = BmlApiErrcode::$datasource_delete_no_id;
            $ret_code["data"] = "datasouce id '".$post_data["datasource_id"]."' for user '".$post_data["sys_user_name"];
            $ret_code["data"] = $ret_code["data"]."' not exist!";
            return $ret_code;
        }

        $sql = "delete from bml_datasource where user_email='".$post_data["sys_user_name"]."' and ";
        $sql = $sql."datasource_id='".$post_data["datasource_id"]."';";
        $ret = DB::bmlDB()->allPrepare($sql, $mode=PDO::FETCH_ASSOC);

        $ret_code["code"] = BmlApiErrcode::$datasource_success;
        $ret_code["data"] = "Delete datasource success";
        return $ret_code;

    }

    private function view_datasource($post_data) {
        $ret_code = array(
                "code" => BmlApiErrcode::$unknow_err,
                "data" => null,
                );

        $para_key = array("sys_user_name", "datasource_id");
        foreach ($para_key as $each_key) {
            if (!array_key_exists($each_key, $post_data)){
                $ret_code["code"] = BmlApiErrcode::$datasource_para_key_lost;
                $ret_code["data"] = "parameter '".$each_key."' must be configured";
                return $ret_code; 
            }

            if (trim($post_data[$each_key]) == "") {
                $ret_code["code"] = BmlApiErrcode::$datasource_para_key_null;
                $ret_code["data"] = "parameter '".$each_key."' can't be null";
                return $ret_code; 
            }
        }


        $sql = "select * from bml_datasource where user_email='".$post_data["sys_user_name"]."' and ";
        $sql = $sql."datasource_id='".$post_data["datasource_id"]."';";
        $ret = DB::bmlDB()->allPrepare($sql);
        if (count($ret->data) <= 0) {
            $ret_code["code"] = BmlApiErrcode::$datasource_view_no_id;
            $ret_code["data"] = "datasouce id '".$post_data["datasource_id"]."' for user '".$post_data["sys_user_name"];
            $ret_code["data"] = $ret_code["data"]."' not exist!";
            return $ret_code;
        }


        $sql = "select * from bml_datasource where user_email='".$post_data["sys_user_name"]."' and ";
        $sql = $sql."datasource_id='".$post_data["datasource_id"]."' order by cdate desc;";
        $ret = DB::bmlDB()->allPrepare($sql, $mode=PDO::FETCH_ASSOC);
        $ret_code["code"] = BmlApiErrcode::$datasource_success;
        $ret_code["data"] = $ret->data[0];
        return $ret_code;

    }

    private function list_datasource($post_data) {
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
                $ret_code["code"] = BmlApiErrcode::$datasource_para_key_lost;
                $ret_code["data"] = "parameter '".$each_key."' must be configured";
                return $ret_code; 
            }

            if (trim($post_data[$each_key]) == "") {
                $ret_code["code"] = BmlApiErrcode::$datasource_para_key_null;
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

        $sql = "select * from bml_datasource where user_email='".$post_data["sys_user_name"]."' order by cdate desc ";
        if($option_value["view_page"] > 0) {
            $start_num = ($option_value["view_page"] - 1) * $option_value["page_num"];
            $sql = $sql." limit ".strval($start_num).",".strval($option_value["page_num"])." ";
        }

        $ret = DB::bmlDB()->allPrepare($sql, $mode=PDO::FETCH_ASSOC);

        foreach($ret->data as $k=>$v) {
            //$ret->data[$k]["datasource_id"] = "xxxxx";
            //$k["datasource_id"] = urlencode($k["datasource_id"]);
        }
        $ret_code["code"] = BmlApiErrcode::$datasource_success;
        $ret_code["data"] = $ret->data;
        return $ret_code;

    }


}
