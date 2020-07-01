<?php
require_once 'ui.php';
if($tools->validate_form_data('$post-mod', true, "add")) {
  if($tools->validate_form_data(['$post-data', '$post-codice', '$post-uscita', '$post-rientro', '$post-capo', '$post-luogo', '$post-note', '$post-tipo', '$post-token'])) {
    if($_POST["token"] == $_SESSION['token']){
      bdump("aggiungo intervento");
      $database->add_intervento($_POST["data"], $_POST["codice"], $_POST["uscita"], $_POST["rientro"], $_POST["capo"], $tools->extract_unique($_POST["autisti"]), $tools->extract_unique($_POST["personale"]), $_POST["luogo"], $_POST["note"], $_POST["tipo"], $tools->extract_unique([$_POST["capo"],$_POST["autisti"],$_POST["personale"]]), $user->name());
      $tools->redirect("interventi.php");
    } else {
      $tools->redirect("nonfareilfurbo.php");
    }
  }
} elseif($tools->validate_form_data('$post-mod', true, "modifica")) {
  if($tools->validate_form_data(['$post-id', '$post-data', '$post-codice', '$post-uscita', '$post-rientro', '$post-capo', '$post-luogo', '$post-note', '$post-tipo', '$post-token'])) {
    if($_POST["token"] == $_SESSION['token']){
      bdump($_POST);
      bdump("modifico intervento");
      $database->change_intervento($_POST["id"], $_POST["data"], $_POST["codice"], $_POST["uscita"], $_POST["rientro"], $_POST["capo"], $tools->extract_unique($_POST["autisti"]), $tools->extract_unique($_POST["personale"]), $_POST["luogo"], $_POST["note"], $_POST["tipo"], $tools->extract_unique([$_POST["capo"],$_POST["autisti"],$_POST["personale"]]), $user->name());
      $tools->redirect("interventi.php");
    } else {
      $tools->redirect("nonfareilfurbo.php");
    }
  }
} elseif($tools->validate_form_data('$post-mod', true, "elimina")) {
  bdump("rimuovo intervento");
  if($tools->validate_form_data(['$post-id', '$post-incrementa', '$post-token'])) {
    if($_POST["token"] == $_SESSION['token']){
      bdump("rimuovo intervento");
      $database->remove_intervento($_POST["id"], $_POST["incrementa"]);
      $tools->redirect("interventi.php");
    } else {
      $tools->redirect("nonfareilfurbo.php");
    }
  }
} else {
  if(isset($_GET["mod"])){
    $_SESSION["token"] = bin2hex(random_bytes(64));
  }
  $personale = $database->exec("SELECT * FROM `%PREFIX%_profiles` ORDER BY name ASC;", true); // Pesco i dati della table e li ordino in base al name
  $tipologie = $database->exec("SELECT `name` FROM `%PREFIX%_tipo` ORDER BY name ASC", true); // Pesco le tipologie della table
  $modalità = (isset($_GET["add"])) ? "add" : ((isset($_GET["modifica"])) ? "modifica" : ((isset($_GET["elimina"])) ? "elimina" : "add"));
  bdump($modalità, "modalità");
  bdump($tipologie, "tipologie");
  bdump($personale, "personale");
  $id = "";
  if(isset($_GET["id"])){
      $id = $_GET["id"];
      bdump($database->exists("interventi", $id));
      $values = $database->exec("SELECT * FROM `%PREFIX%_interventi` WHERE `id` = :id", true, [":id" => $id])[0]; // Pesco le tipologie della table
      bdump($values);
  } else {
      $values = [];
  }
  if(isset($_GET["incrementa"])){
      $incrementa = $_GET["incrementa"];
  } else {
      $incrementa = "";
  }
  if($modalità=="modifica" || $modalità=="elimina"){
      if(empty($id)){
          $tools->redirect("nonfareilfurbo.php");
      } elseif (!$database->exists("interventi", $id)){
          $tools->redirect("nonfareilfurbo.php");
      }
  }
  loadtemplate('modifica_intervento.html', ['intervento' => ['id' => $id, 'token' => $_SESSION['token'], 'modalità' => $modalità, 'personale' => $personale, 'tipologie' => $tipologie], 'values' => $values, 'incrementa' => $incrementa, 'titolo' => ucfirst($modalità) . ' intervento']);
  bdump($_SESSION['token'], "token");
}
?>