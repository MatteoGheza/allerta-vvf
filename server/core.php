<?php
require_once 'vendor/autoload.php';
use DebugBar\StandardDebugBar;
use MinistryOfWeb\OsmTiles\Converter;
use MinistryOfWeb\OsmTiles\LatLng;

if(!file_exists("config.php") && !file_exists("../../config.php")) {
    header('Location: install/install.php');
    exit();
}

require_once 'config.php';

if(SENTRY_ENABLED){
    \Sentry\init([
        'dsn' => SENTRY_DSN,
        'traces_sample_rate' => 0.8,
        'environment' => SENTRY_ENV
    ]);
}

session_start();
date_default_timezone_set('Europe/Rome');

function bdump($message){
    global $debugbar;
    if(!is_null($debugbar)){
        $debugbar["messages"]->addMessage($message);
    }
}

class tools
{
    public $db;
    public $profiler_enabled;
    public $profiler_last_name = "";
    public $script_nonce = null;

    public function generateNonce($bytes_lenght = 16, $base64_encode = false){
        $nonce = bin2hex(random_bytes($bytes_lenght));
        if($base64_encode){
            $nonce = base64_encode($nonce);
        }
        return $nonce;
    }

    public function __construct($db, $profiler_enabled)
    {
        $this->db = $db;
        $this->profiler_enabled = $profiler_enabled;
        if(defined("UI_MODE")){
            if(isset($_SESSION["script_nonce"]) && (
                (isset($_SERVER["HTTP_X_PJAX"]) || isset($_GET["X_PJAX"]) || isset($_GET["_PJAX"])) || 
                strpos($_SERVER['REQUEST_URI'], "edit_")
            )){
                $this->script_nonce = $_SESSION["script_nonce"];
            } else {
                $this->script_nonce = $this->generateNonce(16);
                $_SESSION["script_nonce"] = $this->script_nonce;
            }
        }
    }

    public function validate_form($data, $expected_value=null, $data_source=null)
    {
        if(is_array($data)){
            foreach($data as $element){
                if (!$this->validate_form($element, $expected_value, $data_source)) return false;
            }
            return true;
        } else {
            if(is_null($data_source) || !is_array($data_source)){
                $data_source = $_POST;
            }
            return !is_null($data) && isset($data_source[$data]) && !is_null($data_source[$data]) && (!is_null($expected_value) ? $data_source[$data] == $expected_value : true);
        }
    }

    public function get_ip()
    {
        if(!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }else{
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        if(get_option("check_cf_ip")) {
            if(!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
                $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
            }
        }
        return $ip;
    }

    public function get_page_url()
    {
        if(!empty($_SERVER["HTTPS"])) {
            if($_SERVER["HTTPS"] == "on") {
                $protocol = "https";
            } else {
                $protocol = "http";
            }
        } else {
            $protocol = "http";
        }
        $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
        return $protocol . "://" . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];
    }

    public function redirect($url)
    {
        global $debugbar;
        if(!is_null($debugbar)) $debugbar->stackData();
        if (!headers_sent()) {
            header('Location: '.$url);
        } else {
            echo '<script type="text/javascript">';
            echo 'window.location.href="'.$url.'";';
            echo '</script>';
            echo '<noscript>';
            echo '<meta http-equiv="refresh" content="0;url='.$url.'" />';
            echo '</noscript>';
        }
        exit();
    }

    public function rickroll()
    {
        $rickrolls = [
            "https://www.youtube.com/watch?v=dQw4w9WgXcQ",
            "https://www.youtube.com/watch?v=ub82Xb1C8os",
            "https://www.youtube.com/watch?v=Wjy3o0FoLYc",
            "https://www.youtube.com/watch?v=bxqLsrlakK8",
            "https://www.youtube.com/watch?v=Lrj2Hq7xqQ8"
        ];
        $this->redirect($rickrolls[array_rand($rickrolls)]); //Dear attacker/bot, have fun!
    }

    function extract_unique($data)
    {
        $this->profiler_start("Extract unique");
        $array2=[];
        foreach($data as $arr){
            if(is_array($arr)) {
                $tmp = $this->extract_unique($arr);
                foreach($tmp as $temp){
                    if(!is_array($temp)) {
                        if(!in_array($temp, $array2)) {
                            $array2[] = $temp;
                        }
                    }

                }
            } else {
                if(!in_array($arr, $array2)) {
                    $array2[] = $arr;
                }
            }
        }
        $this->profiler_stop();
        return $array2;
    }

