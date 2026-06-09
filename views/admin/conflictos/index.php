<?php
require_once __DIR__ . '/../../controllers/conflictoJefesController.php';

$controller = new conflictoJefesController();
$analisis = $controller->analizarConflictos();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Análisis de Conflictos - Jefes Hardcodeados vs RRHH</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .status-ok { color: #28a745; }
        .status-warning { color: #ffc107; }
        .status-error { color: #dc3545; }
        .card-stats { transition: transform 0.2s; }
        .card-stats:hover { transform: translateY(-2px); }
        .nit-item { font-family: monospace; background: #f8f9fa; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-exclamation-triangle"></i> Análisis de Conflictos - Jefes vs RRHH</h2>
                    <div>
                        <button class="btn btn-primary" onclick="recargarAnalisis()">
                            <i class="fas fa-sync-alt"></i> Recargar Análisis
                        </button>
                        <button class="btn btn-success" onclick="exportarReporte()">
                            <i class="fas fa-download"></i> Exportar Reporte
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjetas de Resumen -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card card-stats h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="card-category">Total Jefes Hardcodeados</h6>
                                <h3 class="card-title"><?php echo $analisis['analisis']['total_jefes_hardcodeados']; ?></h3>
                            </div>
                            <div class="ms-3">
                                <i class="fas fa-user-tie fa-2x text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card card-stats h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="card-category">Total RRHH</h6>
                                <h3 class="card-title"><?php echo $analisis['analisis']['total_rrhh_hardcodeados']; ?></h3>
                            </div>
                            <div class="ms-3">
                                <i class="fas fa-users-cog fa-2x text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card card-stats h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="card-category">Conflictos Directos</h6>
                                <h3 class="card-title">
                                    <span class="<?php echo $analisis['analisis']['total_conflictos_directos'] > 0 ? 'status-error' : 'status-ok'; ?>">
                                        <?php echo $analisis['analisis']['total_conflictos_directos']; ?>
                                    </span>
                                </h3>
                            </div>
                            <div class="ms-3">
                                <i class="fas fa-exclamation-triangle fa-2x <?php echo $analisis['analisis']['total_conflictos_directos'] > 0 ? 'text-danger' : 'text-success'; ?>"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card card-stats h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="card-category">Jefes Filtrados</h6>
                                <h3 class="card-title status-ok"><?php echo $analisis['analisis']['total_jefes_filtrados']; ?></h3>
                            </div>
                            <div class="ms-3">
                                <i class="fas fa-check-circle fa-2x text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estado General -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert <?php echo $analisis['estado'] === 'SIN_CONFLICTO' ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show" role="alert">
                    <h5><i class="fas fa-info-circle"></i> Estado del Sistema</h5>
                    <strong><?php echo $analisis['estado']; ?></strong> - 
                    <?php 
                    if ($analisis['estado'] === 'SIN_CONFLICTO') {
                        echo 'No se detectaron conflictos entre jefes hardcodeados y usuarios de RRHH.';
                    } else {
                        echo 'Se detectaron conflictos que requieren atención inmediata.';
                    }
                    ?>
                    <div class="mt-2">
                        <small>Último análisis: <?php echo $analisis['analisis']['fecha_analisis']; ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conflictos Detectados -->
        <?php if (!empty($analisis['conflictos_directos'])): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> NITS con Conflicto Directo</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-danger">Los siguientes NITS aparecen como jefe y RRHH simultáneamente:</p>
                        <div class="row">
                            <?php foreach ($analisis['conflictos_directos'] as $nit): ?>
                            <div class="col-md-3 mb-2">
                                <span class="nit-item"><?php echo $nit; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Jefes Filtrados (Sin Conflictos) -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-check-circle"></i> Jefes Validados (Sin Conflictos)</h5>
                    </div>
                    <div class="card-body">
                        <p>Estos son los NITS de jefes hardcodeados que han sido filtrados excluyendo los de RRHH:</p>
                        <div class="row">
                            <?php foreach ($analisis['jefes_filtrados'] as $nit): ?>
                            <div class="col-md-2 mb-2">
                                <span class="nit-item"><?php echo $nit; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted">Total: <?php echo count($analisis['jefes_filtrados']); ?> jefes validados</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RRHH Protegidos -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-shield-alt"></i> Usuarios RRHH Protegidos</h5>
                    </div>
                    <div class="card-body">
                        <p>Estos NITS están protegidos y nunca serán considerados como jefes:</p>
                        <div class="row">
                            <?php foreach ($analisis['rrhh_protegidos'] as $nit): ?>
                            <div class="col-md-3 mb-2">
                                <span class="nit-item"><?php echo $nit; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Verificación Individual -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-search"></i> Verificación Individual de NIT</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="text" id="nitVerificar" class="form-control" placeholder="Ingrese NIT para verificar">
                                    <button class="btn btn-primary" onclick="verificarNit()">
                                        <i class="fas fa-search"></i> Verificar
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div id="resultadoVerificacion" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function recargarAnalisis() {
            location.reload();
        }

        function exportarReporte() {
            window.open('conflictoJefesController.php?accion=reporte_completo', '_blank');
        }

        function verificarNit() {
            const nit = document.getElementById('nitVerificar').value.trim();
            if (!nit) {
                alert('Por favor ingrese un NIT');
                return;
            }

            fetch(`conflictoJefesController.php?accion=verificar_nit&nit=${encodeURIComponent(nit)}`)
                .then(response => response.json())
                .then(data => {
                    const resultadoDiv = document.getElementById('resultadoVerificacion');
                    const alertClass = data.tiene_conflicto ? 'alert-danger' : 'alert-success';
                    const icon = data.tiene_conflicto ? 'fas fa-exclamation-triangle' : 'fas fa-check-circle';
                    
                    resultadoDiv.innerHTML = `
                        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                            <h6><i class="${icon}"></i> Resultado para NIT: ${data.nit}</h6>
                            <div class="row">
                                <div class="col-md-3"><strong>NIT Normalizado:</strong> ${data.nit_normalizado}</div>
                                <div class="col-md-3"><strong>Es Jefe:</strong> ${data.es_jefe ? 'Sí' : 'No'}</div>
                                <div class="col-md-3"><strong>Es RRHH:</strong> ${data.es_rrhh ? 'Sí' : 'No'}</div>
                                <div class="col-md-3"><strong>Conflicto:</strong> ${data.tiene_conflicto ? 'Sí' : 'No'}</div>
                            </div>
                            <div class="mt-2">
                                <strong>Rol Asignado:</strong> ${data.rol_asignado}<br>
                                <strong>Mensaje:</strong> ${data.mensaje}
                            </div>
                        </div>
                    `;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('resultadoVerificacion').innerHTML = `
                        <div class="alert alert-danger" role="alert">
                            Error al verificar el NIT. Intente nuevamente.
                        </div>
                    `;
                });
        }

        // Permitir verificar con Enter
        document.getElementById('nitVerificar').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                verificarNit();
            }
        });
    </script>
</body>
</html>
