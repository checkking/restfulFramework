<?php
include_once("config/PathConfig.php");
/**
 * Generic class autoloader.
 *
 * @param string $class_name
 */
function autoload_class($class_name) {
    $directories = array(
        'classes/',
        'classes/controllers/',
        'classes/models/',
    );
    foreach ($directories as $directory) {
        $filename = $directory . $class_name . '.php';
        if (is_file($filename)) {
            require($filename);
            break;
        }
    }
}
spl_autoload_register('autoload_class');

/**
 * Write log file
 *
 * @param string $ret
 */
function paddle_log($ret) {
    file_put_contents("./log/preprocess.log", serialize($ret)."\r\n",FILE_APPEND);
}

$request = new JsonRequest();
$response = new JsonResponse();

/**
 * Parse the incoming request.
 */
if (isset($_SERVER['PATH_INFO'])) {
    $request->url_elements = explode('/', trim($_SERVER['PATH_INFO'], '/'));
}

$request->method = strtoupper($_SERVER['REQUEST_METHOD']);
$request->post_data = $_POST;
//$request->json_data = json_decode(file_get_contents("php://input"), true);

/**
 * Route the request.
 */
if (!empty($request->url_elements)) {
    $controller_name = ucfirst($request->url_elements[0]) . 'Controller';
    if (class_exists($controller_name)) {
        $controller = new $controller_name;
        $action_name = strtolower($request->method);
        $ret = call_user_func_array(array($controller, $action_name), array($request));
        if ($request->url_elements[0] == "joblog" or $request->url_elements[0] == "bmlstatus") {
            $response->render_flat($ret['code'], $ret['data']);
        } else if ($request->url_elements[0] =="view"){
            $response->render_view($ret['code'], $ret['data']);
        } else {
            $response->render($ret['code'], $ret['data']);
        }
    } else {
        $response_str = 'Request: ' . $request->url_elements[0] . ' Not Found.';
        $response->render(404, $response_str);
    }
} else {
    $response_str = 'Bad Request';
    $response->render(400, $response_str);
}
?>
