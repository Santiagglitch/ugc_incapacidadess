<?php
$estadoValue = $estadoValue ?? '';
$labels = [
    ESTADO_PENDIENTE_JEFE => 'Pendiente Jefe',
    ESTADO_APROBADO_JEFE => 'Aprobado Jefe',
    ESTADO_RECHAZADO_JEFE => 'Rechazado Jefe',
    ESTADO_APROBADO_RRHH => 'Aprobado RRHH',
    ESTADO_RECHAZADO_RRHH => 'Rechazado RRHH',
];
$class = 'badge-info';
if (str_contains($estadoValue, 'PENDIENTE')) { $class = 'badge-pendiente'; }
if (str_contains($estadoValue, 'APROBADO')) { $class = 'badge-aprobado'; }
if (str_contains($estadoValue, 'RECHAZADO')) { $class = 'badge-rechazado'; }
?>
<span class="badge <?= e($class) ?>"><?= e($labels[$estadoValue] ?? $estadoValue) ?></span>