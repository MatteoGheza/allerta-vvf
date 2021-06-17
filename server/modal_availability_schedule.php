<?php
require("core.php");
init_class(false);
$user->requirelogin(false);

// landscape means horizontal
// portrait  means vertical
$orienation = isset($_POST["orientation"]) ? $_POST["orientation"] : "landscape";
echo("<!-- orientation: ".$orienation." !-->");
$hours = [
    "0:00", "0:30",
    "1:00", "1:30",
    "2:00", "2:30",
    "3:00", "3:30",
    "4:00", "4:30",
    "5:00", "5:30",
    "6:00", "6:30",
    "7:00", "7:30",
    "8:00", "8:30",
    "9:00", "9:30",
    "10:00", "10:30",
    "11:00", "11:30",
    "12:00", "12:30",
    "13:00", "13:30",
    "14:00", "14:30",
    "15:00", "15:30",
    "16:00", "16:30",
    "17:00", "17:30",
    "18:00", "18:30",
    "19:00", "19:30",
    "20:00", "20:30",
    "21:00", "21:30",
    "22:00", "22:30",
    "23:00", "23:30",
];
$days = [
    t("Mon",false),
    t("Tue",false),
    t("Wed",false),
    t("Thu",false),
    t("Fri",false),
    t("Sat",false),
    t("Sun",false)
];

$nonce = isset($_GET["nonce"]) ? $_GET["nonce"] : "";

$user_id = $user->auth->getUserId();
$result = $db->select("SELECT * FROM `".DB_PREFIX."_schedules` WHERE `user`={$user_id}");
if(!empty($result)){
    $old_schedules_db = json_decode($result[0]["schedules"]);
    foreach ($old_schedules_db as $schedule) {
        $hour = $schedule[1];
        $hour = $hour[0] == "0" ? substr($hour,1) : $hour;
        $old_schedules[$schedule[0]][$hour] = true;
    }
    $old_holidays = json_decode($result[0]["holidays"]);
    if(is_null($old_holidays)){
        $old_holidays = [];
    }
} else {
    $old_schedules = [];
    $old_holidays = [];
}
?>
<style>
.hour-cell {
    width: 100px;
    height: 100px;
    text-align: center;
    vertical-align: middle;
    background-color: #ccc;
    border: 1px solid #fff;
}

.hour-cell.highlighted {
    background-color: #999;
}
<?php
if($orienation == "landscape"):
?>
#scheduler_body td {
    min-width: 40px;
}
<?php
endif;
?>
</style>
<table cellpadding="0" cellspacing="0" id="scheduler_table">
    <thead>
        <tr>
            <td style="background-color: white;"></td>
            <?php
            if($orienation == "portrait") {
                for($i=0;$i<7;$i++){
                    echo "<td id='$i' class='day'>{$days[$i]}</td>";
                }
            } else if($orienation == "landscape") {
                foreach($hours as $hour) {
                    $hour_replaced = str_replace(":", "-", $hour);
                    echo "<td id='{$hour_replaced}' class='hour'>$hour</td>";
                }
            }
            ?>

        </tr>
    </thead>
    <tbody id="scheduler_body">
        <?php
        if($orienation == "portrait") {
            foreach($hours as $hour) {
                echo "<tr>";
                $hour_replaced = str_replace(":", "-", $hour);
                echo "<td id='{$hour_replaced}' class='hour'>$hour</td>";
                for($i=0;$i<7;$i++){
                    $is_schedule_highlighted = (isset($old_schedules[$i][$hour])) ? "highlighted ": "";
                    echo "<td class='hour-cell day-$i hour-{$hour_replaced} {$is_schedule_highlighted}'></td>";
                }
                echo "</tr>";
            }
        } else if($orienation == "landscape") {
            for($i=0;$i<7;$i++){
                echo "<tr>";
                echo "<td id='$i' class='day'>{$days[$i]}</td>";
                foreach($hours as $hour) {
                    $is_schedule_highlighted = (isset($old_schedules[$i][$hour])) ? "highlighted ": "";
                    $hour_replaced = str_replace(":", "-", $hour);
                    echo "<td class='hour-cell day-$i hour-{$hour_replaced} {$is_schedule_highlighted}'></td>";
                }
                echo "<td style='background-color: white;'></td></tr>";
            }
        }
        ?>

    </tbody>
