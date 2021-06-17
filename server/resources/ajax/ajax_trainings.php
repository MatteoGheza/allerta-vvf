<?php
include_once("../../core.php");
init_class();
$user->requirelogin(false);
$user->online_time_update();

$result = $db->select("SELECT * FROM `".DB_PREFIX."_trainings` ORDER BY date DESC, beginning desc");

$response = [];
foreach(!is_null($result) ? $result : [] as $row){
  $chief = $user->nameById($row["chief"]);

  $others_crew_array = explode(",", $row['crew']);
  foreach($others_crew_array as $key=>$name){
    $others_crew_array[$key] = $user->nameById($name);
  }
  $others_crew = implode(", ", $others_crew_array);
  $response[] = [
    $row['date'],
    $row['name'],
    $row['beginning'],
    $row['end'],
    $chief,
    $others_crew,
    s($row['place'],false,true),
    s($row['notes'],false,true),
    get_option("training_edit") ? "<a class='pjax_disable' data-action='edit' href='edit_training?edit&id={$row['id']}'><i style='font-size: 40px' class='fa fa-edit'></i></a>" : null,
    get_option("training_remove") ? "<a class='pjax_disable' data-action='delete' href='edit_training?delete&id={$row['id']}'><i style='font-size: 40px' class='fa fa-trash'></i></a>" : null
  ];
}
$tools->ajax_page_response($response);
?>