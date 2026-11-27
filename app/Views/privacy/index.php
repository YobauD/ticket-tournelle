<?php
/**
 * Données personnelles (RGPD, article 13).
 * Variables : $controller (responsable du traitement), $dpo (contact du DPD, peut être vide),
 *             $retention (règle => durée lisible, d'après config.php).
 */
$email = filter_var($dpo, FILTER_VALIDATE_EMAIL) !== false;
?>
<div class="privacy">
<div class="page-head">
    <div>
        <h1><?= e(t('privacy.title')) ?></h1>
        <p class="head-meta"><?= e(t('privacy.intro')) ?></p>
    </div>
</div>

    <section class="card">
        <h2><?= e(t('privacy.controller_title')) ?></h2>
        <p><?= e(t('privacy.controller_text', ['controller' => $controller])) ?></p>
    </section>

    <section class="card">
        <h2><?= e(t('privacy.purpose_title')) ?></h2>
        <p><?= e(t('privacy.purpose_text')) ?></p>
    </section>

    <section class="card">
        <h2><?= e(t('privacy.data_title')) ?></h2>
        <ul>
            <?php foreach (['identity', 'email', 'password', 'tickets', 'logs'] as $item): ?>
                <li><?= e(t('privacy.data_' . $item)) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="card">
        <h2><?= e(t('privacy.recipients_title')) ?></h2>
        <p><?= e(t('privacy.recipients_text')) ?></p>
    </section>

    <section class="card">
        <h2><?= e(t('privacy.retention_title')) ?></h2>
        <p><?= e(t('privacy.retention_intro')) ?></p>
        <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col"><?= e(t('privacy.retention_data')) ?></th>
                    <th scope="col"><?= e(t('privacy.retention_duration')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($retention as $rule => $duration): ?>
                    <tr>
                        <td><?= e(t('privacy.r_' . $rule)) ?></td>
                        <td><?= e($duration) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </section>

    <section class="card">
        <h2><?= e(t('privacy.rights_title')) ?></h2>
        <p><?= e(t('privacy.rights_text')) ?></p>
        <p>
            <?php if ($dpo === ''): ?>
                <?= e(t('privacy.contact_school')) ?>
            <?php elseif ($email): ?>
                <?= str_replace(e($dpo), '<a href="mailto:' . e($dpo) . '">' . e($dpo) . '</a>', e(t('privacy.contact_dpo', ['contact' => $dpo]))) ?>
            <?php else: ?>
                <?= e(t('privacy.contact_dpo', ['contact' => $dpo])) ?>
            <?php endif; ?>
        </p>
        <p><?= e(t('privacy.cnil')) ?></p>
    </section>

    <section class="card">
        <h2><?= e(t('privacy.cookies_title')) ?></h2>
        <p><?= e(t('privacy.cookies_text')) ?></p>
    </section>
</div>
