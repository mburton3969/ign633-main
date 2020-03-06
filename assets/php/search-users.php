<?php
include 'connection.php';

//Load Variables...
$val = mysqli_real_escape_string($conn, $_GET['val']);


//Search Users...
$q = "SELECT * FROM `users` WHERE `org_id` = '" . $_SESSION['org_id'] . "' AND `inactive` != 'Yes' AND CONCAT(`fname`, ' ', `lname`) LIKE '%" . $val . "%'  ORDER BY `fname` ASC";
$g = mysqli_query($ign_conn, $q) or die($ign_conn->error);
if(mysqli_num_rows($g) > 0){
  $x->status = 'GOOD';
  $i = 0;
  while($r = mysqli_fetch_array($g)){
    $x->users[$i]->ID = $r['ID'];
    $x->users[$i]->name = $r['fname'] . ' ' . $r['lname'];
    $i++;
  }
}else{
  $x->status = 'BAD';
}

$response = json_encode($x);

echo $response;

?>