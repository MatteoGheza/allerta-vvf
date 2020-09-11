<?php
include_once("../../core.php");
init_class();
$user->requirelogin();

$risultato = $database->exec("SELECT * FROM `%PREFIX%_profiles` ORDER BY available DESC, caposquadra DESC, services ASC, availability_minutes ASC, name ASC;", true);

$hidden = $user->hidden();
?>
<style>
th, td {
    border: 1px solid grey;
    border-collapse: collapse;
 padding: 5px;
}

#href {
 outline: none;
 cursor: pointer;
 text-align: center;
 text-decoration: none;
 font: bold 12px Arial, Helvetica, sans-serif;
 color: #fff;
 padding: 10px 20px;
 border: solid 1px #0076a3;
 background: #0095cd;
}

 table {
   box-shadow: 2px 2px 25px rgba(0,0,0,0.5);
    border-radius: 15px;
  margin: auto;
 }


</style>
<div style="overflow-x:auto;">
<table style="width: 90%; text-align:center;">
  <tr>
    <th><?php t("Name"); ?></th>
    <th><?php t("Available"); ?></th>
    <th><?php t("Driver"); ?></th>
    <th><?php t("Call"); ?></th>
    <th><?php t("Write"); ?></th>
    <th><?php t("Services"); ?></th>
    <th><?php t("Availability Minutes"); ?></th>
    <th><?php t("Other"); ?></th>
    <?php
    foreach($risultato as $row){
      if(!in_array($row['name'], $hidden) && ($row['hidden'] == 0 && $row['disabled'] == 0)){
        echo "<tr>
           <td>";
      $name = $user->nameById($row["id"]);
      $callFunction = ($row['available'] == 1) ? "NonAttivo" : "Attivo";
      $available = $row["available"];
      if ($row['caposquadra'] == 1) {
        echo "<a onclick='$callFunction(".$row["id"].");'><img src='./risorse/images/cascoRosso.png' width='20px'>   ";
      } else {
        echo "<a onclick='Attivo(".$row["id"].");'><img src='./risorse/images/cascoNero.png' width='20px'>   ";
      }
      if((time()-$row["online_time"])<=30){
        echo "<u>".$name."</u></a></td><td><a onclick='$callFunction(".$row["id"].");'>";
      } else {
        echo $name."</a></td><td><a onclick='$callFunction(".$row["id"].");'>";
      }
      if ($row['available'] == 1) {
        echo "<i class='fa fa-check' style='color:green'></i>";
      } else {
        echo "<i class='fa fa-times'  style='color:red'></i>";
      };
        echo  "</a></td>
        <td>";
      if ($row['autista'] == 1) {
        echo "<img src='./risorse/images/volante.png' width='20px'>";
      } else {
        echo "";
      };
      echo "</td>
          <td><a href='tel:+" . $row['telefono'] . "'><i class='fa fa-phone'></i></a></td><td>";

      $nome_url = urlencode($row['name']);
      echo "  <a href='https://api.whatsapp.com/send?phone=" . $row['telefono'] . "&text=ALLERTA IN CORSO.%20Mettiti%20in%20contatto%20con%20$nome_url'><i class='fa fa-whatsapp' style='color:green'></i></td>";

      $services = $row['services'];
      $minutes = $row['availability_minutes'];
      $u = 'anagrafica.php?user=' . str_replace(' ', '_', urldecode(strtolower($row["id"])));
      echo "<td>$services</td><td>$minutes</td><td><a href='$u'><p>".t("Others infos",false)."</p></a></td></tr>";
      }
    }
    ?>
    </table>
    <br>
    <p style="text-align: center;">
      <a id='add' href="edit_user.php?add"><?php t("Add user"); ?></a>
    </p>
</div>
