
<?php
session_start();

// Verificar que haya iniciado sesión un enfermero
if (
    !isset($_SESSION["id_usuario"]) ||
    strtolower($_SESSION["rol"] ?? "") !== "enfermero"
) {
    header("Location: enfermero_login.html");
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conexion = new mysqli(
        "localhost",
        "root",
        "",
        "reservas_enfermeria"
    );

    $conexion->set_charset("utf8mb4");

    $id_usuario = (int) $_SESSION["id_usuario"];
    $nombre = $_SESSION["nombre"] ?? "Enfermero";

    // Buscar el perfil relacionado con la cuenta
    $consultaPerfil = $conexion->prepare(
        "SELECT u.nombre, u.correo, e.id_enfermero,
                e.apellido, e.telefono, e.especialidad, e.disponible
         FROM usuarios u
         INNER JOIN enfermeros e ON u.id_usuario = e.id_usuario
         WHERE u.id_usuario = ?"
    );

    $consultaPerfil->bind_param("i", $id_usuario);
    $consultaPerfil->execute();

    $resultadoPerfil = $consultaPerfil->get_result();
    $enfermero = $resultadoPerfil->fetch_assoc();

    // Consultar las reservas asignadas a este enfermero
    $totalReservas = 0;
    $reservas = null;

    if ($enfermero) {
        $id_enfermero = (int) $enfermero["id_enfermero"];

        $consultaReservas = $conexion->prepare(
            "SELECT r.id_reserva, r.fecha, r.estado,
                    r.observaciones, u.nombre AS paciente,
                    s.nombre AS servicio
             FROM reservas r
             LEFT JOIN usuarios u ON r.id_usuario = u.id_usuario
             LEFT JOIN servicios s ON r.id_servicio = s.id_servicio
             WHERE r.id_enfermero = ?
             ORDER BY r.fecha DESC"
        );

        $consultaReservas->bind_param("i", $id_enfermero);
        $consultaReservas->execute();

        $reservas = $consultaReservas->get_result();
        $totalReservas = $reservas->num_rows;
    }

} catch (Throwable $error) {
    error_log($error->getMessage());
    http_response_code(500);
    exit("Ocurrió un error al cargar el panel. Revisa la estructura de las tablas de EnferPacient.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Panel del Enfermero - EnferPacient</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">

    <style>
        body {
            background-color: #E6F4FF;
        }

        .panel-contenido {
            max-width: 1100px;
            margin: 35px auto;
        }

        .tarjeta-panel {
            background: white;
            border-radius: 12px;
            padding: 22px;
            height: 100%;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.07);
        }

        .titulo-panel {
            color: #0B3D7A;
            font-weight: bold;
        }

        .boton-salir {
            background-color: #0B3D7A;
            color: white;
            text-decoration: none;
            padding: 9px 16px;
            border-radius: 7px;
        }

        .boton-salir:hover {
            background-color: #1EADF0;
            color: white;
        }
    </style>
</head>

<body>

<header class="encabezado py-3">
    <div class="container d-flex flex-wrap justify-content-between
                align-items-center gap-3">

        <a href="index.html">
            <img src="img/logo.png" alt="Logo EnferPacient"
                 class="mi-logo">
        </a>

        <div class="text-center">
            <h1 class="h3 fw-bold mb-1">EnferPacient</h1>
            <small>Panel del enfermero</small>
        </div>

        <a href="cerrar_sesion.php" class="boton-salir">
            Cerrar sesión
        </a>
    </div>
</header>

<main class="container panel-contenido">

    <section class="tarjeta-panel mb-4">
        <h2 class="titulo-panel">
            ¡Bienvenido, <?php echo htmlspecialchars($nombre); ?>!
        </h2>
        <p class="mb-0">
            Aquí puedes consultar tus datos y las reservas que tienes asignadas.
        </p>
    </section>

    <?php if (!$enfermero): ?>

        <div class="alert alert-warning">
            No se encontró un perfil de enfermero asociado a esta cuenta.
            Verifica que exista un registro en la tabla enfermeros con el
            mismo id_usuario.
        </div>

    <?php else: ?>

        <div class="row g-3 mb-4">

            <div class="col-md-4">
                <section class="tarjeta-panel">
                    <h5 class="titulo-panel">Mis reservas</h5>
                    <h2><?php echo $totalReservas; ?></h2>
                    <p class="text-muted mb-0">Reservas asignadas</p>
                </section>
            </div>

            <div class="col-md-4">
                <section class="tarjeta-panel">
                    <h5 class="titulo-panel">Mi especialidad</h5>
                    <p>
                        <?php echo htmlspecialchars(
                            $enfermero["especialidad"] ?? "Sin registrar"
                        ); ?>
                    </p>
                </section>
            </div>

            <div class="col-md-4">
                <section class="tarjeta-panel">
                    <h5 class="titulo-panel">Disponibilidad</h5>
                    <p>
                        <?php echo $enfermero["disponible"]
                            ? "Disponible"
                            : "No disponible"; ?>
                    </p>
                </section>
            </div>

        </div>

        <section class="tarjeta-panel mb-4">
            <h4 class="titulo-panel mb-3">Mi perfil</h4>

            <p>
                <strong>Nombre:</strong>
                <?php echo htmlspecialchars(
                    $enfermero["nombre"] . " " . $enfermero["apellido"]
                ); ?>
            </p>

            <p>
                <strong>Correo:</strong>
                <?php echo htmlspecialchars($enfermero["correo"]); ?>
            </p>

            <p>
                <strong>Teléfono:</strong>
                <?php echo htmlspecialchars(
                    $enfermero["telefono"] ?? "Sin registrar"
                ); ?>
            </p>
        </section>

        <section class="tarjeta-panel mb-4">
            <h4 class="titulo-panel mb-3">Mis reservas asignadas</h4>

            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>N.º</th>
                            <th>Paciente</th>
                            <th>Servicio</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($totalReservas > 0): ?>

                            <?php while ($reserva = $reservas->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo (int) $reserva["id_reserva"]; ?></td>
                                    <td><?php echo htmlspecialchars(
                                        $reserva["paciente"] ?? "Sin nombre"
                                    ); ?></td>
                                    <td><?php echo htmlspecialchars(
                                        $reserva["servicio"] ?? "Sin servicio"
                                    ); ?></td>
                                    <td><?php echo htmlspecialchars(
                                        $reserva["fecha"] ?? ""
                                    ); ?></td>
                                    <td><?php echo htmlspecialchars(
                                        $reserva["estado"] ?? ""
                                    ); ?></td>
                                </tr>
                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    No tienes reservas asignadas por el momento.
                                </td>
                            </tr>

                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

    <?php endif; ?>

</main>

<footer class="pie-pagina text-center py-3">
    <p class="m-0">© 2026 EnferPacient - La salud es la vida</p>
</footer>

</body>
</html>

<?php
if (isset($consultaPerfil)) {
    $consultaPerfil->close();
}

if (isset($consultaReservas)) {
    $consultaReservas->close();
}

if (isset($conexion)) {
    $conexion->close();
}
?>
