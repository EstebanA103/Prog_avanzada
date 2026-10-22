
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
$conexion->set_charset("utf8mb4");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$mensaje = "";

// Registrar notificación
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["registrar"])) {
    $id_usuario = (int) $_POST["id_usuario"];
    $texto = trim($_POST["mensaje"]);

    if ($id_usuario > 0 && $texto !== "") {
        $consulta = $conexion->prepare(
            "INSERT INTO notificaciones (id_usuario, mensaje) VALUES (?, ?)"
        );
        $consulta->bind_param("is", $id_usuario, $texto);

        if ($consulta->execute()) {
            $mensaje = "Notificación registrada correctamente.";
        } else {
            $mensaje = "No se pudo registrar la notificación.";
        }

        $consulta->close();
    } else {
        $mensaje = "Selecciona un usuario y escribe el mensaje.";
    }
}

// Cambiar estado
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["cambiar_estado"])) {
    $id = (int) $_POST["id_notificacion"];
    $estado = $_POST["estado"];

    if (in_array($estado, ["Pendiente", "Leida"], true)) {
        $consulta = $conexion->prepare(
            "UPDATE notificaciones SET estado = ? WHERE id_notificacion = ?"
        );
        $consulta->bind_param("si", $estado, $id);
        $consulta->execute();
        $consulta->close();

        $mensaje = "Estado actualizado.";
    }
}

// Eliminar notificación
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["eliminar"])) {
    $id = (int) $_POST["id_notificacion"];

    $consulta = $conexion->prepare(
        "DELETE FROM notificaciones WHERE id_notificacion = ?"
    );
    $consulta->bind_param("i", $id);

    if ($consulta->execute()) {
        $mensaje = "Notificación eliminada.";
    } else {
        $mensaje = "No se pudo eliminar la notificación.";
    }

    $consulta->close();
}

// Consultar usuarios para el formulario
$usuarios = $conexion->query(
    "SELECT id_usuario, nombre, correo FROM usuarios ORDER BY nombre"
);

// Consultar notificaciones
$notificaciones = $conexion->query(
    "SELECT n.id_notificacion, n.id_usuario, u.nombre, u.correo,
            n.mensaje, n.estado, n.fecha
     FROM notificaciones n
     INNER JOIN usuarios u ON n.id_usuario = u.id_usuario
     ORDER BY n.fecha DESC"
);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar notificaciones - EnferPacient</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #EAF6FF;
            font-family: Arial, sans-serif;
            color: #2d3748;
        }

        .encabezado {
            background: #0B3D7A;
            color: white;
            padding: 22px;
        }

        .contenedor {
            max-width: 1150px;
            margin: 30px auto;
            padding: 0 15px;
        }

        .panel {
            background: white;
            border-radius: 12px;
            padding: 22px;
            margin-bottom: 25px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }

        h2 {
            color: #0B3D7A;
            margin-bottom: 18px;
        }

        .btn-principal {
            background: #0B3D7A;
            color: white;
        }

        .btn-principal:hover {
            background: #1EADF0;
            color: white;
        }

        .tabla-contenedor {
            overflow-x: auto;
        }

        .mensaje {
            padding: 12px;
            background: #d9f3e4;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

<header class="encabezado">
    <h1>EnferPacient</h1>
    <p class="mb-0">Administración de notificaciones</p>
</header>

<main class="contenedor">

    <a href="panel_administrador.php" class="btn btn-secondary mb-3">
        Volver al panel
    </a>

    <?php if ($mensaje !== ""): ?>
        <div class="mensaje">
            <?= htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <section class="panel">
        <h2>Registrar notificación</h2>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Usuario destinatario</label>

                <select name="id_usuario" class="form-select" required>
                    <option value="">Selecciona un usuario</option>

                    <?php if ($usuarios): ?>
                        <?php while ($usuario = $usuarios->fetch_assoc()): ?>
                            <option value="<?= (int) $usuario["id_usuario"] ?>">
                                <?= htmlspecialchars($usuario["nombre"], ENT_QUOTES, "UTF-8") ?>
                                - <?= htmlspecialchars($usuario["correo"], ENT_QUOTES, "UTF-8") ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Mensaje</label>
                <textarea name="mensaje" class="form-control" maxlength="255"
                          rows="3" required></textarea>
            </div>

            <button type="submit" name="registrar" class="btn btn-principal">
                Registrar notificación
            </button>
        </form>
    </section>

    <section class="panel">
        <h2>Notificaciones registradas</h2>

        <div class="tabla-contenedor">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Mensaje</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($notificaciones && $notificaciones->num_rows > 0): ?>
                    <?php while ($fila = $notificaciones->fetch_assoc()): ?>
                        <tr>
                            <td><?= (int) $fila["id_notificacion"] ?></td>

                            <td>
                                <?= htmlspecialchars($fila["nombre"], ENT_QUOTES, "UTF-8") ?><br>
                                <small>
                                    <?= htmlspecialchars($fila["correo"], ENT_QUOTES, "UTF-8") ?>
                                </small>
                            </td>

                            <td>
                                <?= htmlspecialchars($fila["mensaje"], ENT_QUOTES, "UTF-8") ?>
                            </td>

                            <td>
                                <form method="POST" class="d-flex gap-2">
                                    <input type="hidden" name="id_notificacion"
                                           value="<?= (int) $fila["id_notificacion"] ?>">

                                    <select name="estado" class="form-select form-select-sm">
                                        <option value="Pendiente"
                                            <?= $fila["estado"] === "Pendiente" ? "selected" : "" ?>>
                                            Pendiente
                                        </option>

                                        <option value="Leida"
                                            <?= $fila["estado"] === "Leida" ? "selected" : "" ?>>
                                            Leída
                                        </option>
                                    </select>

                                    <button type="submit" name="cambiar_estado"
                                            class="btn btn-sm btn-primary">
                                        Guardar
                                    </button>
                                </form>
                            </td>

                            <td>
                                <?= htmlspecialchars($fila["fecha"], ENT_QUOTES, "UTF-8") ?>
                            </td>

                            <td>
                                <form method="POST"
                                      onsubmit="return confirm('¿Eliminar esta notificación?');">
                                    <input type="hidden" name="id_notificacion"
                                           value="<?= (int) $fila["id_notificacion"] ?>">

                                    <button type="submit" name="eliminar"
                                            class="btn btn-danger btn-sm">
                                        Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">
                            No hay notificaciones registradas.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>

</body>
</html>

<?php
$conexion->close();
?>
