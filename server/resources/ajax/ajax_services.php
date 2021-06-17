<?php
include_once("../../core.php");
init_class();
$user->requirelogin(false);
$user->online_time_update();

$result = $db->select("SELECT * FROM `".DB_PREFIX."_services` ORDER BY date DESC, beginning DESC");

$response = [];
foreach(!is_null($result) ? $result : [] as $row){
  $chief = $user->nameById($row["chief"]);

  $drivers_array = explode(",", $row['drivers']);
  foreach($drivers_array as $key=>$name){
    $drivers_array[$key] = $user->nameById($name);
  }
  $drivers = implode(", ", $drivers_array);

  $others_crew_array = explode(",", $row['crew']);
  foreach($others_crew_array as $key=>$name){
    $others_crew_array[$key] = $user->nameById($name);
  }
  $others_crew = implode(", ", $others_crew_array);
  $response[] = [
    $row['date'],
    $row['code'],
    $row['beginning'],
    $row['end'],
    $chief,
    $drivers,
    $others_crew,
    s($row['place'],false,true),
    s($row['notes'],false,true),
    s($row['type'],false,true),
    get_option("service_edit") ? "<a class='pjax_disable' data-action='edit' href='edit_service?edit&id={$row['id']}'><i style='font-size: 40px' class='fa fa-edit'></i></a>" : null,
    get_option("service_remove") ? "<a class='pjax_disable' data-action='delete' href='edit_service?delete&id={$row['id']}'><i style='font-size: 40px' class='fa fa-trash'></i></a>" : null
  ];
}
$tools->ajax_page_response($response);
?>