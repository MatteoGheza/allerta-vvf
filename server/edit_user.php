<?php
require_once 'ui.php';
use Brick\PhoneNumber\PhoneNumber;
use Brick\PhoneNumber\PhoneNumberFormat;
use Brick\PhoneNumber\PhoneNumberParseException;
function debug(){
    echo("<pre>"); var_dump($_POST); echo("</pre>"); exit();
}
if($tools->validate_form("mod", "add")) {
    if($tools->validate_form(['mail', 'name', 'username', 'password', 'phone_number', 'birthday', 'token'])) {
        if($_POST["token"] == $_SESSION['token']) {
            bdump("adding user");
            bdump($_POST);
            $chief = isset($_POST["chief"]) ? 1 : 0;
            $driver = isset($_POST["driver"]) ? 1 : 0;
            $hidden = isset($_POST["visible"]) ? 0 : 1;
            $disabled = isset($_POST["enabled"]) ? 0 : 1;
            try {
                $phone_number = PhoneNumber::parse($_POST["phone_number"]);
                if (!$phone_number->isValidNumber()) {
                    echo("Bad phone number. <a href='javascript:window.history.back()'>Go back</a>"); //TODO: better form validation
                    exit();
                } else {
                    $phone_number = $phone_number->format(PhoneNumberFormat::E164);
                }
            } catch (PhoneNumberParseException $e) {
                echo("Bad phone number. <a href='javascript:window.history.back()'>Go back</a>"); //TODO: better form validation
                exit();
            }
            $user->add_user($_POST["mail"], $_POST["name"], $_POST["username"], $_POST["password"], $phone_number, $_POST["birthday"], $chief, $driver, $hidden, $disabled, $user->name());
            $tools->redirect("list");
        } else {
            debug();
        }
    } else {
        debug();
    }
/*} elseif($tools->validate_form("mod", "edit")) {
    if($tools->validate_form(['mail', 'name', 'username', 'password', 'birthday', 'token'])) {
    if($_POST["token"] == $_SESSION['token']){
      bdump($_POST);
      bdump("editing service");
      $crud->edit_service($_POST["id"], $_POST["date"], $_POST["code"], $_POST["beginning"], $_POST["end"], $_POST["chief"], $tools->extract_unique($_POST["drivers"]), $tools->extract_unique($_POST["crew"]), $_POST["place"], $_POST["notes"], $_POST["type"], $tools->extract_unique([$_POST["chief"],$_POST["drivers"],$_POST["crew"]]), $user->name());
      $tools->redirect("services");
    } else {
      $tools->redirect("accessdenied");
    }
    }
    */
} elseif($tools->validate_form("mod", "delete")) {
    bdump("removing service");
    if($tools->validate_form(['id', 'token'])) {
        if($_POST["token"] == $_SESSION['token']) {
            bdump("removing user");
            $user->remove_user($_POST["id"]);
            $tools->redirect("list");
        } else {
            debug();
        }
    } else {
        debug();
    }
} else {
    if(isset($_GET["add"])||isset($_GET["edit"])||isset($_GET["delete"])||isset($_GET["mod"])) {
        $_SESSION["token"] = bin2hex(random_bytes(64));
    }
        $modalità = (isset($_GET["add"])) ? "add" : ((isset($_GET["edit"])) ? "edit" : ((isset($_GET["delete"])) ? "delete" : "add"));
        bdump($modalità, "modalità");
        $id = "";
    if(isset($_GET["id"])) {
        $id = $_GET["id"];
        bdump($crud->exists("profiles", $id));
        $values = $db->select("SELECT * FROM `".DB_PREFIX."_profiles` WHERE `id` = :id", [":id" => $id])[0];
        bdump($values);
    } else {
        $values = [];
    }
    if($modalità=="edit" || $modalità=="delete") {
        if(empty($id)) {
            $tools->redirect("accessdenied");
        } elseif (!$crud->exists("profiles", $id)) {
            $tools->redirect("accessdenied");
        }
    }
        loadtemplate('edit_user.html', ['id' => $id, 'token' => $_SESSION["token"], 'modalità' => $modalità, 'values' => $values, 'title' => t(ucfirst($modalità) . " user", false)]);
        bdump($_SESSION['token'], "token");
}
?>