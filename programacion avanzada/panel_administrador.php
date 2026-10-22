


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

$nombre = htmlspecialchars($_SESSION["nombre"] ?? "Administrador");

$conexion = new mysqli(
    "localhost",
    "root",
    "",
    "reservas_enfermeria"
);

if ($conexion->connect_error) {
    die("Error al conectar con la base de datos.");
}

$conexion->set_charset("utf8mb4");

function contarRegistros($conexion, $tabla) {
    $tablasPermitidas = [
        "usuarios",
        "pacientes",
        "enfermeros",
        "administradores",
        "servicios",
        "reservas",
        "notificaciones"
    ];

    if (!in_array($tabla, $tablasPermitidas, true)) {
        return 0;
    }

    $resultado = $conexion->query("SELECT COUNT(*) AS total FROM `$tabla`");

    if (!$resultado) {
        return 0;
    }

    return (int) $resultado->fetch_assoc()["total"];
}

$usuarios = contarRegistros($conexion, "usuarios");
$pacientes = contarRegistros($conexion, "pacientes");
$enfermeros = contarRegistros($conexion, "enfermeros");
$reservas = contarRegistros($conexion, "reservas");
$servicios = contarRegistros($conexion, "servicios");
$administradores = contarRegistros($conexion, "administradores");
$notificaciones = contarRegistros($conexion, "notificaciones");

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrador - EnferPacient</title>

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

        .tarjeta {
            background: white;
            border-radius: 12px;
            padding: 22px;
            height: 100%;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
        }

        .total {
            font-size: 30px;
            font-weight: bold;
            color: #0B3D7A;
        }

        .boton {
            display: inline-block;
            background: #0B3D7A;
            color: white;
            padding: 9px 15px;
            border-radius: 7px;
            text-decoration: none;
            margin-top: 8px;
        }

        .boton:hover {
            background: #1EADF0;
            color: white;
        }

        .salir {
            color: white;
            text-decoration: none;
            border: 1px solid white;
            padding: 8px 13px;
            border-radius: 7px;
        }
    </style>
</head>

<body>

<header class="encabezado">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h2>EnferPacient</h2>
            <p class="mb-0">Panel de administración</p>
        </div>

        <div>
            <span class="me-3">Bienvenido, <?php echo $nombre; ?></span>
            <a href="cerrar_sesion.php" class="salir">Cerrar sesión</a>
        </div>
    </div>
</header>

<main class="contenedor">
    <h3>Resumen del sistema</h3>
    <p>Desde aquí podrás administrar la información de EnferPacient.</p>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="tarjeta">
                <h5>Usuarios</h5>
                <div class="total"><?php echo $usuarios; ?></div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="tarjeta">
                <h5>Pacientes</h5>
                <div class="total"><?php echo $pacientes; ?></div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="tarjeta">
                <h5>Enfermeros</h5>
                <div class="total"><?php echo $enfermeros; ?></div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="tarjeta">
                <h5>Reservas</h5>
                <div class="total"><?php echo $reservas; ?></div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="tarjeta">
                <h5>Servicios</h5>
                <div class="total"><?php echo $servicios; ?></div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="tarjeta">
                <h5>Notificaciones</h5>
                <div class="total"><?php echo $notificaciones; ?></div>
            </div>
        </div>
    </div>

    <h3 class="mb-3">Administrar EnferPacient</h3>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="tarjeta">
                <h5>Usuarios y pacientes</h5>
                <p>Consultar y gestionar las cuentas registradas.</p>
                <a href="admin_usuarios.php" class="boton">Administrar</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="tarjeta">
                <h5>Enfermeros</h5>
                <p>Gestionar información y disponibilidad.</p>
                <a href="admin_enfermeros.php" class="boton">Administrar</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="tarjeta">
                <h5>Reservas</h5>
                <p>Consultar y actualizar las reservas.</p>
                <a href="admin_reservas.php" class="boton">Administrar</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="tarjeta">
                <h5>Servicios</h5>
                <p>Gestionar los servicios de enfermería.</p>
                <a href="admin_servicios.php" class="boton">Administrar</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="tarjeta">
                <h5>Administradores</h5>
                <p>Consultar y gestionar cuentas administrativas.</p>
                <a href="admin_administradores.php" class="boton">Administrar</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="tarjeta">
                <h5>Notificaciones</h5>
                <p>Consultar y gestionar los avisos del sistema.</p>
                <a href="admin_notificaciones.php" class="boton">Administrar</a>
            </div>
        </div>
    </div>
</main>

</body>
</html>
