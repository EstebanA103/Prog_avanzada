
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

// Registrar un administrador usando un usuario existente
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["registrar"])) {
    $id_usuario = (int) $_POST["id_usuario"];

    $consulta = $conexion->prepare(
        "SELECT id_usuario FROM usuarios
         WHERE id_usuario = ? AND rol = 'Administrador'"
    );
    $consulta->bind_param("i", $id_usuario);
    $consulta->execute();
    $resultado = $consulta->get_result();

    if ($resultado->num_rows === 0) {
        $mensaje = "El usuario seleccionado no tiene el rol Administrador.";
    } else {
        $consulta->close();

        $consulta = $conexion->prepare(
            "INSERT INTO administradores (id_usuario) VALUES (?)"
        );
        $consulta->bind_param("i", $id_usuario);

        if ($consulta->execute()) {
            $mensaje = "Administrador registrado correctamente.";
        } else {
            if ($conexion->errno === 1062) {
                $mensaje = "Este usuario ya está registrado como administrador.";
            } else {
                $mensaje = "No se pudo registrar. Verifica los datos.";
            }
        }
    }

    $consulta->close();
}

// Eliminar administrador
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["eliminar"])) {
    $id_administrador = (int) $_POST["id_administrador"];

    $consulta = $conexion->prepare(
        "SELECT id_usuario FROM administradores WHERE id_administrador = ?"
    );
    $consulta->bind_param("i", $id_administrador);
    $consulta->execute();
    $resultado = $consulta->get_result();
    $administrador = $resultado->fetch_assoc();
    $consulta->close();

    if (!$administrador) {
        $mensaje = "No se encontró el administrador.";
    } elseif ((int) $administrador["id_usuario"] === (int) $_SESSION["id_usuario"]) {
        $mensaje = "No puedes eliminar tu propia cuenta de administrador.";
    } else {
        $consulta = $conexion->prepare(
            "DELETE FROM administradores WHERE id_administrador = ?"
        );
        $consulta->bind_param("i", $id_administrador);

        if ($consulta->execute()) {
            $mensaje = "Administrador eliminado del listado.";
        } else {
            $mensaje = "No se pudo eliminar el administrador.";
        }

        $consulta->close();
    }
}

// Usuarios con rol Administrador que todavía no están registrados en esta tabla
$usuarios_disponibles = $conexion->query(
    "SELECT u.id_usuario, u.nombre, u.correo
     FROM usuarios u
     LEFT JOIN administradores a ON u.id_usuario = a.id_usuario
     WHERE u.rol = 'Administrador'
       AND a.id_usuario IS NULL
     ORDER BY u.nombre"
);

// Listado de administradores
$administradores = $conexion->query(
    "SELECT a.id_administrador, u.id_usuario, u.nombre, u.correo
     FROM administradores a
     INNER JOIN usuarios u ON a.id_usuario = u.id_usuario
     ORDER BY a.id_administrador DESC"
);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administradores - EnferPacient</title>

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
            max-width: 1100px;
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
    <p class="mb-0">Administración de cuentas administrativas</p>
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
        <h2>Registrar administrador</h2>

        <p>
            Primero debes crear la cuenta en la sección de usuarios y asignarle
            el rol <strong>Administrador</strong>. Después podrás agregarla aquí.
        </p>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Seleccionar usuario administrador</label>

                <select name="id_usuario" class="form-select" required>
                    <option value="">Selecciona una cuenta</option>

                    <?php if ($usuarios_disponibles): ?>
                        <?php while ($usuario = $usuarios_disponibles->fetch_assoc()): ?>
                            <option value="<?= (int) $usuario["id_usuario"] ?>">
                                <?= htmlspecialchars($usuario["nombre"], ENT_QUOTES, "UTF-8") ?>
                                — <?= htmlspecialchars($usuario["correo"], ENT_QUOTES, "UTF-8") ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>

            <button type="submit" name="registrar" class="btn btn-principal">
                Registrar administrador
            </button>
        </form>
    </section>

    <section class="panel">
        <h2>Administradores registrados</h2>

        <div class="tabla-contenedor">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>ID administrador</th>
                        <th>ID usuario</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($administradores && $administradores->num_rows > 0): ?>
                        <?php while ($fila = $administradores->fetch_assoc()): ?>
                            <tr>
                                <td><?= (int) $fila["id_administrador"] ?></td>
                                <td><?= (int) $fila["id_usuario"] ?></td>

                                <td>
                                    <?= htmlspecialchars($fila["nombre"], ENT_QUOTES, "UTF-8") ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($fila["correo"], ENT_QUOTES, "UTF-8") ?>
                                </td>

                                <td>
                                    <?php if ((int) $fila["id_usuario"] !== (int) $_SESSION["id_usuario"]): ?>
                                        <form method="POST"
                                              onsubmit="return confirm('¿Quitar este administrador del listado?');">
                                            <input type="hidden" name="id_administrador"
                                                   value="<?= (int) $fila["id_administrador"] ?>">

                                            <button type="submit" name="eliminar"
                                                    class="btn btn-danger btn-sm">
                                                Eliminar
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">
                                            Tu cuenta actual
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">
                                No hay administradores registrados.
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
