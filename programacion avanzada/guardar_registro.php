
<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

$conexion = new mysqli("localhost", "root", "", "reservas_enfermeria");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Accede desde el formulario de registro.");
}

// Recibir datos del registro.html
$nombre = trim($_POST["nombre"] ?? "");
$correo = trim($_POST["correo"] ?? "");
$telefono = trim($_POST["telefono"] ?? "");
$contrasena = $_POST["contrasena"] ?? "";
$confirmar = $_POST["confirmar"] ?? "";

// Validar campos
if (
    $nombre === "" ||
    $correo === "" ||
    $telefono === "" ||
    $contrasena === "" ||
    $confirmar === ""
) {
    die("Debes completar todos los campos.");
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    die("El correo electrónico no es válido.");
}

if ($contrasena !== $confirmar) {
    die("Las contraseñas no coinciden.");
}

// Verificar si el correo ya existe
$consulta = $conexion->prepare(
    "SELECT id_usuario FROM usuarios WHERE correo = ?"
);

$consulta->bind_param("s", $correo);
$consulta->execute();
$consulta->store_result();

if ($consulta->num_rows > 0) {
    $consulta->close();
    $conexion->close();
    die("Este correo ya está registrado.");
}

$consulta->close();

// Proteger la contraseña
$contrasena_segura = password_hash(
    $contrasena,
    PASSWORD_DEFAULT
);

$rol = "paciente";

// Guardar usuario
$insertar = $conexion->prepare(
    "INSERT INTO usuarios (nombre, correo, contraseña, rol)
     VALUES (?, ?, ?, ?)"
);

$insertar->bind_param(
    "ssss",
    $nombre,
    $correo,
    $contrasena_segura,
    $rol
);

if (!$insertar->execute()) {
    die("Error al guardar el usuario: " . $insertar->error);
}

$id_usuario = $conexion->insert_id;
$insertar->close();

// Guardar teléfono del paciente
$consulta_paciente = $conexion->prepare(
    "INSERT INTO pacientes (id_usuario, telefono)
     VALUES (?, ?)"
);

if (!$consulta_paciente) {
    die("El usuario se creó, pero revisa las columnas de pacientes: "
        . $conexion->error);
}

$consulta_paciente->bind_param("is", $id_usuario, $telefono);

if ($consulta_paciente->execute()) {
    echo "¡Registro exitoso! Ya puedes iniciar sesión en EnferPacient.";
} else {
    echo "El usuario se creó, pero ocurrió un error al guardar el teléfono: "
        . $consulta_paciente->error;
}

$consulta_paciente->close();
$conexion->close();
?>
