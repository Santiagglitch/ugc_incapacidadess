<?php

declare(strict_types=1);

require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/controllers/authController.php';
require_once __DIR__ . '/controllers/dashboardController.php';
require_once __DIR__ . '/controllers/solicitudController.php';
require_once __DIR__ . '/controllers/adminController.php';
require_once __DIR__ . '/controllers/notificacionController.php';

iniciar_sesion_segura();
aplicar_headers_seguridad();

$action = $_GET['action'] ?? '';
$view = trim((string) ($_GET['view'] ?? 'login'), '/');
$method = strtoupper((string) ($_POST['_method'] ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET')));

if ($method === 'POST' || $method === 'PUT' || $method === 'DELETE') {
    switch ($action) {
        case 'login':
            (new authController())->loginPost();
            break;

        case 'logout':
            (new authController())->logout();
            break;

        case 'solicitud_create':
            (new solicitudController())->crearPost();
            break;

        case 'solicitud_update':
            (new solicitudController())->editarPost((int) ($_POST['id'] ?? 0));
            break;

        case 'solicitud_jefe':
            (new solicitudController())->gestionJefePost((int) ($_POST['id'] ?? 0));
            break;

        case 'solicitud_rrhh':
            (new solicitudController())->gestionRrhhPost((int) ($_POST['id'] ?? 0));
            break;

        case 'solicitud_delete':
            (new solicitudController())->eliminar((int) ($_POST['id'] ?? 0));
            break;

        case 'admin_hacer_admin':
            (new adminController())->hacerAdmin((string) ($_POST['nit'] ?? ''));
            break;

        case 'admin_quitar_admin':
            (new adminController())->quitarAdmin((string) ($_POST['nit'] ?? ''));
            break;

        case 'notif_read':
            (new notificacionController())->marcarLeida((int) ($_POST['id'] ?? 0));
            break;

        case 'notif_read_all':
            (new notificacionController())->marcarTodasLeidas();
            break;

        default:
            http_response_code(404);
            exit('Accion no encontrada.');
    }
}

switch ($view) {
    case '':
    case 'login':
        (new authController())->loginForm();
        break;

    case 'dashboard':
        (new dashboardController())->index();
        break;

    case 'solicitudes':
        (new solicitudController())->listar();
        break;

    case 'solicitud_crear':
        (new solicitudController())->crearForm();
        break;

    case 'solicitud_editar':
        (new solicitudController())->editarForm((int) ($_GET['id'] ?? 0));
        break;

    case 'solicitud_ver':
        (new solicitudController())->ver((int) ($_GET['id'] ?? 0));
        break;

    case 'solicitud_archivo':
        (new solicitudController())->servirArchivo((int) ($_GET['id'] ?? 0));
        break;

    case 'notif_count':
        (new notificacionController())->contador();
        break;

    case 'notif_list':
        (new notificacionController())->listar();
        break;

    case 'admin_empleados':
        (new adminController())->empleados();
        break;

    case 'admin_empleado':
        (new adminController())->empleadoDetalle((string) ($_GET['nit'] ?? ''));
        break;

    default:
        http_response_code(404);
        render_view('shared/404', ['view' => $view], 'auth');
        break;
}