<?php
class BmlApiErrcode {
    public static $common_success = 0;
    public static $unknow_err = -9999;
    public static $DB_err = -1;
    public static $not_register_user = -2;
    public static $job_killed = -3;
    public static $job_rerun = -4;

    public static $submit_success = 0;
    public static $submit_algo_name_lost = -1001;
    public static $submit_algo_name_wrong = -1002;
    public static $submit_algo_para_lost = -1003;
    public static $submit_algo_para_null = -1004;
    public static $add_dataset_id_exist = -1005;
    public static $add_dataset_id_running = -1006;
    public static $train_model_id_exist = -1005;
    public static $train_model_id_running = -1006;
    public static $submit_dataset_id_not_exist = -1007;
    public static $submit_model_id_not_exist = -1008;

    public static $view_success = 0;
    public static $view_para_lost = -2001;
    public static $view_para_wrong = -2002;
    public static $view_para_key_lost = -2003;
    public static $view_para_key_null = -2004;
    public static $view_no_dataset = -2101;
    public static $view_no_model = -2201;
    public static $view_job_conf_no_jobid = -2301;
    public static $view_job_conf_jobid_wrong = -2302;
    public static $view_job_status_no_jobid = -2301;
    public static $view_job_status_jobid_wrong = -2302;

    public static $log_success = 0;
    public static $log_jobid_lost = -3001;
    public static $log_jobid_wrong = -3002;
    
    public static $delete_success = 0;
    public static $delete_para_lost = -4001;
    public static $delete_para_wrong = -4002;
    public static $delete_para_key_lost = -4003;
    public static $delete_para_key_null = -4004;
    public static $delete_no_dataset = -4101;
    public static $delete_no_model = -4201;

    public static $jobcmd_success = 0;
    public static $jobcmd_para_lost = -5001;
    public static $jobcmd_para_wrong = -5002;
    public static $jobcmd_para_key_lost = -5003;
    public static $jobcmd_para_key_null = -5004;
    public static $jobcmd_no_job = -5005;
    public static $jobcmd_jobid_error = -5006;
    public static $jobcmd_jobid_success = -5007;
    public static $jobcmd_maxtry = -5008; 
    public static $jobcmd_jobid_too_old = -5009;

    public static $datasource_success = 0;
    public static $datasource_para_lost = -6001;
    public static $datasource_para_wrong = -6002;
    public static $datasource_para_key_lost = -6003;
    public static $datasource_para_key_null = -6004;
    public static $datasource_add_exist_id = -6005;
    public static $datasource_delete_no_id = -6006;
    public static $datasource_view_no_id = -6006;
 
    public static $mpi_node_log_success = 0;
    public static $mpi_node_log_para_wrong = -7001;
    public static $mpi_node_log_para_key_lost = -7002;
    public static $mpi_node_log_para_key_null = -7003;
    public static $mpi_node_log_job_not_exist = -7004;
    public static $mpi_node_log_visual_value_error = -7005;

    public static $itervisual_success = 0;
    public static $itervisual_para_wrong = -8001;
    public static $itervisual_para_key_lost = -8002;
    public static $itervisual_para_key_null = -8003;
    public static $itervisual_no_jobid = -8004;
    public static $itervisual_no_iterdata = -8005;
    public static $itervisual_no_modelid = -8006;
}
?>
