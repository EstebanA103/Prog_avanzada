
<?php
session_start();

ini_set('display_errors', '1');
error_reporting(E_ALL);

$conexion = new mysqli(
    "localhost",
    "root",
    "",
    "reservas_enfermeria"
);

if ($conexion->connect_error) {
    die("Error de conexión con la base de datos.");
}

$conexion->set_charset("utf8mb4");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Debes ingresar desde el formulario de inicio de sesión.");
}

$correo = trim($_POST["correo"] ?? "");
$contrasena = $_POST["contrasena"] ?? "";

if ($correo === "" || $contrasena === "") {
    die("Escribe tu correo y contraseña.");
}

// Buscar usuario por correo
$consulta = $conexion->prepare(
    "SELECT id_usuario, nombre, correo, `contraseña`, rol
     FROM usuarios
     WHERE correo = ?"
);

$consulta->bind_param("s", $correo);
$consulta->execute();

$resultado = $consulta->get_result();

if ($resultado->num_rows === 1) {

    $usuario = $resultado->fetch_assoc();

    // Verificar contraseña
    if (password_verify($contrasena, $usuario["contraseña"])) {

        session_regenerate_id(true);

        $_SESSION["id_usuario"] = $usuario["id_usuario"];
        $_SESSION["nombre"] = $usuario["nombre"];
        $_SESSION["correo"] = $usuario["correo"];
        $_SESSION["rol"] = $usuario["rol"];

        // Dirigir al panel de reservas
        header("Location: reservas_usuario.php");
        exit;

    } else {
        echo "La contraseña es incorrecta.";
    }

} else {
    echo "No existe una cuenta registrada con ese correo.";
}

$consulta->close();
$conexion->close();
?>
