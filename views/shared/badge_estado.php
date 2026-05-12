<?php
$estadoValue = $estadoValue ?? '';
$labels = [
    ESTADO_PENDIENTE_JEFE => 'Pendiente Jefe',
    ESTADO_APROBADO_JEFE => 'Pendiente RRHH',
    ESTADO_RECHAZADO_JEFE => 'Rechazado Jefe',
    ESTADO_APROBADO_RRHH => 'Aprobado RRHH',
    ESTADO_RECHAZADO_RRHH => 'Rechazado RRHH',
    ESTADO_CANCELADO_AGENDA => 'Cancelado por usuario',
];
$class = 'badge-info';
if (str_contains($estadoValue, 'PENDIENTE')) { $class = 'badge-pendiente'; }
if (str_contains($estadoValue, 'APROBADO')) { $class = 'badge-aprobado'; }
if (str_contains($estadoValue, 'RECHAZADO')) { $class = 'badge-rechazado'; }
if ($estadoValue === ESTADO_APROBADO_JEFE) { $class = 'badge-rrhh'; }
if ($estadoValue === ESTADO_CANCELADO_AGENDA) { $class = 'badge-cancelado-agenda'; }
?>
<span class="badge <?= e($class) ?>"><?= e($labels[$estadoValue] ?? $estadoValue) ?></span>
