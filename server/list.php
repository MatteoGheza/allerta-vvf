<?php
require_once 'ui.php';
$_SESSION["token_list"] = bin2hex(random_bytes(64));
loadtemplate('list.html', ['title' => t("Availability List", false), 'token_list' => $_SESSION['token_list']]);
bdump($_SESSION);
