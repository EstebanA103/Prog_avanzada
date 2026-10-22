
<?php
session_start();

if (
    !isset($_SESSION["id_usuario"]) ||
    !isset($_SESSION["rol"]) ||
    trim($_SESSION["rol"]) !== "Administrador"
) {
    header("Location: administrador.html");
    exit;
}

$conexion = new mysqli("localhost", "root", "", "reservas_enfermeria");

if ($conexion->connect_error) {
    die("Error al conectar con la base de datos.");
}

$conexion->set_charset("utf8mb4");

$mensaje = "";

function limpiar($dato) {
    return htmlspecialchars((string)$dato, ENT_QUOTES, "UTF-8");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $accion = $_POST["accion"] ?? "";

    if ($accion === "crear") {
        $nombre = trim($_POST["nombre"] ?? "");
        $correo = trim($_POST["correo"] ?? "");
        $contrasena = $_POST["contrasena"] ?? "";
        $rol = $_POST["rol"] ?? "Usuario";

        $rolesPermitidos = ["Usuario", "Paciente", "Enfermero", "Administrador"];

        if (
            $nombre === "" ||
            !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
            $contrasena === "" ||
            !in_array($rol, $rolesPermitidos, true)
        ) {
            $mensaje = "Completa correctamente todos los campos.";
        } else {
            $clave = password_hash($contrasena, PASSWORD_DEFAULT);

            $consulta = $conexion->prepare(
                "INSERT INTO usuarios (nombre, correo, contraseña, rol)
                 VALUES (?, ?, ?, ?)"
            );

            $consulta->bind_param("ssss", $nombre, $correo, $clave, $rol);

            if ($consulta->execute()) {
                $mensaje = "Usuario registrado correctamente.";
            } else {
                $mensaje = $consulta->errno === 1062
                    ? "Ese correo ya está registrado."
                    : "No se pudo registrar el usuario. Revisa los datos.";
            }

            $consulta->close();
        }
    }

    if ($accion === "editar") {
        $id = (int)($_POST["id_usuario"] ?? 0);
        $nombre = trim($_POST["nombre"] ?? "");
        $correo = trim($_POST["correo"] ?? "");
        $rol = $_POST["rol"] ?? "";
        $contrasena = $_POST["contrasena"] ?? "";

        $rolesPermitidos = ["Usuario", "Paciente", "Enfermero", "Administrador"];

        if (
            $id <= 0 ||
            $nombre === "" ||
            !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
            !in_array($rol, $rolesPermitidos, true)
        ) {
            $mensaje = "Los datos ingresados no son válidos.";
        } elseif (
            $id === (int)$_SESSION["id_usuario"] &&
            $rol !== "Administrador"
        ) {
            $mensaje = "No puedes quitarte tu propio rol de administrador.";
        } else {
            if ($contrasena !== "") {
                $clave = password_hash($contrasena, PASSWORD_DEFAULT);

                $consulta = $conexion->prepare(
                    "UPDATE usuarios
                     SET nombre = ?, correo = ?, contraseña = ?, rol = ?
                     WHERE id_usuario = ?"
                );

                $consulta->bind_param(
                    "ssssi",
                    $nombre,
                    $correo,
                    $clave,
                    $rol,
                    $id
                );
            } else {
                $consulta = $conexion->prepare(
                    "UPDATE usuarios
                     SET nombre = ?, correo = ?, rol = ?
                     WHERE id_usuario = ?"
                );

                $consulta->bind_param(
                    "sssi",
                    $nombre,
                    $correo,
                    $rol,
                    $id
                );
            }

            if ($consulta->execute()) {
                $mensaje = "Usuario actualizado correctamente.";
            } else {
                $mensaje = $consulta->errno === 1062
                    ? "Ese correo ya pertenece a otra cuenta."
                    : "No se pudo actualizar el usuario.";
            }

            $consulta->close();
        }
    }

    if ($accion === "eliminar") {
        $id = (int)($_POST["id_usuario"] ?? 0);

        if ($id === (int)$_SESSION["id_usuario"]) {
            $mensaje = "No puedes eliminar tu propia cuenta.";
        } elseif ($id <= 0) {
            $mensaje = "El usuario seleccionado no es válido.";
        } else {
            $consulta = $conexion->prepare(
                "DELETE FROM usuarios WHERE id_usuario = ?"
            );

            $consulta->bind_param("i", $id);

            if ($consulta->execute()) {
                $mensaje = $consulta->affected_rows > 0
                    ? "Usuario eliminado correctamente."
                    : "No se encontró ese usuario.";
            } else {
                $mensaje = "No se pudo eliminar. Puede tener reservas u otros registros relacionados.";
            }

            $consulta->close();
        }
    }
}