    public function createKey($lenght=32)
    {
        return bin2hex(random_bytes($lenght));
    }

    public function sanitize($string, $htmlAllowed=false, $htmlPurifierOptions=[])
    {
        $this->profiler_start("Sanitize");
        $htmlAllowed=false; //TODO: fix HTMLPurifier_Config
        if($htmlAllowed) {
            $config = HTMLPurifier_Config::createDefault();
            foreach ($htmlPurifierOptions as $key => $value) {
                $config->set($key, $value);
            }
            $purifier = new HTMLPurifier($config);
            $string = $purifier->purify($string);
        } else {
            $string = htmlspecialchars($string);
        }
        $this->profiler_stop();
        return $string;
    }

    public function profiler_start($name=null)
    {
        global $debugbar;
        if($this->profiler_enabled && !is_null($debugbar)) {
            if(is_null($name)) {
                $name = $this->profiler_last_name;
            }
            if($name !== "") $debugbar['time']->startMeasure($name);
        }
    }

    public function profiler_stop($name=null)
    {
        global $debugbar;
        if($this->profiler_enabled && !is_null($debugbar)) {
            if(is_null($name) || $name == "") {
                $name = $this->profiler_last_name;
            }
            if($name !== "") $debugbar['time']->stopMeasure($name);
        }
    }

    public function ajax_page_response($response)
    {
        global $debugbar;
        $json_response = json_encode($response);
        $response_data = substr(crc32($json_response), 0, 10);
        header("data: ".$response_data);
        header("Content-type: application/json");
        if(!is_null($debugbar)) $debugbar->sendDataInHeaders(true);
        if(isset($_GET["oldData"]) && $_GET["oldData"] !== $response_data){
          print($json_response);
        } else {
          print("{}");
        }
    }

    public function convertMapAddressToUrl($lat, $lng, $zoom){
        switch (get_option("map_preview_generator")) {
            case 'osm':
                $converter = new Converter();
                $point     = new LatLng($lat, $lng);
                $tile = $converter->toTile($point, $zoom);
                $tile_servers = ["a", "b", "c"];
                $tileServer = $tile_servers[array_rand($tile_servers)];
                return sprintf("https://{$tileServer}.tile.openstreetmap.org/{$zoom}/%d/%d.png", $tile->getX(), $tile->getY());

            case 'custom':
            default:
                if(get_option("map_preview_generator_add_marker") && get_option("map_preview_generator_url_marker") && get_option("map_preview_generator_url_marker") !== ""){
                    $url = get_option("map_preview_generator_url_marker");
                } else {
                    $url = get_option("map_preview_generator_url");
                }
                $url = str_replace("{{LAT}}", $lat, $url);
                $url = str_replace("{{LNG}}", $lng, $url);
                $url = str_replace("{{ZOOM}}", $zoom, $url);
                return $url;
        }
    }

