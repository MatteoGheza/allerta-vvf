<?php
include_once("../../core.php");
init_class();
$user->requirelogin(false);
$user->online_time_update();

$result = $db->select("SELECT * FROM `".DB_PREFIX."_profiles` ORDER BY available DESC, chief DESC, services ASC, availability_minutes ASC, name ASC");

$response = [];
foreach(!is_null($result) ? $result : [] as $row){
  if(!$user->hidden($row["id"])){
    if($user->hasRole(Role::FULL_VIEWER)){
      $name = $user->nameById($row["id"]);
      $name_encoded = urlencode($user->name());
      $helmet_colour = $row["chief"] ? "red" : "black";
      $firstCellName = (time()-$row["online_time"])<=30 ? "<u>".$name."</u>" : $name;
      $firstCell = "<a data-clickable data-user='{$row['id']}' data-user-available='{$row['available']}' style='text-align: left;'><img alt='{$helmet_colour} helmet' src='./resources/images/{$helmet_colour}_helmet.png' width='20px'>$firstCellName</a>";
      $secondCell = $row["available"] ? "<a data-clickable><i class='fa fa-check' style='color:green'></i></a>" : "<a data-clickable><i class='fa fa-times' style='color:red'></i></a>";
      $response[] = [
        $firstCell,
        $secondCell,
        $row['driver'] ? "<img alt='driver' src='./resources/images/wheel.png' width='20px'>" : "",
        !empty($row['phone_number']) ? "<a href='tel:".$row['phone_number']."'><i class='fa fa-phone'></i></a>" : "",
        !empty($row['phone_number']) ? "<a href='https://api.whatsapp.com/send?phone=".$row['phone_number']."&text=ALLERTA IN CORSO.%20Mettiti%20in%20contatto%20con%20$name_encoded'><i class='fa fa-whatsapp' style='color:green'></i></a>" : "",
        $row['services'],
        $row['availability_minutes'],
        //"<a href='user_details?user=".$row['id']."'><p>".t("Altri dettagli", false)."</p></a>" TODO: fix "Other" page
      ];
    } else {
      $name = $user->nameById($row["id"]);
      $helmet_colour = $row["chief"] ? "red" : "black";
      $firstCellName = (time()-$row["online_time"])<=30 ? "<u>".$name."</u>" : $name;
      $firstCell = "<a style='text-align: left;'><img alt='{$helmet_colour} helmet' src='./resources/images/{$helmet_colour}_helmet.png' width='20px'>$firstCellName</a>";
      $secondCell = $row["available"] ? "<a><i class='fa fa-check' style='color:green'></i></a>" : "<a><i class='fa fa-times' style='color:red'></i></a>";
      $response[] = [
        $firstCell,
        $secondCell
      ];
    }
  }
}
$tools->ajax_page_response($response);
?>
