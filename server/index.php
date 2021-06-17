<?php
require_once 'ui.php';

$user_info = [];

$dispatcher = FastRoute\simpleDispatcher(
    function (FastRoute\RouteCollector $r) {
        $r->addRoute(
            ['GET','POST'], '/', function ($vars) {
                global $user, $tools;
                if($user->authenticated) {
                    $tools->redirect("list");
                }
                $error = false;
                if(isset($_POST['name']) & isset($_POST['password'])) {
                    $login = $user->login($_POST['name'], $_POST['password'], isset($_POST["remember_me"]));
                    if($login===true) {
                        $tools->redirect("list");
                    } else {
                        $error = $login;
                        bdump($error);
                    }
                }
                return [
                    'index.html',
                    'Login',
                    ['error' => $error],
                    false
                ];
            }
        );
        $r->addRoute(
            'GET', '/list', function ($vars) {
                $_SESSION["token_list"] = bin2hex(random_bytes(64));
                return [
                    'list.html',
                    'Availability List',
                    ['token_list' => $_SESSION["token_list"]],
                    "SELECT * FROM `".DB_PREFIX."_profiles` ORDER BY available DESC, chief DESC, services ASC, availability_minutes ASC, name ASC"
                ];
            }
        );
        $r->addRoute(
            'GET', '/services', function ($vars) {
                return [
                    'services.html',
                    'Services',
                    [],
                    "SELECT * FROM `".DB_PREFIX."_services` ORDER BY date DESC, beginning DESC"
                ];
            }
        );
        $r->addRoute(
            'GET', '/trainings', function ($vars) {
                return [
                    'trainings.html',
                    'Trainings',
                    [],
                    "SELECT * FROM `".DB_PREFIX."_trainings` ORDER BY date DESC, beginning desc"
                ];
            }
        );
        $r->addRoute(
            'GET', '/log', function ($vars) {
                return [
                    'log.html',
                    'Logs',
                    [],
                    "SELECT * FROM `".DB_PREFIX."_log` ORDER BY `timestamp` DESC"
                ];
            }
        );
        $r->addRoute(
            ['GET','POST'], '/logout', function ($vars) {
                global $user, $tools;
                $user->logout();
                $tools->redirect("index");
            }
        );
    }
);

$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['PHP_SELF'];
$uri = str_replace($_SERVER['SCRIPT_NAME'], "", $uri);
$uri = str_replace("///", "/", $uri);
$uri = str_replace("//", "/", $uri);
$uri = str_replace(".php", "", $uri);
$uri = str_replace("index", "", $uri);
$uri = "/" . trim($uri, "/");

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
bdump($routeInfo);

if($_SERVER['REQUEST_METHOD'] == "OPTIONS"){
    exit();
}

function apiResponse($callback_results){

}

function uiResponse($callback_results){
    global $JSless, $db;
    if($JSless && !is_null($callback_results[3]) && !empty($callback_results[3])){
        $query_results = $db->select($callback_results[3]);
    } else {
        $query_results = null;
    }
    $twig_options = $callback_results[2];
    $twig_options = array_merge($twig_options, [
        'title' => t($callback_results[1], false),
        'query_results' => $query_results
    ]);
    $requireLogin = isset($callback_results[4]) ? $callback_results[4] : false;
    loadtemplate($callback_results[0], $twig_options, $requireLogin);
}

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        require("error_page.php");
        show_error_page(404);
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        require("error_page.php");
        $allowedMethods = $routeInfo[1];
        show_error_page(405, "Method not allowed", "Allowed methods: ".implode(",",$allowedMethods));
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        bdump($vars);
        uiResponse($handler($vars));
        break;
}
