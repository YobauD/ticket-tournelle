<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Pagination;
use App\Models\Log;
use DateTime;

/**
 * Consultation du journal d'audit (contrainte C5).
 * Lecture seule : l'application ne propose aucun moyen de modifier ou de supprimer une ligne.
 */
final class AuditController extends Controller
{
    private const PER_PAGE = 50;

    public function index(): void
    {
        $actions = Log::actions();

        // Chaque filtre est contrôlé : action connue, recherche limitée, dates au format AAAA-MM-JJ
        $action  = $this->query('action');
        $filters = [
            'action' => in_array($action, $actions, true) ? $action : '',
            'q'      => mb_substr($this->query('q'), 0, 100),
            'from'   => $this->validDate($this->query('from')),
            'to'     => $this->validDate($this->query('to')),
        ];

        $pagination = new Pagination(Log::count($filters), self::PER_PAGE, $this->query('page', '1'));
        $rows       = Log::search($filters, $pagination->perPage, $pagination->offset());

        $this->render('admin/logs', [
            'title'      => t('nav.logs'),
            'rows'       => $rows,
            'targets'    => Log::targetLabels($rows),
            'actions'    => $actions,
            'filters'    => $filters,
            'pagination' => $pagination,
        ]);
    }

    /** Renvoie la date si elle est valide (AAAA-MM-JJ), une chaîne vide sinon. */
    private function validDate(string $value): string
    {
        $date = DateTime::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value ? $value : '';
    }
}
