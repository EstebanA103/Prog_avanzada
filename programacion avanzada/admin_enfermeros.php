
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

function limpiarDato($dato) {
    return htmlspecialchars((string)$dato, ENT_QUOTES, "UTF-8");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? "";

    if ($accion === "crear") {
        $id_usuario = (int)($_POST["id_usuario"] ?? 0);
        $apellido = trim($_POST["apellido"] ?? "");
        $telefono = trim($_POST["telefono"] ?? "");
        $especialidad = trim($_POST["especialidad"] ?? "");
        $disponible = isset($_POST["disponible"]) ? 1 : 0;

        if ($id_usuario <= 0 || $apellido === "") {
            $mensaje = "Selecciona un usuario y escribe el apellido.";
        } else {
            $consulta = $conexion->prepare(
                "INSERT INTO enfermeros
                (id_usuario, apellido, telefono, especialidad, disponible)
                VALUES (?, ?, ?, ?, ?)"
            );

            $consulta->bind_param(
                "isssi",
                $id_usuario,
                $apellido,
                $telefono,
                $especialidad,
                $disponible
            );

            if ($consulta->execute()) {
                $mensaje = "Enfermero registrado correctamente.";
            } else {
                $mensaje = $consulta->errno === 1062
                    ? "Este usuario ya está registrado como enfermero."
                    : "No se pudo registrar. Verifica que el usuario exista y que los datos sean correctos.";
            }

            $consulta->close();
        }
    }

    if ($accion === "editar") {
        $id = (int)($_POST["id_enfermero"] ?? 0);
        $apellido = trim($_POST["apellido"] ?? "");
        $telefono = trim($_POST["telefono"] ?? "");
        $especialidad = trim($_POST["especialidad"] ?? "");
        $disponible = isset($_POST["disponible"]) ? 1 : 0;

        if ($id <= 0 || $apellido === "") {
            $mensaje = "Revisa el apellido y los datos del enfermero.";
        } else {
            $consulta = $conexion->prepare(
                "UPDATE enfermeros
                 SET apellido = ?, telefono = ?,
                     especialidad = ?, disponible = ?
                 WHERE id_enfermero = ?"
            );

            $consulta->bind_param(
                "sssii",
                $apellido,
                $telefono,
                $especialidad,
                $disponible,
                $id
            );

            $mensaje = $consulta->execute()
                ? "Datos del enfermero actualizados."
                : "No se pudieron actualizar los datos.";

            $consulta->close();
        }
    }

    if ($accion === "eliminar") {
        $id = (int)($_POST["id_enfermero"] ?? 0);

        if ($id <= 0) {
            $mensaje = "El enfermero seleccionado no es válido.";
        } else {
            $consulta = $conexion->prepare(
                "DELETE FROM enfermeros WHERE id_enfermero = ?"
            );

            $consulta->bind_param("i", $id);

            if ($consulta->execute()) {
                $mensaje = $consulta->affected_rows > 0
                    ? "Enfermero eliminado correctamente."
                    : "No se encontró ese enfermero.";
            } else {
                $mensaje = "No se puede eliminar porque puede tener reservas relacionadas.";
            }

            $consulta->close();
        }
    }
}

$usuariosDisponibles = $conexion->query(
    "SELECT u.id_usuario, u.nombre, u.correo
     FROM usuarios u
     WHERE NOT EXISTS (
         SELECT 1
         FROM enfermeros e
         WHERE e.id_usuario = u.id_usuario
     )
     ORDER BY u.nombre"
);

$resultado = $conexion->query(
    "SELECT e.id_enfermero, e.id_usuario, u.nombre, u.correo,
            e.apellido, e.telefono, e.especialidad, e.disponible
     FROM enfermeros e
     INNER JOIN usuarios u ON e.id_usuario = u.id_usuario
     ORDER BY e.id_enfermero DESC"
);

