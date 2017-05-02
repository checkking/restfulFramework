<?php

class DB {
    public static function getDbInstrance($data) {
        static $instance;
        $key = $data["server"] . "_" . $data["database"] . "_" . $data["username"];
        if (!isset($instance[$key]) || !is_object($instance[$key]))
        {
            $instance[$key] = new myPDO($data["server"], $data["database"], $data["username"], $data["password"], $data['dbtype']);
        }
        return $instance[$key];
    }

    public static function bmlDB() {
        return self::getDbInstrance(PathConfig::$bmlBase);
    }
}

?>
