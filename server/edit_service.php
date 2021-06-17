<?php
require_once 'ui.php';
function debug(){
    echo("<pre>"); var_dump($_POST); echo("</pre>"); exit();
}
if($tools->validate_form("mod", "add")) {
    if($tools->validate_form(['date', 'code', 'beginning', 'end', 'place', 'notes', 'type', 'token'])) {
        if($_POST["token"] == $_SESSION['token']) {
            bdump("adding service");
            $place = $tools->checkPlaceParam($_POST["place"]);
            $crud->add_service($_POST["date"], $_POST["code"], $_POST["beginning"], $_POST["end"], $_POST["chief"][0], $tools->extract_unique($_POST["drivers"]), $tools->extract_unique($_POST["crew"]), $place, $_POST["notes"], $_POST["type"], $tools->extract_unique([$_POST["chief"],$_POST["drivers"],$_POST["crew"]]), $user->name());
            $tools->redirect("services");
        } else {
            debug(); //TODO: remove debug info
        }
    } else {
        debug();
    }
} elseif($tools->validate_form("mod", "edit")) {
    if($tools->validate_form(['id', 'date', 'code', 'beginning', 'end', 'place', 'notes', 'type', 'token'])) {
        if($_POST["token"] == $_SESSION['token']) {
            bdump($_POST);
            bdump("editing service");
            $place = $tools->checkPlaceParam($_POST["place"]);
            $crud->edit_service($_POST["id"], $_POST["date"], $_POST["code"], $_POST["beginning"], $_POST["end"], $_POST["chief"][0], $tools->extract_unique($_POST["drivers"]), $tools->extract_unique($_POST["crew"]), $place, $_POST["notes"], $_POST["type"], $tools->extract_unique([$_POST["chief"],$_POST["drivers"],$_POST["crew"]]), $user->name());
            $tools->redirect("services");
        } else {
            debug();
        }
    } else {
        debug();
    }
} elseif($tools->validate_form("mod", "delete")) {
    bdump("removing service");
    if($tools->validate_form(['id', 'token'])) {
        if($_POST["token"] == $_SESSION['token']) {
            bdump("removing service");
            $crud->remove_service($_POST["id"]);
            $tools->redirect("services");
        } else {
            echo("1");
            debug();
        }
    } else {
        echo("2");
        debug();
    }
} else {
    if(isset($_GET["add"])||isset($_GET["edit"])||isset($_GET["delete"])||isset($_GET["mod"])) {
        $_SESSION["token"] = bin2hex(random_bytes(64));
    }
    $crew = $db->select("SELECT * FROM `".DB_PREFIX."_profiles` ORDER BY name ASC");
    $types = $db->select("SELECT `name` FROM `".DB_PREFIX."_type` ORDER BY name ASC");
    $modalità = (isset($_GET["add"])) ? "add" : ((isset($_GET["edit"])) ? "edit" : ((isset($_GET["delete"])) ? "delete" : "add"));
    bdump($modalità, "modalità");
    bdump($types, "types");
    bdump($crew, "crew");
    $id = "";
    if(isset($_GET["id"])) {
        $id = $_GET["id"];
        bdump($crud->exists("services", $id));
        $values = $db->select("SELECT * FROM `".DB_PREFIX."_services` WHERE `id` = :id", [":id" => $id])[0];
        bdump($values);
    } else {
        $values = [];
    }
    if($modalità=="edit" || $modalità=="delete") {
        if(empty($id)) {
            echo("<pre>"); var_dump($_POST); echo("</pre>");
        } elseif (!$crud->exists("services", $id)) {
            echo("<pre>"); var_dump($_POST); echo("</pre>");
        }
    }
    loadtemplate('edit_service.html', ['service' => ['id' => $id, 'token' => $_SESSION['token'], 'modalità' => $modalità, 'crew' => $crew, 'types' => $types], 'values' => $values, 'title' => t(ucfirst($modalità) . " service", false)]);
    bdump($_SESSION['token'], "token");
}
?>