if (!$resultado) {
    die("No se pudieron consultar los enfermeros. Revisa la relación entre usuarios y enfermeros.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar enfermeros - EnferPacient</title>

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
            min-width: 140px;
        }
    </style>
</head>

<body>

<header>
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h2>EnferPacient</h2>
            <p class="mb-0">Administración de enfermeros</p>
        </div>

        <a href="panel_administrador.php" class="btn btn-light">
            Volver al panel
        </a>
    </div>
</header>

<main class="contenedor">
    <h3>Gestión de enfermeros</h3>
    <p>Administra los datos y la disponibilidad de los enfermeros.</p>

    <?php if ($mensaje !== ""): ?>
        <div class="alert alert-info">
            <?php echo limpiarDato($mensaje); ?>
        </div>
    <?php endif; ?>

    <section class="tarjeta">
        <h4>Registrar enfermero</h4>

        <form method="POST">
            <input type="hidden" name="accion" value="crear">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Usuario registrado</label>
                    <select name="id_usuario" class="form-select" required>
                        <option value="">Selecciona un usuario</option>

                        <?php while ($u = $usuariosDisponibles->fetch_assoc()): ?>
                            <option value="<?php echo (int)$u["id_usuario"]; ?>">
                                <?php echo limpiarDato($u["nombre"]); ?>
                                — <?php echo limpiarDato($u["correo"]); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Apellido</label>
                    <input type="text" name="apellido" class="form-control" maxlength="100" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" class="form-control" maxlength="30">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Especialidad</label>
                    <input type="text" name="especialidad" class="form-control" maxlength="100">
                </div>

                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" name="disponible" class="form-check-input" id="disponibleNuevo" checked>
                        <label for="disponibleNuevo" class="form-check-label">
                            Disponible para reservas
                        </label>
                    </div>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-azul">
                        Registrar enfermero
                    </button>
                </div>
            </div>
        </form>
    </section>

    <section class="tarjeta">
        <h4>Enfermeros registrados</h4>

        <div class="tabla-contenedor">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Apellido</th>
                        <th>Teléfono</th>
                        <th>Especialidad</th>
                        <th>Disponibilidad</th>
                        <th>Editar</th>
                        <th>Eliminar</th>
                    </tr>
                </thead>

                <tbody>
                <?php while ($e = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo (int)$e["id_enfermero"]; ?></td>
                        <td>
                            <?php echo limpiarDato($e["nombre"]); ?>
                            <br>
                            <small><?php echo limpiarDato($e["correo"]); ?></small>
                        </td>

                        <td><?php echo limpiarDato($e["apellido"]); ?></td>
                        <td><?php echo limpiarDato($e["telefono"]); ?></td>
                        <td><?php echo limpiarDato($e["especialidad"]); ?></td>

                        <td>
                            <?php echo (int)$e["disponible"] === 1 ? "Disponible" : "No disponible"; ?>
                        </td>

                        <td>
                            <form method="POST" class="d-grid gap-2">
                                <input type="hidden" name="accion" value="editar">
                                <input type="hidden" name="id_enfermero" value="<?php echo (int)$e["id_enfermero"]; ?>">

                                <input
                                    type="text"
                                    name="apellido"
                                    class="form-control campo"
                                    value="<?php echo limpiarDato($e["apellido"]); ?>"
                                    placeholder="Apellido"
                                    maxlength="100"
                                    required
                                >

                                <input
                                    type="text"
                                    name="telefono"
                                    class="form-control campo"
                                    value="<?php echo limpiarDato($e["telefono"]); ?>"
                                    placeholder="Teléfono"
                                    maxlength="30"
                                >

                                <input
                                    type="text"
                                    name="especialidad"
                                    class="form-control campo"
                                    value="<?php echo limpiarDato($e["especialidad"]); ?>"
                                    placeholder="Especialidad"
                                    maxlength="100"
                                >

                                <div class="form-check">
                                    <input
                                        type="checkbox"
                                        name="disponible"
                                        class="form-check-input"
                                        id="disp<?php echo (int)$e["id_enfermero"]; ?>"
                                        <?php echo (int)$e["disponible"] === 1 ? "checked" : ""; ?>
                                    >

                                    <label class="form-check-label" for="disp<?php echo (int)$e["id_enfermero"]; ?>">
                                        Disponible
                                    </label>
                                </div>

                                <button type="submit" class="btn btn-azul">
                                    Guardar cambios
                                </button>
                            </form>
                        </td>

                        <td>
                            <form method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este enfermero?');">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id_enfermero" value="<?php echo (int)$e["id_enfermero"]; ?>">

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
