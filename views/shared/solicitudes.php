<section class="page-header">
  <div>
    <h1 class="page-title"><?= e($titulo ?? 'Solicitudes') ?></h1>
    <p style="color:var(--muted)">Consulta de registros existentes y acciones disponibles segun tu rol.</p>
  </div>
</section>
<?php require __DIR__ . '/tabla_solicitudes.php'; ?>