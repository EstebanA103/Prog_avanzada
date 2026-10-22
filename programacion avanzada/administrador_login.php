
<?php
session_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conexion = new mysqli(
        "localhost",
        "root",
        "",
        "reservas_enfermeria"
    );

    $conexion->set_charset("utf8mb4");

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        header("Location: administrador.html");
        exit;
    }

    $correo = trim($_POST["correo"] ?? "");
    $contrasena = $_POST["contrasena"] ?? "";

    if ($correo === "" || $contrasena === "") {
        die("Ingresa el correo y la contraseña.");
    }

    $consulta = $conexion->prepare(
        "SELECT id_usuario, nombre, correo, `contraseña`, rol
         FROM usuarios
         WHERE correo = ? AND rol = 'Administrador'
         LIMIT 1"
    );

    $consulta->bind_param("s", $correo);
    $consulta->execute();

    $resultado = $consulta->get_result();

    if ($resultado->num_rows !== 1) {
        die("No se encontró una cuenta de administrador con ese correo.");
    }

    $usuario = $resultado->fetch_assoc();

    if (!password_verify($contrasena, $usuario["contraseña"])) {
        die("La contraseña no coincide con la cuenta.");
    }

    session_regenerate_id(true);

    $_SESSION["id_usuario"] = $usuario["id_usuario"];
    $_SESSION["nombre"] = $usuario["nombre"];
    $_SESSION["correo"] = $usuario["correo"];
    $_SESSION["rol"] = $usuario["rol"];

    $consulta->close();
    $conexion->close();

    header("Location: panel_administrador.php");
    exit;

} catch (Throwable $error) {
    error_log($error->getMessage());
    http_response_code(500);

    echo "Ocurrió un error al conectar con la base de datos. Revisa XAMPP y los archivos del proyecto.";
}
?>

