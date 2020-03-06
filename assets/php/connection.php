<?php
session_start();

if($_SERVER['HTTP_HOST'] == 'sandbox.ignition633.org'){
  error_reporting(E_ALL);
  $db_host = 'localhost';
  $db_user = 'root';
  $db_pass = 'root';
}else{
  error_reporting(0);
  $db_host = 'localhost';
  $db_user = 'mburton9_ign633';
  $db_pass = 'Ignition633!';
}

$ign_conn = mysqli_connect($db_host,$db_user,$db_pass,'mburton9_ign633') or die('IGN DB Connection Error: ' . $ign_conn->error);
$tb_conn = mysqli_connect($db_host,$db_user,$db_pass,'mburton9_toolbox') or die('Toolbox DB Connection Error: ' . $tb_conn->error);
if($_SESSION['org_db_name']){
  $conn = mysqli_connect($db_host,$db_user,$db_pass,$_SESSION['org_db_name']) or die('ORG DB [' . $_SESSION['org_db_name'] . '] Connection Error: ' . $conn->error);
}
$sys_ctime = date('h:iA');
$ctime = date("h:iA", (strtotime($sys_ctime) + 60*60));//Adds 1 hour to system time..
$current_time = $ctime;
?>