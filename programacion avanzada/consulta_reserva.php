
<?php
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

    // Recibir los datos del formulario
    $id_usuario = filter_input(
        INPUT_POST,
        "id_usuario",
        FILTER_VALIDATE_INT
    );

    $id_enfermero = trim($_POST["id_enfermero"] ?? "");

    if (!$id_usuario || $id_usuario < 1) {
        die("Ingresa un ID de usuario válido.");
    }

    // Consultar las reservas del usuario
    if ($id_enfermero !== "") {
        if (!ctype_digit($id_enfermero) || (int)$id_enfermero < 1) {
            die("El ID del enfermero no es válido.");
        }

        $id_enfermero = (int)$id_enfermero;

        $consulta = $conexion->prepare(
            "SELECT id_reserva, id_usuario, id_enfermero,
                    id_servicio, fecha, estado, observaciones,
                    fecha_registro
             FROM reservas
             WHERE id_usuario = ? AND id_enfermero = ?
             ORDER BY fecha DESC"
        );

        $consulta->bind_param(
            "ii",
            $id_usuario,
            $id_enfermero
        );

    } else {
        $consulta = $conexion->prepare(
            "SELECT id_reserva, id_usuario, id_enfermero,
                    id_servicio, fecha, estado, observaciones,
                    fecha_registro
             FROM reservas
             WHERE id_usuario = ?
             ORDER BY fecha DESC"
        );

        $consulta->bind_param("i", $id_usuario);
    }

    $consulta->execute();
    $resultado = $consulta->get_result();

} catch (Throwable $error) {
    error_log($error->getMessage());
    http_response_code(500);
    die("No se pudo consultar la reserva. Revisa la conexión y la estructura de la base de datos.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado de Reserva - EnferPacient</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

<header class="encabezado py-3 text-center">
    <a href="index.html">
        <img src="img/logo.png" alt="Logo EnferPacient" class="mi-logo">
    </a>
    <h1 class="h2 fw-bold">EnferPacient</h1>
    <small>La salud es la vida</small>
</header>

<div class="container my-5">
    <div class="caja-producto p-4">

        <h2 class="text-center mb-4">Resultados de tu reserva</h2>

        <?php if ($resultado->num_rows > 0): ?>

            <?php while ($reserva = $resultado->fetch_assoc()): ?>

                <div class="border rounded p-3 mb-3">

                    <h4>
                        Reserva #<?= (int)$reserva["id_reserva"] ?>
                    </h4>

                    <p>
                        <strong>ID de usuario:</strong>
                        <?= (int)$reserva["id_usuario"] ?>
                    </p>

                    <p>
                        <strong>ID del enfermero:</strong>
                        <?= (int)$reserva["id_enfermero"] ?>
                    </p>

                    <p>
                        <strong>ID del servicio:</strong>
                        <?= (int)$reserva["id_servicio"] ?>
                    </p>

                    <p>
                        <strong>Fecha:</strong>
                        <?= htmlspecialchars($reserva["fecha"] ?? "") ?>
                    </p>

                    <p>
                        <strong>Estado:</strong>
                        <?= htmlspecialchars($reserva["estado"] ?? "") ?>
                    </p>

                    <p>
                        <strong>Observaciones:</strong>
                        <?= htmlspecialchars($reserva["observaciones"] ?? "Sin observaciones") ?>
                    </p>

                    <p>
                        <strong>Fecha de registro:</strong>
                        <?= htmlspecialchars($reserva["fecha_registro"] ?? "") ?>
                    </p>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <p class="text-center">
                No encontramos reservas con los datos ingresados.
            </p>

        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="reserva.html" class="btn btn-primary">
                Volver a buscar
            </a>

            <a href="index.html" class="btn btn-secondary">
                Ir al inicio
            </a>
        </div>

    </div>
</div>

<footer class="pie-pagina text-center py-3">
    <p class="m-0">© 2026 EnferPacient - La salud es la vida</p>
</footer>

</body>
</html>

<?php
$consulta->close();
$conexion->close();
?>
