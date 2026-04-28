<?php

declare(strict_types=1);

if (!function_exists('ugcPaginateRows')) {
    function ugcPaginateRows(array $rows, string $paramName = 'pagina', int $perPage = 8): array
    {
        $perPage = max(1, $perPage);
        $total = count($rows);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $currentPage = max(1, min($totalPages, (int) ($_GET[$paramName] ?? 1)));
        $offset = ($currentPage - 1) * $perPage;

        return [
            'rows' => array_slice($rows, $offset, $perPage),
            'current' => $currentPage,
            'totalPages' => $totalPages,
            'total' => $total,
            'perPage' => $perPage,
            'paramName' => $paramName,
        ];
    }
}

if (!function_exists('ugcBuildPageUrl')) {
    function ugcBuildPageUrl(string $paramName, int $page): string
    {
        $params = $_GET;
        $params[$paramName] = $page;
        return app_base_url('index.php') . '?' . http_build_query($params);
    }
}

if (!function_exists('ugcRenderPagination')) {
    function ugcRenderPagination(array $pagination, string $label = 'registros'): void
    {
        $totalPages = (int) ($pagination['totalPages'] ?? 1);
        if ($totalPages <= 1) {
            return;
        }

        $current = (int) ($pagination['current'] ?? 1);
        $total = (int) ($pagination['total'] ?? 0);
        $perPage = (int) ($pagination['perPage'] ?? 8);
        $paramName = (string) ($pagination['paramName'] ?? 'pagina');
        $start = max(2, min($current - 1, max(2, $totalPages - 3)));
        $end = min($totalPages, $start + 2);
        $firstShown = (($current - 1) * $perPage) + 1;
        $lastShown = min($total, $current * $perPage);
        ?>
        <nav class="ugc-pagination" aria-label="Paginacion de <?= e($label) ?>" style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin:18px 0;flex-wrap:wrap">
            <div class="ugc-pagination__meta" style="color:var(--muted);font-size:13px">
                <?= $firstShown ?>-<?= $lastShown ?> de <?= $total ?> <?= e($label) ?>
            </div>
            <div class="ugc-pagination__controls" style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                <?php if ($current > 1): ?>
                    <a class="btn btn-outline btn-sm" href="<?= e(ugcBuildPageUrl($paramName, $current - 1)) ?>">Anterior</a>
                <?php else: ?>
                    <span class="btn btn-gray btn-sm" style="opacity:.55">Anterior</span>
                <?php endif; ?>

                <?php if ($current === 1): ?>
                    <span class="btn btn-green btn-sm">1</span>
                <?php else: ?>
                    <a class="btn btn-outline btn-sm" href="<?= e(ugcBuildPageUrl($paramName, 1)) ?>">1</a>
                <?php endif; ?>

                <?php if ($start > 2): ?><span style="padding:0 4px;color:var(--muted)">...</span><?php endif; ?>

                <?php for ($page = $start; $page <= $end; $page++): ?>
                    <?php if ($page === $current): ?>
                        <span class="btn btn-green btn-sm"><?= $page ?></span>
                    <?php else: ?>
                        <a class="btn btn-outline btn-sm" href="<?= e(ugcBuildPageUrl($paramName, $page)) ?>"><?= $page ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($end < $totalPages): ?><span style="padding:0 4px;color:var(--muted)">...</span><?php endif; ?>

                <?php if ($current < $totalPages): ?>
                    <a class="btn btn-outline btn-sm" href="<?= e(ugcBuildPageUrl($paramName, $current + 1)) ?>">Siguiente</a>
                <?php else: ?>
                    <span class="btn btn-gray btn-sm" style="opacity:.55">Siguiente</span>
                <?php endif; ?>
            </div>
        </nav>
        <?php
    }
}