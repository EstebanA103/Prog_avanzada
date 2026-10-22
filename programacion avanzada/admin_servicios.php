
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

function limpiarServicio($dato) {
    return htmlspecialchars((string)$dato, ENT_QUOTES, "UTF-8");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? "";

    if ($accion === "crear" || $accion === "editar") {
        $nombre = trim($_POST["nombre"] ?? "");
        $descripcion = trim($_POST["descripcion"] ?? "");
        $precio = $_POST["precio"] ?? "";
        $unidad = trim($_POST["unidad_cobro"] ?? "");
        $estado = $_POST["estado"] ?? "Activo";

        if (
            $nombre === "" ||
            !is_numeric($precio) ||
            (float)$precio < 0 ||
            !in_array($estado, ["Activo", "Inactivo"], true)
        ) {
            $mensaje = "Verifica el nombre, el precio y el estado.";
        } else {
            $precio = (float)$precio;

            if ($accion === "crear") {
                $consulta = $conexion->prepare(
                    "INSERT INTO servicios
                    (nombre, descripcion, precio, unidad_cobro, estado)
                    VALUES (?, ?, ?, ?, ?)"
                );

                $consulta->bind_param(
                    "ssdss",
                    $nombre,
                    $descripcion,
                    $precio,
                    $unidad,
                    $estado
                );

                if ($consulta->execute()) {
                    $mensaje = "Servicio registrado correctamente.";
                } else {
                    $mensaje = $consulta->errno === 1062
                        ? "Ya existe un servicio con ese nombre."
                        : "No se pudo registrar el servicio.";
                }

                $consulta->close();
            } else {
                $id = (int)($_POST["id_servicio"] ?? 0);

                if ($id <= 0) {
                    $mensaje = "El servicio seleccionado no es válido.";
                } else {
                    $consulta = $conexion->prepare(
                        "UPDATE servicios
                         SET nombre = ?, descripcion = ?, precio = ?,
                             unidad_cobro = ?, estado = ?
                         WHERE id_servicio = ?"
                    );

                    $consulta->bind_param(
                        "ssdssi",
                        $nombre,
                        $descripcion,
                        $precio,
                        $unidad,
                        $estado,
                        $id
                    );

                    if ($consulta->execute()) {
                        $mensaje = "Servicio actualizado correctamente.";
                    } else {
                        $mensaje = $consulta->errno === 1062
                            ? "Ya existe otro servicio con ese nombre."
                            : "No se pudo actualizar el servicio.";
                    }

                    $consulta->close();
                }
            }
        }
    }

    if ($accion === "eliminar") {
        $id = (int)($_POST["id_servicio"] ?? 0);

        if ($id <= 0) {
            $mensaje = "El servicio seleccionado no es válido.";
        } else {
            $consulta = $conexion->prepare(
                "DELETE FROM servicios WHERE id_servicio = ?"
            );

            $consulta->bind_param("i", $id);

            if ($consulta->execute()) {
                $mensaje = $consulta->affected_rows > 0
                    ? "Servicio eliminado correctamente."
                    : "No se encontró ese servicio.";
            } else {
                $mensaje = "No se puede eliminar porque puede estar relacionado con otras tablas. Puedes marcarlo como Inactivo.";
            }

            $consulta->close();
        }
    }
}

$resultado = $conexion->query(
    "SELECT id_servicio, nombre, descripcion, precio, unidad_cobro, estado
     FROM servicios
     ORDER BY id_servicio DESC"
);

