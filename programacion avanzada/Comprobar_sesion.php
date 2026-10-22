
<?php
session_start();

echo "<h2>Comprobación de sesión</h2>";

echo "<p>ID de usuario: ";
var_dump($_SESSION["id_usuario"] ?? "No existe");
echo "</p>";

echo "<p>Nombre: ";
var_dump($_SESSION["nombre"] ?? "No existe");
echo "</p>";

echo "<p>Rol: ";
var_dump($_SESSION["rol"] ?? "No existe");
echo "</p>";
?>