$resultado = $conexion->query(
    "SELECT id_usuario, nombre, correo, rol
     FROM usuarios
     ORDER BY id_usuario DESC"
);

if (!$resultado) {
    die("No se pudieron consultar los usuarios. Revisa la estructura de la tabla usuarios.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Administrar usuarios - EnferPacient</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #EAF6FF;
            font-family: Arial, sans-serif;
            color: #2d3748;
        }

        header {
            background: #0B3D7A;
            color: white;
            padding: 20px;
        }

        .contenedor {
            max-width: 1150px;
            margin: 30px auto;
            padding: 0 15px;
        }

        .tarjeta {
            background: white;
            padding: 22px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
        }

        .btn-azul {
            background: #0B3D7A;
            color: white;
        }

        .btn-azul:hover {
            background: #1EADF0;
            color: white;
        }

        .tabla-contenedor {
            overflow-x: auto;
        }

        .campo {
            min-width: 120px;
        }
    </style>
</head>

<body>

<header>
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h2>EnferPacient</h2>
            <p class="mb-0">Administración de usuarios</p>
        </div>

        <a href="panel_administrador.php" class="btn btn-light">
            Volver al panel
        </a>
    </div>
</header>

<main class="contenedor">

    <h3>Gestión de usuarios</h3>
    <p>Desde esta página puedes registrar, consultar, editar y eliminar cuentas.</p>

    <?php if ($mensaje !== ""): ?>
        <div class="alert alert-info">
            <?php echo limpiar($mensaje); ?>
        </div>
    <?php endif; ?>

    <section class="tarjeta">
        <h4>Registrar usuario</h4>

        <form method="POST">
            <input type="hidden" name="accion" value="crear">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre completo</label>
                    <input type="text" name="nombre" class="form-control" maxlength="100" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Correo electrónico</label>
                    <input type="email" name="correo" class="form-control" maxlength="100" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="contrasena" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Rol</label>
                    <select name="rol" class="form-select" required>
                        <option value="Usuario">Usuario</option>
                        <option value="Paciente">Paciente</option>
                        <option value="Enfermero">Enfermero</option>
                        <option value="Administrador">Administrador</option>
                    </select>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-azul">
                        Registrar usuario
                    </button>
                </div>
            </div>
        </form>
    </section>

    <section class="tarjeta">
        <h4>Usuarios registrados</h4>

        <div class="tabla-contenedor">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Editar datos</th>
                        <th>Eliminar</th>
                    </tr>
                </thead>

                <tbody>
                <?php while ($usuario = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo (int)$usuario["id_usuario"]; ?></td>
                        <td><?php echo limpiar($usuario["nombre"]); ?></td>
                        <td><?php echo limpiar($usuario["correo"]); ?></td>
                        <td><?php echo limpiar($usuario["rol"]); ?></td>

                        <td>
                            <form method="POST" class="d-grid gap-2">
                                <input type="hidden" name="accion" value="editar">
                                <input type="hidden" name="id_usuario" value="<?php echo (int)$usuario["id_usuario"]; ?>">

                                <input
                                    type="text"
                                    name="nombre"
                                    class="form-control campo"
                                    value="<?php echo limpiar($usuario["nombre"]); ?>"
                                    maxlength="100"
                                    required
                                >

                                <input
                                    type="email"
                                    name="correo"
                                    class="form-control campo"
                                    value="<?php echo limpiar($usuario["correo"]); ?>"
                                    maxlength="100"
                                    required
                                >

                                <select name="rol" class="form-select" required>
                                    <?php
                                    $roles = ["Usuario", "Paciente", "Enfermero", "Administrador"];
                                    foreach ($roles as $rol):
                                    ?>
                                        <option
                                            value="<?php echo limpiar($rol); ?>"
                                            <?php echo $usuario["rol"] === $rol ? "selected" : ""; ?>
                                        >
                                            <?php echo limpiar($rol); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <input
                                    type="password"
                                    name="contrasena"
                                    class="form-control campo"
                                    placeholder="Nueva contraseña (opcional)"
                                >

                                <button type="submit" class="btn btn-azul">
                                    Guardar cambios
                                </button>
                            </form>
                        </td>

                        <td>
                            <?php if ((int)$usuario["id_usuario"] !== (int)$_SESSION["id_usuario"]): ?>
                                <form method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este usuario?');">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id_usuario" value="<?php echo (int)$usuario["id_usuario"]; ?>">

                                    <button type="submit" class="btn btn-danger">
                                        Eliminar
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted">Cuenta actual</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>

</body>
</html>