if (!$resultado) {
    die("No se pudieron consultar los servicios.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Administrar servicios - EnferPacient</title>

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
            max-width: 1250px;
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
            min-width: 130px;
        }
    </style>
</head>

<body>

<header>
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h2>EnferPacient</h2>
            <p class="mb-0">Administración de servicios</p>
        </div>

        <a href="panel_administrador.php" class="btn btn-light">
            Volver al panel
        </a>
    </div>
</header>

<main class="contenedor">
    <h3>Gestión de servicios de enfermería</h3>
    <p>Registra los servicios, administra sus precios y controla cuáles están activos.</p>

    <?php if ($mensaje !== ""): ?>
        <div class="alert alert-info">
            <?php echo limpiarServicio($mensaje); ?>
        </div>
    <?php endif; ?>

    <section class="tarjeta">
        <h4>Registrar servicio</h4>

        <form method="POST">
            <input type="hidden" name="accion" value="crear">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre del servicio</label>
                    <input type="text" name="nombre" class="form-control" maxlength="120" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Precio (COP)</label>
                    <input type="number" name="precio" class="form-control" min="0" step="0.01" value="0.00" required>
                </div>

                <div class="col-md-12">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" class="form-control" maxlength="255" rows="2"></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Unidad de cobro</label>
                    <input type="text" name="unidad_cobro" class="form-control" maxlength="30" placeholder="Ej.: Por hora, por visita">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select" required>
                        <option value="Activo">Activo</option>
                        <option value="Inactivo">Inactivo</option>
                    </select>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-azul">
                        Registrar servicio
                    </button>
                </div>
            </div>
        </form>
    </section>

    <section class="tarjeta">
        <h4>Servicios registrados</h4>

        <div class="tabla-contenedor">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Precio</th>
                        <th>Unidad de cobro</th>
                        <th>Estado</th>
                        <th>Editar</th>
                        <th>Eliminar</th>
                    </tr>
                </thead>

                <tbody>
                <?php while ($servicio = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo (int)$servicio["id_servicio"]; ?></td>
                        <td><?php echo limpiarServicio($servicio["nombre"]); ?></td>
                        <td><?php echo limpiarServicio($servicio["descripcion"]); ?></td>
                        <td>$<?php echo number_format((float)$servicio["precio"], 2, ",", "."); ?></td>
                        <td><?php echo limpiarServicio($servicio["unidad_cobro"]); ?></td>
                        <td><?php echo limpiarServicio($servicio["estado"]); ?></td>

                        <td>
                            <form method="POST" class="d-grid gap-2">
                                <input type="hidden" name="accion" value="editar">
                                <input type="hidden" name="id_servicio" value="<?php echo (int)$servicio["id_servicio"]; ?>">

                                <input
                                    type="text"
                                    name="nombre"
                                    class="form-control campo"
                                    value="<?php echo limpiarServicio($servicio["nombre"]); ?>"
                                    maxlength="120"
                                    placeholder="Nombre"
                                    required
                                >

                                <textarea
                                    name="descripcion"
                                    class="form-control campo"
                                    maxlength="255"
                                    rows="2"
                                    placeholder="Descripción"
                                ><?php echo limpiarServicio($servicio["descripcion"]); ?></textarea>

                                <input
                                    type="number"
                                    name="precio"
                                    class="form-control campo"
                                    value="<?php echo limpiarServicio($servicio["precio"]); ?>"
                                    min="0"
                                    step="0.01"
                                    required
                                >

                                <input
                                    type="text"
                                    name="unidad_cobro"
                                    class="form-control campo"
                                    value="<?php echo limpiarServicio($servicio["unidad_cobro"]); ?>"
                                    maxlength="30"
                                    placeholder="Unidad de cobro"
                                >

                                <select name="estado" class="form-select">
                                    <option value="Activo" <?php echo $servicio["estado"] === "Activo" ? "selected" : ""; ?>>
                                        Activo
                                    </option>
                                    <option value="Inactivo" <?php echo $servicio["estado"] === "Inactivo" ? "selected" : ""; ?>>
                                        Inactivo
                                    </option>
                                </select>

                                <button type="submit" class="btn btn-azul">
                                    Guardar cambios
                                </button>
                            </form>
                        </td>

                        <td>
                            <form method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este servicio?');">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id_servicio" value="<?php echo (int)$servicio["id_servicio"]; ?>">

                                <button type="submit" class="btn btn-danger">
                                    Eliminar
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
