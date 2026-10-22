
<?php
ini_set("display_errors", "1");
error_reporting(E_ALL);

try {
    $conexion = new mysqli(
        "localhost",
        "root",
        "",
        "reservas_enfermeria"
    );

    $conexion->set_charset("utf8mb4");

    $correo = "admin@enferpacient.com";
    $contrasena = "Admin123*";
    $clave_segura = password_hash(
        $contrasena,
        PASSWORD_DEFAULT
    );

    $consulta = $conexion->prepare(
        "UPDATE usuarios
         SET `contraseña` = ?, rol = 'administrador'
         WHERE correo = ?"
    );

    $consulta->bind_param("ss", $clave_segura, $correo);
    $consulta->execute();

    if ($consulta->affected_rows > 0) {
        echo "Contraseña actualizada correctamente.";
    } else {
        $verificar = $conexion->prepare(
            "SELECT id_usuario FROM usuarios WHERE correo = ?"
        );
        $verificar->bind_param("s", $correo);
        $verificar->execute();
        $verificar->store_result();

        if ($verificar->num_rows > 0) {
            echo "La cuenta existe y la contraseña ya está actualizada.";
        } else {
            echo "No existe ese correo en la tabla usuarios.";
        }

        $verificar->close();
    }

    $consulta->close();
    $conexion->close();

} catch (Throwable $error) {
    echo "Error: " . htmlspecialchars($error->getMessage());
}
?>
