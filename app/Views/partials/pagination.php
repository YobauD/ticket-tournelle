<?php
/**
 * Navigation entre les pages d'une liste.
 * Variables : $pagination (App\Core\Pagination), $path (ex. '/queue'),
 *             $query (paramètres d'URL à conserver : filtres en cours).
 */
if ($pagination->pages <= 1) {
    return;
}

/** Adresse d'une page en gardant les filtres ; la page 1 n'a pas de paramètre. */
$pageUrl = static function (int $page) use ($path, $query): string {
    $params = array_filter($query, static fn ($value): bool => $value !== '' && $value !== null);
    if ($page > 1) {
        $params['page'] = $page;
    }
    return url($path) . ($params ? '?' . http_build_query($params) : '');
};
$current = $pagination->page;
?>
<nav class="pagination" aria-label="<?= e(t('pagination.label')) ?>">
    <p class="pagination-info">
        <?= e(t('pagination.range', ['from' => $pagination->from(), 'to' => $pagination->to(), 'total' => $pagination->total])) ?>
    </p>
    <ul>
        <li>
            <?php if ($current > 1): ?>
                <a href="<?= e($pageUrl($current - 1)) ?>" rel="prev"><?= e(t('pagination.previous')) ?></a>
            <?php else: ?>
                <span class="is-disabled"><?= e(t('pagination.previous')) ?></span>
            <?php endif; ?>
        </li>
        <?php foreach ($pagination->links() as $number): ?>
            <li>
                <?php if ($number === null): ?>
                    <span class="gap" aria-hidden="true">…</span>
                <?php elseif ($number === $current): ?>
                    <span aria-current="page"><?= $number ?></span>
                <?php else: ?>
                    <a href="<?= e($pageUrl($number)) ?>" aria-label="<?= e(t('pagination.page', ['page' => $number])) ?>"><?= $number ?></a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
        <li>
            <?php if ($current < $pagination->pages): ?>
                <a href="<?= e($pageUrl($current + 1)) ?>" rel="next"><?= e(t('pagination.next')) ?></a>
            <?php else: ?>
                <span class="is-disabled"><?= e(t('pagination.next')) ?></span>
            <?php endif; ?>
        </li>
    </ul>
</nav>
