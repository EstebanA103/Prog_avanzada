
<?php
session_start();

// Verificar que el usuario haya iniciado sesión
if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.html");
    exit;
}

$id_usuario = (int) $_SESSION["id_usuario"];

$conexion = new mysqli("localhost", "root", "", "reservas_enfermeria");
$conexion->set_charset("utf8mb4");

if ($conexion->connect_error) {
    die("Error de conexión con la base de datos.");
}

$mensaje = "";
$tipo_mensaje = "info";

// Nombre del usuario
$consulta_usuario = $conexion->prepare(
    "SELECT nombre FROM usuarios WHERE id_usuario = ?"
);
$consulta_usuario->bind_param("i", $id_usuario);
$consulta_usuario->execute();
$resultado_usuario = $consulta_usuario->get_result();
$usuario = $resultado_usuario->fetch_assoc();
$consulta_usuario->close();

if (!$usuario) {
    session_destroy();
    header("Location: login.html");
    exit;
}

// Crear una reserva
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["agendar"])) {
    $id_servicio = (int) ($_POST["id_servicio"] ?? 0);
    $fecha = $_POST["fecha"] ?? "";
    $observaciones = trim($_POST["observaciones"] ?? "");

    if (
        $id_servicio <= 0 ||
        $fecha === "" ||
        $fecha < date("Y-m-d") ||
        strlen($observaciones) > 255
    ) {
        $mensaje = "Revisa el servicio, la fecha y las observaciones.";
        $tipo_mensaje = "danger";
    } else {
        // Comprobar que el servicio exista y esté activo
        $consulta = $conexion->prepare(
            "SELECT id_servicio FROM servicios
             WHERE id_servicio = ? AND estado = 'Activo'"
        );
        $consulta->bind_param("i", $id_servicio);
        $consulta->execute();
        $resultado = $consulta->get_result();
        $servicio_valido = $resultado->num_rows > 0;
        $consulta->close();

        if (!$servicio_valido) {
            $mensaje = "El servicio seleccionado no está disponible.";
            $tipo_mensaje = "danger";
        } else {
            // El usuario se toma de la sesión, no del formulario
            $consulta = $conexion->prepare(
                "INSERT INTO reservas
                 (id_usuario, id_servicio, fecha, estado, observaciones)
                 VALUES (?, ?, ?, 'Pendiente', ?)"
            );
            $consulta->bind_param(
                "iiss",
                $id_usuario,
                $id_servicio,
                $fecha,
                $observaciones
            );

            if ($consulta->execute()) {
                $mensaje = "¡Tu reserva fue agendada! Está pendiente de confirmación.";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "No se pudo guardar la reserva. Intenta nuevamente.";
                $tipo_mensaje = "danger";
            }

            $consulta->close();
        }
    }
}

// Cancelar una reserva propia que todavía esté pendiente
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["cancelar"])) {
    $id_reserva = (int) ($_POST["id_reserva"] ?? 0);

    $consulta = $conexion->prepare(
        "UPDATE reservas
         SET estado = 'Cancelada'
         WHERE id_reserva = ?
           AND id_usuario = ?
           AND estado = 'Pendiente'"
    );
    $consulta->bind_param("ii", $id_reserva, $id_usuario);
    $consulta->execute();

    if ($consulta->affected_rows > 0) {
        $mensaje = "La reserva fue cancelada.";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "No se pudo cancelar. Solo puedes cancelar reservas pendientes.";
        $tipo_mensaje = "warning";
    }

    $consulta->close();
}

// Consultar servicios activos y sus precios
$servicios = $conexion->query(
    "SELECT id_servicio, nombre, descripcion, precio, unidad_cobro
     FROM servicios
     WHERE estado = 'Activo'
     ORDER BY nombre"
);