</table>
<br>
<?php
$holidays_selection_question = t("Do you want to exclude holidays from schedules?",false);
$holidays_select_all = t("Select all", false);
$holidays_select_none = t("Remove selections", false);
echo(<<<EOL
<div class="form-group">
    <label>{$holidays_selection_question}</label>
    <a id="select-all-holiday" class="text-primary">{$holidays_select_all}</a> / <a id="select-none-holiday" class="text-primary">{$holidays_select_none}</a>
    <script nonce="{$nonce}">
    $('#select-all-holiday').on("click",function(){
        $('.holiday_check').prop('checked', true);
    });
    $('#select-none-holiday').on("click",function(){
        $('.holiday_check').prop('checked', false);
    });
    </script>
EOL);

$i = 0;
foreach ($user->holidays as $holiday) {
    $i++;
    $holiday_name = $holiday->getName();
    $holiday_shortname = $holiday->shortName;
    $is_holiday_selected = in_array($holiday_shortname, $old_holidays) ? "checked" : "";
echo(<<<EOT
    <div class="form-check">
        <input class="form-check-input holiday_check" name="holiday_check" type="checkbox" value="{$holiday_shortname}" id="holidayCheckbox{$i}" {$is_holiday_selected}>
        <label class="form-check-label" for="holidayCheckbox{$i}">
        {$holiday_name} ({$holiday})
        </label>
    </div>
EOT);
}

echo("</div>");
?>
<script nonce="<?php echo($nonce); ?>">
function init_modal() {
  <?php if($orienation == "landscape"){ ?> $(".modal-dialog").css("max-width", "99%"); <?php } ?>
  var isMouseDown = false;
  $(document).mouseup(function () {
    isMouseDown = false;
  });
  $(".hour-cell").mousedown(function () {
    isMouseDown = true;
    $(this).toggleClass("highlighted");
    return false; // prevent text selection
  }).mouseover(function () {
    if (isMouseDown) {
      $(this).toggleClass("highlighted");
    }
  });

  function selectDay(id) {
    console.log("day selection " + id);

    if ($(event.target).hasClass("highlighted_all")) {
      $("#scheduler_body .day-" + id).toggleClass("highlighted");
      $(event.target).toggleClass("highlighted_all");
    } else {
      $("#scheduler_body .day-" + id).addClass("highlighted");
      $(event.target).addClass("highlighted_all");
    }
  }

  function selectHour(id) {
    console.log("hour selection " + id);

    if ($(event.target).hasClass("highlighted_all")) {
      $("#scheduler_body .hour-" + id).toggleClass("highlighted");
      $(event.target).toggleClass("highlighted_all");
    } else {
      $("#scheduler_body .hour-" + id).addClass("highlighted");
      $(event.target).addClass("highlighted_all");
    }
  }

  $(".day").mousedown(function () {
    isMouseDown = true;
    var id = event.target.id;
    selectDay(id);
    return false; // prevent text selection
  }).mouseover(function () {
    if (isMouseDown) {
      var id = event.target.id;
      selectDay(id);
    }
  });
  $(".hour").mousedown(function () {
    isMouseDown = true;
    var id = event.target.id.replace(":", "-");
    selectHour(id);
    return false; // prevent text selection
  }).mouseover(function () {
    if (isMouseDown) {
      var id = event.target.id.replace(":", "-");
      selectHour(id);
    }
  });
  $("#submit_schedules_change").unbind().on("click", submit_changes);
}

function extractSelections() {
  hours_list = [];
  $("#scheduler_body td.highlighted").each(function (key, value) {
    var day = value.classList[1].replace("day-", "");
    var hour = value.classList[2].replace("hour-", "").replace("-", ":");
    if (hour.length < 5) hour = "0" + hour;
    console.log(day, hour, value);
    hours_list.push([day, hour]);
  });
  return hours_list;
}

function submit_changes() {
  var hours = extractSelections();
  var holidays = $.map($('input[name="holiday_check"]:checked'), function (c) {
    return c.value;
  });
  $.ajax({
    url: "resources/ajax/ajax_availability_schedule",
    method: "POST",
    data: {
      hours: hours,
      holidays: holidays
    },
    success: function success(data) {
      console.log(data);
      toastr.success('<?php t('Schedules updated successfully'); ?>');
    }
  });
}
</script>