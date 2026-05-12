<?php

$estadoValue = strtoupper(trim((string)($estadoValue ?? '')));

$labels = [
    ESTADO_PENDIENTE_JEFE => 'Pendiente jefe',
    ESTADO_APROBADO_JEFE => 'Pendiente RRHH',
    ESTADO_RECHAZADO_JEFE => 'Rechazado jefe',
    ESTADO_APROBADO_RRHH => 'Aprobado RRHH',
    ESTADO_RECHAZADO_RRHH => 'Rechazado RRHH',
    ESTADO_CANCELADO_AGENDA => 'Cancelado usuario',
];

$class = 'badge-info';

if ($estadoValue === ESTADO_PENDIENTE_JEFE) {
    $class = 'badge-pendiente';
}

if ($estadoValue === ESTADO_APROBADO_JEFE) {
    $class = 'badge-rrhh';
}

if ($estadoValue === ESTADO_APROBADO_RRHH) {
    $class = 'badge-aprobado';
}

if (
    $estadoValue === ESTADO_RECHAZADO_JEFE ||
    $estadoValue === ESTADO_RECHAZADO_RRHH
) {
    $class = 'badge-rechazado';
}

if ($estadoValue === ESTADO_CANCELADO_AGENDA) {
    $class = 'badge-cancelado-agenda';
}

$textoEstado = $labels[$estadoValue] ?? ucwords(strtolower(str_replace('_', ' ', $estadoValue)));
?>

<span class="badge badge-estado <?= e($class) ?>">
    <?= e($textoEstado) ?>
</span>