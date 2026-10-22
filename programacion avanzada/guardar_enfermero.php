
<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conexion = new mysqli(
    "localhost",
    "root",
    "",
    "reservas_enfermeria"
);

$conexion->set_charset("utf8mb4");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Debes ingresar desde el formulario de enfermeros.");
}

// Recibir los datos del formulario
$nombre = trim($_POST["nombre"] ?? "");
$apellido = trim($_POST["apellido"] ?? "");
$correo = trim($_POST["correo"] ?? "");
$telefono = trim($_POST["telefono"] ?? "");
$especialidad = trim($_POST["especialidad"] ?? "");
$contrasena = $_POST["contrasena"] ?? "";
$disponible = $_POST["disponible"] ?? "";

// Validar los campos
if (
    $nombre === "" ||
    $apellido === "" ||
    $correo === "" ||
    $telefono === "" ||
    $especialidad === "" ||
    $contrasena === "" ||
    !in_array($disponible, ["0", "1"], true)
) {
    die("Completa todos los campos.");
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    die("El correo electrónico no es válido.");
}

if (!preg_match('/^[0-9]{10}$/', $telefono)) {
    die("El teléfono debe tener exactamente 10 dígitos.");
}

if (strlen($contrasena) < 6) {
    die("La contraseña debe tener al menos 6 caracteres.");
}

// Proteger la contraseña
$contrasena_segura = password_hash(
    $contrasena,
    PASSWORD_DEFAULT
);

$rol = "enfermero";

try {
    $conexion->begin_transaction();

    // Comprobar si el correo ya existe
    $consulta = $conexion->prepare(
        "SELECT id_usuario FROM usuarios WHERE correo = ?"
    );

    $consulta->bind_param("s", $correo);
    $consulta->execute();
    $consulta->store_result();

    if ($consulta->num_rows > 0) {
        $consulta->close();
        throw new Exception("Este correo ya está registrado.");
    }

    $consulta->close();

    // Crear la cuenta en usuarios
    $insertar_usuario = $conexion->prepare(
        "INSERT INTO usuarios
        (nombre, correo, `contraseña`, rol)
        VALUES (?, ?, ?, ?)"
    );

    $insertar_usuario->bind_param(
        "ssss",
        $nombre,
        $correo,
        $contrasena_segura,
        $rol
    );

    $insertar_usuario->execute();

    $id_usuario = $conexion->insert_id;
    $insertar_usuario->close();

    // Guardar los datos profesionales
    $insertar_enfermero = $conexion->prepare(
        "INSERT INTO enfermeros
        (id_usuario, apellido, telefono, especialidad, disponible)
        VALUES (?, ?, ?, ?, ?)"
    );

    $disponible_numero = (int) $disponible;

    $insertar_enfermero->bind_param(
        "isssi",
        $id_usuario,
        $apellido,
        $telefono,
        $especialidad,
        $disponible_numero
    );

    $insertar_enfermero->execute();
    $insertar_enfermero->close();

    $conexion->commit();

    echo "¡Registro de enfermero exitoso!";

} catch (Throwable $error) {
    $conexion->rollback();

    echo "No se pudo completar el registro: "
        . htmlspecialchars(
            $error->getMessage(),
            ENT_QUOTES,
            "UTF-8"
        );
}

$conexion->close();
?>
