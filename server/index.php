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
                global $JSless,$db;
                $_SESSION["token_list"] = bin2hex(random_bytes(64));
                if($JSless){
                    $query_results = $db->select("SELECT * FROM `".DB_PREFIX."_profiles` ORDER BY available DESC, chief DESC, services ASC, availability_minutes ASC, name ASC");
                } else {
                    $query_results = null;
                }
                return [
                    'list.html',
                    'Availability List',
                    ['token_list' => $_SESSION["token_list"], 'query_results' => $query_results]
                ];
            }
        );
        $r->addRoute(
            'GET', '/services', function ($vars) {
                global $JSless,$db;
                if($JSless){
                    $query_results = $db->select("SELECT * FROM `".DB_PREFIX."_services` ORDER BY date DESC, beginning DESC");
                } else {
                    $query_results = null;
                }
                return [
                    'services.html',
                    'Services',
                    ['query_results' => $query_results]
                ];
            }
        );
        $r->addRoute(
            'GET', '/trainings', function ($vars) {
                global $JSless,$db;
                if($JSless){
                    $query_results = $db->select("SELECT * FROM `".DB_PREFIX."_trainings` ORDER BY date DESC, beginning desc");
                } else {
                    $query_results = null;
                }
                return [
                    'trainings.html',
                    'Trainings',
                    ['query_results' => $query_results]
                ];
            }
        );
        $r->addRoute(
            'GET', '/log', function ($vars) {
                global $JSless,$db;
                if($JSless){
                    $query_results = $db->select("SELECT * FROM `".DB_PREFIX."_log` ORDER BY `timestamp` DESC");
                } else {
                    $query_results = null;
                }
                return [
                    'log.html',
                    'Logs',
                    ['query_results' => $query_results]
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
    $twig_options = $callback_results[2];
    $twig_options = array_merge($twig_options, ['title' => t($callback_results[1], false)]);
    $requireLogin = isset($callback_results[3]) ? $callback_results[3] : false;
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
