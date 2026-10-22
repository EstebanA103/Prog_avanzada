
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

function limpiarReserva($dato) {
    return htmlspecialchars((string)$dato, ENT_QUOTES, "UTF-8");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? "";
    $id = (int)($_POST["id_reserva"] ?? 0);

    if ($accion === "editar" && $id > 0) {
        $estado = $_POST["estado"] ?? "";
        $estadosPermitidos = ["Pendiente", "Confirmada", "Cancelada", "Completada"];

        if (!in_array($estado, $estadosPermitidos, true)) {
            $mensaje = "El estado seleccionado no es válido.";
        } else {
            $consulta = $conexion->prepare(
                "UPDATE reservas SET estado = ? WHERE id_reserva = ?"
            );

            $consulta->bind_param("si", $estado, $id);

            $mensaje = $consulta->execute()
                ? "Estado de la reserva actualizado."
                : "No se pudo actualizar la reserva.";

            $consulta->close();
        }
    }

    if ($accion === "eliminar" && $id > 0) {
        $consulta = $conexion->prepare(
            "DELETE FROM reservas WHERE id_reserva = ?"
        );

        $consulta->bind_param("i", $id);

        if ($consulta->execute()) {
            $mensaje = $consulta->affected_rows > 0
                ? "Reserva eliminada correctamente."
                : "No se encontró la reserva.";
        } else {
            $mensaje = "No se pudo eliminar la reserva.";
        }

        $consulta->close();
    }
}

$resultado = $conexion->query(
    "SELECT * FROM reservas ORDER BY id_reserva DESC"
);

if (!$resultado) {
    die("No se pudieron consultar las reservas.");
}

$columnas = [];
while ($campo = $resultado->fetch_field()) {
    $columnas[] = $campo->name;
}
$resultado->data_seek(0);

if (!in_array("id_reserva", $columnas, true) ||
    !in_array("estado", $columnas, true)) {
    die("La tabla reservas debe tener las columnas id_reserva y estado.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar reservas - EnferPacient</title>

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
            max-width: 1200px;
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

        .tabla-contenedor {
            overflow-x: auto;
        }

        .btn-azul {
            background: #0B3D7A;
            color: white;
        }

        .btn-azul:hover {
            background: #1EADF0;
            color: white;
        }

        td, th {
            vertical-align: middle;
            min-width: 100px;
        }
    </style>
</head>

<body>

<header>
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h2>EnferPacient</h2>
            <p class="mb-0">Administración de reservas</p>
        </div>

        <a href="panel_administrador.php" class="btn btn-light">
            Volver al panel
        </a>
    </div>
</header>

<main class="contenedor">
    <h3>Gestión de reservas</h3>
    <p>Consulta las reservas y actualiza su estado.</p>

    <?php if ($mensaje !== ""): ?>
        <div class="alert alert-info">
            <?php echo limpiarReserva($mensaje); ?>
        </div>
    <?php endif; ?>

    <section class="tarjeta">
        <div class="tabla-contenedor">
            <table class="table table-bordered table-striped">
                <thead class="table-primary">
                    <tr>
                        <?php foreach ($columnas as $columna): ?>
                            <th><?php echo limpiarReserva($columna); ?></th>
                        <?php endforeach; ?>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                <?php while ($reserva = $resultado->fetch_assoc()): ?>
                    <tr>
                        <?php foreach ($columnas as $columna): ?>
                            <td>
                                <?php echo limpiarReserva($reserva[$columna] ?? ""); ?>
                            </td>
                        <?php endforeach; ?>

                        <td>
                            <form method="POST" class="d-grid gap-2 mb-2">
                                <input type="hidden" name="accion" value="editar">
                                <input type="hidden" name="id_reserva" value="<?php echo (int)$reserva["id_reserva"]; ?>">

                                <select name="estado" class="form-select" required>
                                    <?php
                                    $estados = ["Pendiente", "Confirmada", "Cancelada", "Completada"];
                                    foreach ($estados as $estado):
                                    ?>
                                        <option
                                            value="<?php echo limpiarReserva($estado); ?>"
                                            <?php echo ($reserva["estado"] ?? "") === $estado ? "selected" : ""; ?>
                                        >
                                            <?php echo limpiarReserva($estado); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <button type="submit" class="btn btn-azul">
                                    Guardar estado
                                </button>
                            </form>

                            <form method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar esta reserva?');">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id_reserva" value="<?php echo (int)$reserva["id_reserva"]; ?>">

                                <button type="submit" class="btn btn-danger">
                                    Eliminar reserva
                                </button>
                            </form>
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
