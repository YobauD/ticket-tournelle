<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;

/**
 * Page « Données personnelles » : information des personnes (RGPD, article 13).
 * Publique, pour pouvoir être lue avant la première connexion. Les durées affichées sont
 * celles de config.php (section 'retention'), celles qu'applique scripts/purge.php.
 */
final class PrivacyController extends Controller
{
    public function index(): void
    {
        $r = static fn (string $key): int => max(1, (int) Config::get('retention.' . $key));

        $this->render('privacy/index', [
            'title'      => t('privacy.title'),
            'controller' => (string) Config::get('privacy.controller'),
            'dpo'        => trim((string) Config::get('privacy.dpo_contact', '')),
            'retention'  => [
                'connection_logs'   => t('privacy.months', ['n' => $r('connection_logs_months')]),
                'audit_logs'        => t('privacy.months_ip', ['n' => $r('audit_logs_months'), 'ip' => $r('connection_logs_months')]),
                'closed_tickets'    => t('privacy.months_after_close', ['n' => $r('closed_tickets_months')]),
                'disabled_accounts' => t('privacy.months_after_disable', ['n' => $r('disabled_accounts_months')]),
                'password_requests' => t('privacy.months', ['n' => $r('password_requests_months')]),
                'email_tokens'      => t('privacy.days_after_use', ['n' => $r('email_tokens_days')]),
            ],
        ]);
    }
}
