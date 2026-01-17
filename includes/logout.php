<?php
session_start();
session_unset();
session_destroy();
//logout kanw back se intex
header('Location: /Ergasia3_php/index.php');
exit();
?>