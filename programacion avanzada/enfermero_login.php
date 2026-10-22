

<?php
session_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Conectar con la base de datos
    $conexion = new mysqli(
        "localhost",
        "root",
        "",
        "reservas_enfermeria"
    );

    $conexion->set_charset("utf8mb4");

    // Verificar que se envíe el formulario
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        header("Location: enfermero_login.html");
        exit;
    }

    // Recibir los datos del formulario
    $correo = trim($_POST["correo"] ?? "");
    $contrasena = $_POST["contrasena"] ?? "";

    if ($correo === "" || $contrasena === "") {
        die("Debes ingresar el correo y la contraseña.");
    }

    // Buscar la cuenta del enfermero
    $consulta = $conexion->prepare(
        "SELECT id_usuario, nombre, correo, `contraseña`, rol
         FROM usuarios
         WHERE correo = ? AND LOWER(rol) = 'enfermero'"
    );

    $consulta->bind_param("s", $correo);
    $consulta->execute();

    $resultado = $consulta->get_result();

    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();

        // Comprobar la contraseña
        if (password_verify($contrasena, $usuario["contraseña"])) {

            // Crear una nueva sesión para el enfermero
            session_regenerate_id(true);

            $_SESSION["id_usuario"] = $usuario["id_usuario"];
            $_SESSION["nombre"] = $usuario["nombre"];
            $_SESSION["correo"] = $usuario["correo"];
            $_SESSION["rol"] = $usuario["rol"];

            $consulta->close();
            $conexion->close();

            // Abrir el panel del enfermero
            header("Location: panel_enfermero.php");
            exit;

        } else {
            echo "El correo o la contraseña son incorrectos.";
        }

    } else {
        echo "El correo o la contraseña son incorrectos.";
    }

    $consulta->close();
    $conexion->close();

} catch (Throwable $error) {
    error_log($error->getMessage());
    http_response_code(500);
    echo "Ocurrió un error al conectar con la base de datos. Revisa la configuración de XAMPP.";
}
?>