    public function cachePreviewMap($filename, $lat, $lng, $zoom=16){
        $url = $this->convertMapAddressToUrl($lat, $lng, $zoom);
        $options = ['http' => [
            'user_agent' => 'AllertaVVF dev version (cached map previews generator)'
        ]];
        $context = stream_context_create($options);
        $data = file_get_contents($url, false, $context);

        try {
            if (!file_exists('resources/images/map_cache')) {
                mkdir('resources/images/map_cache', 0755, true);
            }
            $filePath = "resources/images/map_cache/".$filename.".png";
            file_put_contents($filePath, $data);
            if(extension_loaded('gd')){
                $img = imagecreatefromstring(file_get_contents($filePath));
                if(get_option("map_preview_generator_add_marker") && (!get_option("map_preview_generator_url_marker") || get_option("map_preview_generator_url_marker") == "")){
                    $marker = imagecreatefromgif("resources/images/marker.gif");
                    imagecopy($img, $marker, 120, 87, 0, 0, 25, 41);
                }
                if(get_option("map_preview_generator") == "osm"){
                    $textcolor = imagecolorallocate($img, 0, 0, 0);
                    imagestring($img, 5, 0, 236, ' OpenStreetMap contributors', $textcolor);
                }
                imagepng($img, $filePath);
                imagedestroy($img);
            }
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function checkPlaceParam($place){
        if(get_option("generate_map_preview")){
            if(preg_match('/[+-]?\d+([.]\d+)?[;][+-]?\d+([.]\d+)?/', $place)){
                $lat = explode(";", $place)[0];
                $lng = explode(";", $place)[1];
                $mapImageID = \Delight\Auth\Auth::createUuid();
                $this->cachePreviewMap($mapImageID, $lat, $lng);
                $place = $place . "#" . $mapImageID;
            }
        }
        return $place;
    }

    public function savePlaceReverse($place){
        if(strpos($place, ";") === false) return 0;
        $lat = explode(";", $place)[0];
        $lng = explode("#", explode(";", $place)[1])[0];

        $url = sprintf("https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=%s&lon=%s", $lat, $lng);
        $options = ['http' => [
            'user_agent' => 'AllertaVVF dev version (place info downloader)'
        ]];
        $context = stream_context_create($options);
        $data = file_get_contents($url, false, $context);

        $this->db->insert(
            DB_PREFIX."_places_info",
            ["reverse_json" => $data]
        );
        return $this->db->getLastInsertId();
    }
}

class options
{
    protected $db;
    public $load_from_file = true;
    public $options = [];
    public $options_cache_file = null;

    public function __construct($db){
        $this->db = $db;
        $file_infos = pathinfo(array_reverse(debug_backtrace())[0]['file']);
        if(strpos($file_infos['dirname'], 'resources') !== false) {
            $this->options_cache_file = "../../options.txt";
        } else {
            $this->options_cache_file = "options.txt";
        }
        if($this->load_from_file) {
            if(file_exists($this->options_cache_file)/* && time()-@filemtime($this->options_cache_file) < 604800*/) {
                $this->options = json_decode(file_get_contents($this->options_cache_file), true);
            } else {
                $this->options = $db->select("SELECT * FROM `".DB_PREFIX."_options` WHERE `enabled` = 1");
                file_put_contents($this->options_cache_file, json_encode($this->options));
            }
        } else {
            $this->options = $db->select("SELECT * FROM `".DB_PREFIX."_options` WHERE `enabled` = 1");
        }
        if(empty($this->options)) header('Location: install/install.php');
    }

    public function get($name)
    {
        if(defined($name)) {
            return constant($name);
        } else {
            foreach($this->options as $option){
                if($name == $option["name"]) {
                    return empty($option["value"]) ? false : $option["value"];
                }
            }
            return false;
        }
    }
}

final class Role
{
    //https://github.com/delight-im/PHP-Auth/blob/master/src/Role.php
    const GUEST = \Delight\Auth\Role::AUTHOR;
    const BASIC_VIEWER = \Delight\Auth\Role::COLLABORATOR;
    const FULL_VIEWER = \Delight\Auth\Role::CONSULTANT;
    const EDITOR = \Delight\Auth\Role::CONSUMER;
    const SUPER_EDITOR = \Delight\Auth\Role::CONTRIBUTOR;
    const DEVELOPER = \Delight\Auth\Role::DEVELOPER;
    const TESTER = \Delight\Auth\Role::CREATOR;
    const EXTERNAL_VIEWER = \Delight\Auth\Role::REVIEWER;
    const ADMIN = \Delight\Auth\Role::ADMIN;
    const SUPER_ADMIN = \Delight\Auth\Role::SUPER_ADMIN;

    public function __construct()
    {
    }

}

class user
{
    private $db = null;
    private $tools = null;
    private $profile_names = null;
    public $auth = null;
    public $authenticated = false;
    public $holidays = null;

    public function __construct($db, $tools)
    {
        $this->db = $db;
        $this->tools = $tools;
        $this->auth = new \Delight\Auth\Auth($this->db, $tools->get_ip(), DB_PREFIX."_", false);
        \header_remove('X-Frame-Options');
        if(isset($_REQUEST["apiKey"]) && !is_null($_REQUEST["apiKey"])){
            //var_dump("SELECT * FROM \`".DB_PREFIX."_api_keys\` WHERE apikey = :apikey");
            //exit();
            $api_key_row = $this->db->select("SELECT * FROM `".DB_PREFIX."_api_keys` WHERE apikey = :apikey", [":apikey" => $_REQUEST["apiKey"]]);
            if(!empty($api_key_row)){
                $user = $this->db->select("SELECT * FROM `".DB_PREFIX."_profiles` WHERE id = :id", [":id" => $api_key_row[0]["user"]]);
                $user_id = $user[0]["id"];
                $this->auth->admin()->logInAsUserById($user_id);
                if(!empty($user)) {
                    if(is_null($user[0]["name"])) {
                        $_SESSION['_user_name'] = $this->auth->getUsername();
                    } else {
                        $_SESSION['_user_name'] = $user[0]["name"];
                    }
                    $_SESSION['_user_hidden'] = $user[0]["hidden"];
                    $_SESSION['_user_disabled'] = $user[0]["disabled"];
                    $_SESSION['_user_chief'] = $user[0]["chief"];
                    setcookie("authenticated", true);
                }
            }
        }
        $this->authenticated = $this->auth->isLoggedIn();
        $this->profile_names = $this->db->select("SELECT `id`, `name` FROM `".DB_PREFIX."_profiles`");
        $this->user_names = $this->db->select("SELECT `id`, `username` FROM `".DB_PREFIX."_users`");
        $this->holidays = Yasumi\Yasumi::create(get_option("holidays_provider") ?: "USA", date("Y"), get_option("holidays_language") ?: "en_US");
    }

    public function authenticated()
    {
        return $this->authenticated;
    }

    public function requirelogin($redirect=true)
    {
        $this->tools->profiler_start("Require login");
        if(!$this->authenticated()) {
            if($redirect) {
                $this->tools->redirect(get_option("web_url"));
            } else {
                exit();
            }
        }
        $this->tools->profiler_stop();
    }

    public function hasRole($role, $adminGranted=true)
    {
        return $this->auth->hasRole($role) || $adminGranted && $role !== Role::DEVELOPER && $this->auth->hasRole(Role::ADMIN) || $role !== Role::DEVELOPER && $this->auth->hasRole(Role::SUPER_ADMIN);
    }

    public function name($replace=false)
    {
        if(isset($_SESSION['_user_name'])) {
            $return_name = $_SESSION['_user_name'];
        } else {
            $check_name = $this->nameById($this->auth->getUserId());
            if($check_name) {
                $return_name = $check_name;
            } else {
                $return_name = "not authenticated";
            }
        }
        if($replace) {
            return str_replace(" ", "_", $return_name);
        } else {
            return $return_name;
        }
    }

    public function nameById($id)
    {
        $name = null;
        foreach($this->profile_names as $profile) {
            if($profile["id"] == $id && !is_null($profile["name"])) {
                $name = s($profile["name"], false);
            }
        }
        if(is_null($name)){
            foreach($this->user_names as $user) {
                if($user["id"] == $id){
                    return(s($user["username"], false));
                }
            }
            if(is_null($name)) return false;
        }
        return $name;
    }

    public function hidden($user = null)
    {
        if(is_null($user)){
            $user = $this->auth->getUserId();
        }
        $result = $this->db->select("SELECT `hidden` FROM `".DB_PREFIX."_profiles` WHERE id = :id", [":id" => $user]);
        if(isset($result[0]) && isset($result[0]["hidden"])){
            return boolval($result[0]["hidden"]);
        }
        return false;
    }

    public function available($id)
    {
        $user = $this->db->select("SELECT available FROM `".DB_PREFIX."_users` WHERE id = :id", [":id" => $id]);
        if(empty($user)) {
            return false;
        } else {
            return $user[0]["available"];
        }
    }

    public function info()
    {
        return array("autenticated" => $this->authenticated(), "id" => $this->auth->getUserId(), "name" => $this->name(), "full_viewer" => $this->hasRole(Role::FULL_VIEWER), "tester" => $this->hasRole(Role::TESTER), "developer" => $this->hasRole(Role::DEVELOPER));
    }

    public function login($name, $password, $remember_me)
    {
        $this->tools->profiler_start("Login");
        if(!empty($name)) {
            if(!empty($password)) {
                try {
                    if ($remember_me) {
                        // keep logged in for one year
                        $rememberDuration = (int) (60 * 60 * 24 * 365.25);
                    } else {
                        // do not keep logged in after session ends
                        $rememberDuration = null;
                    }
                    $this->auth->loginWithUsername($name, $password, $rememberDuration);
                }
                catch (\Delight\Auth\InvalidEmailException $e) {
                    $this->tools->profiler_stop();
                    return ["status" => "error", "code" => 010, "text" => "Wrong email address"];
                }
                catch (\Delight\Auth\InvalidPasswordException $e) {
                    $this->tools->profiler_stop();
                    return ["status" => "error", "code" => 011, "text" => "Wrong password"];
                }
                catch (\Delight\Auth\EmailNotVerifiedException $e) {
                    $this->tools->profiler_stop();
                    return ["status" => "error", "code" => 012, "text" => "Email not verified"];
                }
                catch (\Delight\Auth\UnknownUsernameException $e) {
                    $this->tools->profiler_stop();
                    return ["status" => "error", "code" => 013, "text" => "Wrong username"];
                }
                catch (\Delight\Auth\TooManyRequestsException $e) {
                    $this->tools->profiler_stop();
                    return ["status" => "error", "code" => 020, "text" => "Too many requests"];
                }
                if($this->auth->isLoggedIn()) {
                    $this->log("Login", $this->auth->getUserId());
                    $user = $this->db->select("SELECT * FROM `".DB_PREFIX."_profiles` WHERE id = :id", [":id" => $this->auth->getUserId()]);
                    if(!empty($user)) {
                        if(is_null($user[0]["name"])) {
                                $_SESSION['_user_name'] = $this->auth->getUsername();
                        } else {
                              $_SESSION['_user_name'] = $user[0]["name"];
                        }
                        $_SESSION['_user_hidden'] = $user[0]["hidden"];
                        $_SESSION['_user_disabled'] = $user[0]["disabled"];
                        $_SESSION['_user_chief'] = $user[0]["chief"];
                        $this->tools->profiler_stop();
                        setcookie("authenticated", true);
                        return true;
                    }
                }
            } else {
                $this->tools->profiler_stop();
                return ["status" => "error", "code" => 002];
            }
        } else {
            $this->tools->profiler_stop();
            return ["status" => "error", "code" => 001];
        }
    }
    public function log($action, $changed=null, $editor=null, $timestamp=null)
    {
        $this->tools->profiler_start("Log");
        if(is_null($timestamp)){
            $date = new Datetime('now');
            $timestamp = $date->format('Y-m-d H:i:s');
        }
        if(is_null($changed)){
            $changed = $this->auth->getUserId();
        }
        if(is_null($editor)){
            $editor = $changed;
        }
        if(!$this->hidden($editor)){
            if(get_option("log_save_ip")){
                $ip = $this->tools->get_ip();
            } else {
                $ip = null;
            }
            $source_type = defined("REQUEST_USING_API") ? "api" : "web";
            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? mb_strimwidth($_SERVER['HTTP_USER_AGENT'], 0, 200, "...") : null;
            $this->db->insert(
                DB_PREFIX."_log",
                ["action" => $action, "changed" => $changed, "editor" => $editor, "timestamp" => $timestamp, "ip" => $ip, "source_type" => $source_type, "user_agent" => $user_agent]
            );
        }
        $this->tools->profiler_stop();
    }

    public function logout()
    {
        try {
            $this->log("Logout");
            $this->auth->logOut();
            $this->auth->destroySession();
            setcookie("authenticated", false, time() - 3600);
        }
        catch (\Delight\Auth\NotLoggedInException $e) {
            die('Not logged in');
        }
    }

    public function add_user($email, $name, $username, $password, $phone_number, $birthday, $chief, $driver, $hidden, $disabled, $inserted_by)
    {
        //TODO: save birthday in db
        bdump($birthday);
        $this->tools->profiler_start("Add user");
        $userId = $this->auth->admin()->createUserWithUniqueUsername($email, $password, $username);
        if($userId) {
            $hidden = $hidden ? 1 : 0;
            $disabled = $disabled ? 1 : 0;
            $chief = $chief ? 1 : 0;
            $driver = $driver ? 1 : 0;
            $this->db->insert(
                DB_PREFIX."_profiles",
                ["hidden" => $hidden, "disabled" => $disabled, "name" => $name, "phone_number" => $phone_number, "chief" => $chief, "driver" => $driver]
            );
            if($chief == 1) {
                $this->auth->admin()->addRoleForUserById($userId, Role::FULL_VIEWER);
            }
            $this->log("User added", $userId, $inserted_by);
            $this->tools->profiler_stop();
            return $userId;
        } else {
            $this->tools->profiler_stop();
            return false;
        }
    }

    public function remove_user($id, $removed_by)
    {
        $this->tools->profiler_start("Remove user");
        $this->db->delete(
            DB_PREFIX."_users",
            ["id" => $id]
        );
        $this->db->delete(
            DB_PREFIX."_profiles",
            ["id" => $id]
        );
        $this->log("User removed", null, $removed_by);
        $this->tools->profiler_stop();
    }

    public function online_time_update($id=null){
        $this->tools->profiler_start("Update online timestamp");
        if(is_null($id)) $id = $this->auth->getUserId();
        $time = time();
        $this->db->update(
            DB_PREFIX."_profiles",
            ["online_time" => $time],
            ["id" => $id]
        );
        bdump(["id" => $id, "time" => $time]);
        $this->tools->profiler_stop();
    }
}

class crud
{
    public $tools = null;
    public $db = null;
    public $user = null;

    public function __construct($tools, $db, $user)
    {
        $this->tools = $tools;
        $this->db = $db;
        $this->user = $user;
    }

    public function increment_services($increment)
    {
        bdump($increment);
        $this->db->exec(
            "UPDATE `".DB_PREFIX."_profiles` SET `services`= services + 1 WHERE id IN ($increment)"
        );
    }

    public function getIncrement_services($id)
    {
        bdump($id);
        $increment = $this->db->selectValue(
            "SELECT `increment` FROM `".DB_PREFIX."_services` WHERE `id` = :id LIMIT 0, 1",
            ["id" => $id]
        );
        bdump($increment);
        return $increment;
    }

    public function decrease_services($id)
    {
        $increment = $this->getIncrement_services($id);
        $this->db->exec(
            "UPDATE `".DB_PREFIX."_profiles` SET `services`= services - 1 WHERE id IN ($increment)"
        );
    }

    public function increment_trainings($increment)
    {
        bdump($increment);
        $this->db->exec(
            "UPDATE `".DB_PREFIX."_profiles` SET `trainings`= trainings + 1 WHERE id IN ($increment)"
        );
    }

    public function getIncrement_trainings($id)
    {
        bdump($id);
        $increment = $this->db->selectValue(
            "SELECT `increment` FROM `".DB_PREFIX."_trainings` WHERE `id` = :id LIMIT 0, 1",
            ["id" => $id]
        );
        bdump($increment);
        return $increment;
    }

    public function decrease_trainings($id)
    {
        $increment = $this->getIncrement_trainings($id);
        $this->db->exec(
            "UPDATE `".DB_PREFIX."_profiles` SET `trainings`= trainings - 1 WHERE id IN ($increment)"
        );
    }

    public function add_service($date, $code, $beginning, $end, $chief, $drivers, $crew, $place, $notes, $type, $increment, $inserted_by)
    {
        $drivers = implode(",", $drivers);
        bdump($drivers);
        $crew = implode(",", $crew);
        bdump($crew);
        $increment = implode(",", $increment);
        bdump($increment);
        $date = date('Y-m-d H:i:s', strtotime($date));
        $this->db->insert(
            DB_PREFIX."_services",
            ["date" => $date, "code" => $code, "beginning" => $beginning, "end" => $end, "chief" => $chief, "drivers" => $drivers, "crew" => $crew, "place" => $place, "place_reverse" => $this->tools->savePlaceReverse($place), "notes" => $notes, "type" => $type, "increment" => $increment, "inserted_by" => $inserted_by]
        );
        $this->increment_services($increment);
        $this->user->log("Service added");
    }

    public function remove_service($id)
    {
        $this->decrease_services($id);
        $this->db->delete(
            DB_PREFIX."_services",
            ["id" => $id]
        );
        $this->user->log("Service removed");
    }


    public function edit_service($id, $date, $code, $beginning, $end, $chief, $drivers, $crew, $place, $notes, $type, $increment, $inserted_by)
    {
        $this->remove_service($id);
        $this->add_service($date, $code, $beginning, $end, $chief, $drivers, $crew, $place, $notes, $type, $increment, $inserted_by);
        $this->user->log("Service edited");
    }

    public function add_training($date, $name, $start_time, $end_time, $chief, $crew, $place, $notes, $increment, $inserted_by)
    {
        $crew = implode(",", $crew);
        bdump($crew);
        $increment = implode(",", $increment);
        bdump($increment);
        $date = date('Y-m-d H:i:s', strtotime($date));
        $this->db->insert(
            DB_PREFIX."_trainings",
            ["date" => $date, "name" => $name, "beginning" => $start_time, "end" => $end_time, "chief" => $chief, "crew" => $crew, "place" => $place, "place_reverse" => $this->tools->savePlaceReverse($place), "notes" => $notes, "increment" => $increment, "inserted_by" => $inserted_by]
        );
        $this->increment_trainings($increment);
        $this->user->log("Training added");
    }

    public function remove_training($id)
    {
        $this->decrease_trainings($id);
        bdump($id);
        $this->db->delete(
            DB_PREFIX."_trainings",
            ["id" => $id]
        );
        $this->user->log("Training removed");
    }


    public function edit_training($id, $date, $name, $start_time, $end_time, $chief, $crew, $place, $notes, $increment, $inserted_by)
    {
        $this->remove_training($id);
        $this->add_training($date, $name, $start_time, $end_time, $chief, $crew, $place, $notes, $increment, $inserted_by);
        $this->user->log("Training edited");
    }

    public function exists($table, $id)
    {
        $result = $this->db->select("SELECT id FROM `".DB_PREFIX."_{$table}` WHERE id = :id", [":id" => $id]);
        return !empty($result);
    }
}

class translations
{
    public $loaded_languages = ["en", "it"];
    public $default_language = "en";
    public $language = null;
    public $client_languages = ["en"];
    public $loaded_translations = [];
    public $filename = "";

    public function client_languages()
    {
        if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $client_languages = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        } else {
            $client_languages = "en-US;q=0.5,en;q=0.3";
        }
        if(strpos($client_languages, ';') == false) {
            if(strpos($client_languages, '-') !== false) {
                return [substr($client_languages, 0, 5)];
            } else {
                return [substr($client_languages, 0, 2)];
            }
        } else {
            $client_languages = explode(",", $client_languages);
            $tmp_languages = [];
            foreach($client_languages as $language){
                if(strpos($language, ';') == false) {
                    $tmp_languages[$language] = 1;
                } else {
                    $tmp_languages[explode(";q=", $language)[0]] = (float) explode(";q=", $language)[1];
                }
            }
            arsort($tmp_languages);
            return array_keys($tmp_languages);
        }
    }

    public function __construct($force_language)
    {
        $this->client_languages = $this->client_languages();
        if(isset($_COOKIE["forceLanguage"]) && in_array($_COOKIE["forceLanguage"], $this->loaded_languages)){
            $this->language = $_COOKIE["forceLanguage"];
        } else if($force_language && in_array($force_language, $this->loaded_languages)){
            $this->language = $force_language;
        } else {
            foreach($this->client_languages as $language){
                if(in_array($language, $this->loaded_languages) && $this->language == null) {
                    $this->language = $language;
                }
            }
            if($this->language == null) {
                $this->language = "en";
            }
        }
        $file_infos = pathinfo(array_reverse(debug_backtrace())[0]['file']);
        if(strpos($file_infos['dirname'], 'resources') !== false) {
            $this->filename = "../../translations/".$this->language."/".$file_infos['basename'];
        } else {
            $this->filename = "translations/".$this->language."/".$file_infos['basename'];
        }
        if (file_exists($this->filename)) {
            $this->loaded_translations = array_merge(include "translations/".$this->language."/base.php", include $this->filename);
        } else {
            try{
                $this->loaded_translations = include "translations/".$this->language."/base.php";
            } catch(Exception $e) {
                $this->loaded_translations = include "../../translations/".$this->language."/base.php";
            }
        }
    }

    public function translate($string)
    {
        bdump($string);
        try {
            if (!array_key_exists($string, $this->loaded_translations)) {
                throw new Exception('string does not exist');
            }
            return $this->loaded_translations[$string];
        } catch (\Exception $e) {
            bdump($this->filename);
            bdump($e, $string);
            return $string;
        }
    }
}

function init_db(){
    global $db;

    $dataSource = new \Delight\Db\PdoDataSource('mysql');
    $dataSource->setHostname(DB_HOST);
    $dataSource->setPort(3306);
    $dataSource->setDatabaseName(DB_NAME);
    $dataSource->setCharset('utf8mb4');
    $dataSource->setUsername(DB_USER);
    $dataSource->setPassword(DB_PASSWORD);
    $db = \Delight\Db\PdoDatabase::fromDataSource($dataSource);
}

$webpack_manifest_path = realpath("resources/dist/assets-manifest.json");
function init_class($enableDebugger=true, $headers=true)
{
    global $tools, $options, $db, $user, $crud, $translations, $debugbar;
    init_db();
    $options = new options($db);
    $tools = new tools($db, $enableDebugger);
    $user = new user($db, $tools);
    $crud = new crud($tools, $db, $user);
    $translations = new translations(get_option("force_language"));

    if($headers) {
        //TODO adding require-trusted-types-for 'script';
        $script_nonce_csp = defined("UI_MODE") ? "'nonce-{$tools->script_nonce}' " : "";
        $csp_rules = [
            "default-src 'self' data: *.tile.openstreetmap.org nominatim.openstreetmap.org",
            "connect-src 'self' *.sentry.io nominatim.openstreetmap.org",
            "script-src {$script_nonce_csp}'self' 'unsafe-eval'",
            "img-src 'self' data: *.tile.openstreetmap.org",
            "object-src 'self'",
            "style-src 'self' 'unsafe-inline'",
            "base-uri 'self'"
        ];
        if(defined(SENTRY_CSP_REPORT_URI) && SENTRY_CSP_REPORT_URI !== false){
            $csp_rules[] = "report-uri ".SENTRY_CSP_REPORT_URI;
        }
        $csp = implode("; ", $csp_rules);
        if(!isset($_COOKIE["JSless"]) && (isset($_GET["JSless"]) ? !$_GET["JSless"] : true)){
            header("Content-Security-Policy: $csp");
            header("X-XSS-Protection: 1; mode=block");
            header("X-Content-Type-Options: nosniff");
            header("Permissions-Policy: interest-cohort=(), camera=(), microphone=(), payment=(), usb=()");
            header("Referrer-Policy: no-referrer");
            header("X-Frame-Options: DENY");
        }
    }

    if(SENTRY_ENABLED){
        Sentry\configureScope(function (Sentry\State\Scope $scope): void {
            global $user, $translations;
            if($user->authenticated()){
                $id = $user->auth->getUserId();
                $username = $user->nameById($id);
                if($username !== false){
                    $scope->setUser([
                        'id' => $id,
                        'username' => $username
                    ]);
                }
            }
            $scope->setTag('page.locale', $translations->client_languages[0]);
        });
    } else {
        //TODO: add Monolog here
    }

    if($enableDebugger && $user->hasRole(Role::DEVELOPER)) {
        $debugbar = new StandardDebugBar();
        bdump(__DIR__);
        $dir = str_replace("resources\ajax\\", "", __DIR__).DIRECTORY_SEPARATOR.'debug_storage';
        $debugbar->setStorage(new DebugBar\Storage\FileStorage($dir));
        //TODO: debug PDO
        //$debugbar->addCollector(new DebugBar\DataCollector\PDO\PDOCollector($database->connection));
        $debugbar->addCollector(new DebugBar\DataCollector\ConfigCollector($options->options));
    } else {
        $debugbar = null;
    }

    function customErrorPage() {
        global $webpack_manifest_path;
        $error = error_get_last();
        if ($error) {
            bdump($webpack_manifest_path);
            require("error_page.php");
            show_error_page(500);
        }
    }
    register_shutdown_function('customErrorPage');

    if(isset($_GET["disableSW"])){
        setcookie("disableServiceWorkerInstallation", true);
        $tools->redirect("?");
    } else if(isset($_GET["enableSW"])){
        setcookie("disableServiceWorkerInstallation", false, time() - 3600);
        setcookie("forceServiceWorkerInstallation", false, time() - 3600);
        $tools->redirect("?");
    } else if(isset($_GET["forceSW"])){
        setcookie("disableServiceWorkerInstallation", false, time() - 3600);
        setcookie("forceServiceWorkerInstallation", true);
        $tools->redirect("?");
    }
}

function t($string, $echo=true)
{
    global $translations;
    if($echo) {
        echo $translations->translate($string);
    } else {
        return $translations->translate($string);
    }
}

function s($string, $echo=true, $htmlAllowed=false, $htmlPurifierOptions=[])
{
    global $tools;
    if($echo) {
        echo $tools->sanitize($string, $htmlAllowed, $htmlPurifierOptions);
    } else {
        return $tools->sanitize($string, $htmlAllowed, $htmlPurifierOptions);
    }
}

function get_option($option){
    global $options;
    return $options->get($option);
}

function p_start($name=null)
{
    global $tools;
    $tools->profiler_start($name);
}

function p_stop($name=null)
{
    global $tools;
    $tools->profiler_stop($name);
}