// Consultar solamente las reservas del usuario que inició sesión
$consulta_reservas = $conexion->prepare(
    "SELECT r.id_reserva, r.fecha, r.estado, r.observaciones,
            s.nombre AS servicio, s.precio, s.unidad_cobro
     FROM reservas r
     INNER JOIN servicios s ON r.id_servicio = s.id_servicio
     WHERE r.id_usuario = ?
     ORDER BY r.fecha DESC, r.id_reserva DESC"
);
$consulta_reservas->bind_param("i", $id_usuario);
$consulta_reservas->execute();
$reservas = $consulta_reservas->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mis reservas - EnferPacient</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <style>
        body {
            margin: 0;
            background: #EAF6FF;
            color: #2d3748;
            font-family: Arial, sans-serif;
        }

        .encabezado {
            background: #0B3D7A;
            color: white;
            padding: 25px;
        }

        .encabezado p {
            margin-bottom: 0;
        }

        .contenedor {
            max-width: 1150px;
            margin: 30px auto;
            padding: 0 15px;
        }

        .panel {
            background: white;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        h2 {
            color: #0B3D7A;
            margin-bottom: 18px;
        }

        .servicio {
            border: 1px solid #d7e8f7;
            border-radius: 10px;
            padding: 18px;
            height: 100%;
        }

        .precio {
            color: #0B3D7A;
            font-size: 23px;
            font-weight: bold;
        }

        .btn-enferpacient {
            background: #0B3D7A;
            color: white;
        }

        .btn-enferpacient:hover {
            background: #1EADF0;
            color: white;
        }

        .tabla-contenedor {
            overflow-x: auto;
        }

        .estado {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 6px;
            background: #fff0c2;
            color: #795600;
        }
    </style>
</head>

<body>

<header class="encabezado">
    <h1>EnferPacient</h1>
    <p>La salud es la vida</p>
    <p class="mt-2">
        Bienvenido/a,
        <strong><?= htmlspecialchars($usuario["nombre"], ENT_QUOTES, "UTF-8") ?></strong>
    </p>
</header>

<main class="contenedor">

    <div class="mb-3">
        <a href="index.html" class="btn btn-secondary">Volver al inicio</a>
    </div>

    <?php if ($mensaje !== ""): ?>
        <div class="alert alert-<?= $tipo_mensaje ?>">
            <?= htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <section class="panel">
        <h2>Servicios y precios</h2>
        <p>Consulta los servicios disponibles antes de realizar tu reserva.</p>

        <div class="row g-3">
            <?php if ($servicios && $servicios->num_rows > 0): ?>
                <?php while ($servicio = $servicios->fetch_assoc()): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="servicio">
                            <h5>
                                <?= htmlspecialchars($servicio["nombre"], ENT_QUOTES, "UTF-8") ?>
                            </h5>

                            <p>
                                <?= htmlspecialchars(
                                    $servicio["descripcion"] ?? "",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </p>

                            <p class="precio">
                                $<?= number_format((float) $servicio["precio"], 0, ",", ".") ?>
                            </p>

                            <small>
                                <?= htmlspecialchars(
                                    $servicio["unidad_cobro"] ?? "Precio del servicio",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </small>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    Todavía no hay servicios activos. Consulta con el administrador.
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="panel">
        <h2>Agendar una reserva</h2>

        <form method="POST">
            <div class="mb-3">
                <label for="id_servicio" class="form-label">
                    Selecciona el servicio
                </label>

                <select name="id_servicio" id="id_servicio"
                        class="form-select" required>
                    <option value="">Elige un servicio</option>

                    <?php
                    $servicios_formulario = $conexion->query(
                        "SELECT id_servicio, nombre, precio, unidad_cobro
                         FROM servicios
                         WHERE estado = 'Activo'
                         ORDER BY nombre"
                    );
                    ?>

                    <?php if ($servicios_formulario): ?>
                        <?php while ($s = $servicios_formulario->fetch_assoc()): ?>
                            <option value="<?= (int) $s["id_servicio"] ?>">
                                <?= htmlspecialchars($s["nombre"], ENT_QUOTES, "UTF-8") ?>
                                — $<?= number_format((float) $s["precio"], 0, ",", ".") ?>
                                <?= htmlspecialchars($s["unidad_cobro"] ?? "", ENT_QUOTES, "UTF-8") ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="fecha" class="form-label">Fecha de la reserva</label>
                <input type="date" name="fecha" id="fecha"
                       class="form-control" min="<?= date("Y-m-d") ?>" required>
                <small class="text-muted">
                    Por ahora puedes seleccionar la fecha; tu base de datos no guarda la hora.
                </small>
            </div>

            <div class="mb-3">
                <label for="observaciones" class="form-label">
                    Observaciones (opcional)
                </label>
                <textarea name="observaciones" id="observaciones"
                          class="form-control" rows="3" maxlength="255"
                          placeholder="Cuéntanos si necesitas indicar algo importante."></textarea>
            </div>

            <button type="submit" name="agendar" class="btn btn-enferpacient">
                Agendar reserva
            </button>
        </form>
    </section>

    <section class="panel">
        <h2>Mis reservas</h2>

        <div class="tabla-contenedor">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>N.º</th>
                        <th>Servicio</th>
                        <th>Precio actual</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Observaciones</th>
                        <th>Acción</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($reservas && $reservas->num_rows > 0): ?>
                        <?php while ($r = $reservas->fetch_assoc()): ?>
                            <tr>
                                <td><?= (int) $r["id_reserva"] ?></td>

                                <td>
                                    <?= htmlspecialchars($r["servicio"], ENT_QUOTES, "UTF-8") ?>
                                </td>

                                <td>
                                    $<?= number_format((float) $r["precio"], 0, ",", ".") ?>
                                    <?= htmlspecialchars($r["unidad_cobro"] ?? "", ENT_QUOTES, "UTF-8") ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($r["fecha"], ENT_QUOTES, "UTF-8") ?>
                                </td>

                                <td>
                                    <span class="estado">
                                        <?= htmlspecialchars($r["estado"], ENT_QUOTES, "UTF-8") ?>
                                    </span>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $r["observaciones"] ?? "",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </td>

                                <td>
                                    <?php if ($r["estado"] === "Pendiente"): ?>
                                        <form method="POST"
                                              onsubmit="return confirm('¿Deseas cancelar esta reserva?');">
                                            <input type="hidden" name="id_reserva"
                                                   value="<?= (int) $r["id_reserva"] ?>">

                                            <button type="submit" name="cancelar"
                                                    class="btn btn-danger btn-sm">
                                                Cancelar
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">Sin acciones</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">
                                Todavía no tienes reservas. ¡Puedes agendar la primera!
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
$consulta_reservas->close();
$conexion->close();
?>
