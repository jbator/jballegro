<?php

require_once 'Jb.class.php';
include_once(dirname(__FILE__) . '/../../classes/MySQL.php');

$jb = new Jb();

if (isset($_GET['checkallegro'])) {
  echo json_encode($jb->checkByAllegro());
}
else if (isset($_GET['checkaqty'])) {
  echo json_encode($jb->checkByQuantity());
}
else echo 'Brak parametrów'
?>
