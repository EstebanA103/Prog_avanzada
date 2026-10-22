
<?php
session_start();

$_SESSION = [];
session_destroy();

header("Location: administrador.html");
exit;
?>
<?php
session_start();
$_SESSION = [];
session_destroy();

header("Location: enfermero_login.html");
exit;
?>