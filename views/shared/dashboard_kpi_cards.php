<?php
$dashboardKpiCards = $dashboardKpiCards ?? [];
$dashboardKpiActive = $dashboardKpiActive ?? ($historialTipo ?? '');
$dashboardKpiUrl = $dashboardKpiUrl ?? ($historialUrl ?? null);
$dashboardKpiRowClass = $dashboardKpiRowClass ?? '';
?>
<div class="dashboard-kpi-row animate-fade-up <?= e($dashboardKpiRowClass) ?>">
  <?php foreach ($dashboardKpiCards as $dashboardKpi):
    $tipo = (string)($dashboardKpi['tipo'] ?? '');
    $isActive = ($dashboardKpiActive === $tipo);
    $href = is_callable($dashboardKpiUrl) ? $dashboardKpiUrl($tipo) : '#';
    $color = (string)($dashboardKpi['color'] ?? 'var(--green)');
    $bg = (string)($dashboardKpi['bg'] ?? 'var(--green3)');
    $label = (string)($dashboardKpi['lbl'] ?? '');
    $value = $dashboardKpi['val'] ?? 0;
    $svg = (string)($dashboardKpi['svg'] ?? '');
  ?>
    <a href="<?= e($href) ?>" class="dashboard-kpi-card<?= $isActive ? ' is-active' : '' ?>" style="--stat-color:<?= e($color) ?>;--stat-bg:<?= e($bg) ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
      <div class="dashboard-kpi-top">
        <div class="dashboard-kpi-icon">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><?= $svg ?></svg>
        </div>
        <?php if ($isActive): ?>
          <span class="dashboard-kpi-badge">VIENDO</span>
        <?php endif; ?>
      </div>
      <div class="dashboard-kpi-body">
        <div class="dashboard-kpi-value"><?= e((string)$value) ?></div>
        <div class="dashboard-kpi-label"><?= e($label) ?></div>
      </div>
    </a>
  <?php endforeach; ?>
</div>
