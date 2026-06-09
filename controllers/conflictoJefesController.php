<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../models/mainModelUXXI.php';

class conflictoJefesController extends mainModelUXXI
{
    /**
     * Analiza conflictos entre jefes hardcodeados y RRHH
     */
    public function analizarConflictos(): array
    {
        $jefesHardcodeados = NITS_JEFES_REVISORES;
        $rrhhHardcodeados = CC_RRHH;
        
        // Encontrar conflictos directos (NITS que aparecen en ambos arrays)
        $conflictosDirectos = array_intersect($jefesHardcodeados, $rrhhHardcodeados);
        
        // Obtener jefes filtrados (excluyendo RRHH)
        $jefesFiltrados = get_jefes_revisores_filtrados();
        
        return [
            'analisis' => [
                'total_jefes_hardcodeados' => count($jefesHardcodeados),
                'total_rrhh_hardcodeados' => count($rrhhHardcodeados),
                'total_conflictos_directos' => count($conflictosDirectos),
                'total_jefes_filtrados' => count($jefesFiltrados),
                'fecha_analisis' => date('Y-m-d H:i:s')
            ],
            'conflictos_directos' => array_values($conflictosDirectos),
            'jefes_filtrados' => $jefesFiltrados,
            'rrhh_protegidos' => $rrhhHardcodeados,
            'estado' => empty($conflictosDirectos) ? 'SIN_CONFLICTO' : 'CON_CONFLICTO'
        ];
    }
    
    /**
     * Verifica si un NIT específico tiene conflicto
     */
    public function verificarConflictoPorNit(string $nit): array
    {
        $nitNormalizado = preg_replace('/\D+/', '', $nit);
        
        if ($nitNormalizado === '') {
            return [
                'nit' => $nit,
                'nit_normalizado' => '',
                'es_jefe' => false,
                'es_rrhh' => false,
                'tiene_conflicto' => false,
                'mensaje' => 'NIT inválido'
            ];
        }
        
        $esJefe = in_array($nitNormalizado, NITS_JEFES_REVISORES, true);
        $esRRHH = in_array($nitNormalizado, CC_RRHH, true);
        $tieneConflicto = $esJefe && $esRRHH;
        
        return [
            'nit' => $nit,
            'nit_normalizado' => $nitNormalizado,
            'es_jefe' => $esJefe,
            'es_rrhh' => $esRRHH,
            'tiene_conflicto' => $tieneConflicto,
            'rol_asignado' => $tieneConflicto ? 'CONFLICTO' : ($esRRHH ? 'RRHH' : ($esJefe ? 'JEFE' : 'EMPLEADO')),
            'mensaje' => $tieneConflicto ? 'El NIT tiene conflicto: aparece como jefe y RRHH' : 'Sin conflictos detectados'
        ];
    }
    
    /**
     * Obtiene lista de jefes validados (sin conflictos)
     */
    public function getJefesValidados(): array
    {
        $jefesFiltrados = get_jefes_revisores_filtrados();
        
        return [
            'jefes_validados' => $jefesFiltrados,
            'total' => count($jefesFiltrados),
            'mensaje' => 'Lista de jefes hardcodeados filtrados (excluyendo RRHH)',
            'fecha_generacion' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Genera reporte completo para descarga
     */
    public function generarReporteCompleto(): array
    {
        $analisis = $this->analizarConflictos();
        
        return [
            'resumen_ejecutivo' => [
                'titulo' => 'Reporte de Conflictos - Jefes Hardcodeados vs RRHH',
                'fecha_generacion' => date('Y-m-d H:i:s'),
                'sistema' => 'UGC Incapacidades',
                'version' => '1.0'
            ],
            'analisis_completo' => $analisis,
            'recomendaciones' => [
                'estado_actual' => $analisis['estado'],
                'accion_requerida' => $analisis['estado'] === 'CON_CONFLICTO' ? 'CORREGIR_CONFLICTOS' : 'SIN_ACCION_REQUERIDA',
                'recomendacion' => 'Usar siempre get_jefes_revisores_filtrados() para obtener jefes sin conflictos'
            ],
            'funciones_disponibles' => [
                'es_jefe_revisor_nit($nit)' => 'Valida si es jefe (excluye RRHH)',
                'get_jefes_revisores_filtrados()' => 'Retorna jefes sin conflictos'
            ]
        ];
    }
    
    /**
     * Endpoint para API JSON
     */
    public function apiAnalisis(): void
    {
        header('Content-Type: application/json');
        
        $accion = $_GET['accion'] ?? 'analisis_completo';
        
        switch ($accion) {
            case 'analisis_completo':
                echo json_encode($this->analizarConflictos(), JSON_PRETTY_PRINT);
                break;
                
            case 'verificar_nit':
                $nit = $_GET['nit'] ?? '';
                echo json_encode($this->verificarConflictoPorNit($nit), JSON_PRETTY_PRINT);
                break;
                
            case 'jefes_validados':
                echo json_encode($this->getJefesValidados(), JSON_PRETTY_PRINT);
                break;
                
            case 'reporte_completo':
                echo json_encode($this->generarReporteCompleto(), JSON_PRETTY_PRINT);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Acción no válida']);
        }
        exit;
    }
}

// Si se accede directamente al controlador, procesar API
if (basename($_SERVER['PHP_SELF']) === 'conflictoJefesController.php') {
    $controller = new conflictoJefesController();
    $controller->apiAnalisis();
}
