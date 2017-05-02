<?php
ini_set('display_errors', 1);
class PathConfig {
    public static $BML_DIR = "/home/bml/bml_cmd";
    public static $HADOOP_DIR = "/home/bml/bml_soft/hadoop-client/hadoop";
    public static $HPC_DIR = "/home/bml/bml_soft/hpc_client";
    public static $bmlBase = array("dbtype" => "mysql", "database" => "bml_meta", "username" => "root","password" => "root", "server" => "localhost"); 
    public static $log_path = "/home/bml/bml_api/log/";
    public static $jobcmd_timestamp = 10;
    public static $jobcmd_maxtry = 30;
    public static $qdel_str = "[INFO] qsub_f: to stop, pls run: qdel ";
    public static $bml_job_end = "Usage Info: All Completed!"; 
}
?>
