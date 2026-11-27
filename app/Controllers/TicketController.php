<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Core\View;
use App\Models\Attachment;
use App\Models\Category;
use App\Models\Comment;
use App\Models\History;
use App\Models\Location;
use App\Models\Log;
use App\Models\Priority;
use App\Models\Status;
use App\Models\Ticket;
use App\Services\Notifier;
use DomainException;
use RuntimeException;

/**
 * Espace déclarant : déclarer un incident, suivre ses tickets, échanger avec le support.
 */
final class TicketController extends Controller
{
    /** Tableau de bord personnel : mes tickets et leur statut. */
    public function index(): void
    {
        $scope = $this->query('scope', 'all');
        if (!in_array($scope, ['all', 'active', 'done'], true)) {
            $scope = 'all';
        }

        $this->render('tickets/index', [
            'title'   => t('nav.my_tickets'),
            'scope'   => $scope,
            'tickets' => Ticket::forUser(Auth::id(), $scope),
            'counts'  => Ticket::countsForUser(Auth::id()),
        ]);
    }

    /**
     * Formulaire de déclaration. Les liens de l'aide peuvent présélectionner une catégorie
     * et une priorité (?category=4&priority=high) : chaque valeur est vérifiée, une valeur
     * inconnue ou désactivée est simplement ignorée.
     */
    public function create(): void
    {
        $old = [];

        $category = (int) $this->query('category');
        if ($category > 0 && Category::isActive($category)) {
            $old['category_id'] = $category;
        }

        $priorities = array_column(Priority::all(), 'id', 'code');
        $priority   = $this->query('priority');
        if (isset($priorities[$priority])) {
            $old['priority_id'] = (int) $priorities[$priority];
        }

        $this->renderForm($old, []);
    }

    /** Enregistre un nouveau ticket après validation complète côté serveur. */
    public function store(): void
    {
        $old = [
            'title'       => $this->input('title'),
            'description' => $this->input('description'),
            'category_id' => (int) $this->input('category_id'),
            'location'    => Location::clean($this->input('location')),
            'priority_id' => (int) $this->input('priority_id'),
        ];
        $errors = [];

        // La validation JavaScript n'est qu'un confort : seul ce contrôle fait foi
        $titleLength = mb_strlen($old['title']);
        if ($titleLength < 5 || $titleLength > 150) {
            $errors['title'] = t('ticket.error.title');
        }
        $descriptionLength = mb_strlen($old['description']);
        if ($descriptionLength < 10 || $descriptionLength > 5000) {
            $errors['description'] = t('ticket.error.description');
        }
        if (!Category::isActive($old['category_id'])) {
            $errors['category_id'] = t('ticket.error.category');
        }
        // Lieu saisi librement : longueur, au moins une lettre ou un chiffre, et pas un lieu désactivé
        $locationLength = mb_strlen($old['location']);
        if ($locationLength < Location::MIN_LENGTH || $locationLength > Location::MAX_LENGTH
            || Location::key($old['location']) === '') {
            $errors['location'] = t('ticket.error.location');
        } elseif (($known = Location::match($old['location'])) !== null && (int) $known['is_active'] !== 1) {
            $errors['location'] = t('ticket.error.location_disabled');
        }
        if (!Priority::exists($old['priority_id'])) {
            $errors['priority_id'] = t('ticket.error.priority');
        }

        $file     = $_FILES['attachment'] ?? null;
        $fileInfo = null;
        try {
            $fileInfo = Attachment::validate($file);
        } catch (RuntimeException $e) {
            $errors['attachment'] = t($e->getMessage());
        }

        if ($errors) {
            http_response_code(422);
            $this->renderForm($old, $errors);
            return;
        }

        // Lieu existant (« salle a102 » retrouve « A102 »), ou nouveau lieu « à vérifier »
        $location = Location::resolve($old['location']);
        if ($location['created']) {
            Log::write('location_proposed', 'location:' . $location['id'], $location['name']);
        }

        $id = Ticket::create(
            Auth::id(),
            $old['category_id'],
            $location['id'],
            $old['priority_id'],
            $old['title'],
            $old['description']
        );

        if ($fileInfo !== null) {
            try {
                Attachment::store($file, $fileInfo[0], $fileInfo[1], $id, Auth::id());
            } catch (RuntimeException $e) {
                Session::flash('error', t($e->getMessage()));
            }
        }

        $ticket = Ticket::find($id);
        Notifier::declarant($ticket, 'created');
        Notifier::support($ticket);

        Session::flash('success', t('ticket.created', ['ref' => $ticket['reference']]));
        if ($location['created']) {
            Session::flash('info', t('ticket.location_new', ['name' => $location['name']]));
        }
        $this->redirect('/tickets/' . $id);
    }

    /** Détail d'un ticket, commun au déclarant et aux techniciens (les actions affichées diffèrent). */
    public function show(int $id): void
    {
        $ticket = $this->findVisible($id);
        $user   = Auth::user();

        $this->render('tickets/show', [
            'title'           => $ticket['reference'],
            'ticket'          => $ticket,
            'comments'        => Comment::forTicket($id, Auth::isStaff()),
            'history'         => History::forTicket($id),
            'attachments'     => Attachment::forTicket($id),
            'statuses'        => Status::all(),
            'isOwner'         => (int) $ticket['user_id'] === (int) $user['id'],
            'isStaff'         => Auth::isStaff(),
            'canTake'         => Ticket::canTake($ticket, $user),
            'canChangeStatus' => Ticket::canChangeStatus($ticket, $user),
            'targets'         => Ticket::staffTargets($ticket),
        ]);
    }

    /** Ajoute un message. Si le déclarant répond à un ticket « En attente », le ticket repart « En cours ». */
    public function comment(int $id): void
    {
        $ticket = $this->findVisible($id);
        $body   = $this->input('body');

        if ((int) $ticket['is_final'] === 1) {
            View::abort(403);
        }
        if ($body === '' || mb_strlen($body) > 5000) {
            Session::flash('error', t('comment.error'));
            $this->redirect('/tickets/' . $id);
        }

        $isOwner    = (int) $ticket['user_id'] === Auth::id();
        $isInternal = Auth::isStaff() && $this->input('is_internal') === '1'; // option ignorée pour un déclarant
        Comment::add($id, Auth::id(), $body, $isInternal);

        if ($isOwner) {
            if ($ticket['status_code'] === 'pending') {
                try {
                    Ticket::changeStatus($ticket, 'in_progress', Auth::id());
                } catch (DomainException) {
                    // Le technicien a changé le statut au même moment : le message reste enregistré
                }
            }
            Notifier::technician($ticket, 'reply', $body);
        } elseif (!$isInternal) {
            Notifier::declarant($ticket, 'comment', $body);
        }

        Session::flash('success', t('comment.added'));
        $this->redirect('/tickets/' . $id);
    }

    /** Le déclarant confirme la résolution : le ticket est clôturé. */
    public function close(int $id): void
    {
        $ticket = $this->findOwnResolved($id);
        try {
            Ticket::changeStatus($ticket, 'closed', Auth::id());
            Notifier::technician($ticket, 'confirmed');
            Session::flash('success', t('ticket.closed'));
        } catch (DomainException) {
            Session::flash('error', t('ticket.error.changed'));
        }
        $this->redirect('/tickets/' . $id);
    }

    /** Le déclarant signale que le problème persiste : le ticket repart « En cours ». */
    public function reopen(int $id): void
    {
        $ticket = $this->findOwnResolved($id);
        $reason = $this->input('body');

        if ($reason === '' || mb_strlen($reason) > 5000) {
            Session::flash('error', t('ticket.error.reopen_reason'));
            $this->redirect('/tickets/' . $id);
        }

        try {
            Ticket::changeStatus($ticket, 'in_progress', Auth::id());
            Comment::add($id, Auth::id(), $reason);
            Notifier::technician($ticket, 'reopened', $reason);
            Session::flash('success', t('ticket.reopened'));
        } catch (DomainException) {
            Session::flash('error', t('ticket.error.changed'));
        }
        $this->redirect('/tickets/' . $id);
    }

    /** Téléchargement d'une pièce jointe, après contrôle du droit d'accès au ticket. */
    public function attachment(int $id): void
    {
        $attachment = Attachment::find($id) ?? View::abort(404);
        $this->findVisible((int) $attachment['ticket_id']);

        $path = Attachment::path($attachment);
        if (!is_file($path)) {
            View::abort(404);
        }

        // Nom de fichier réduit à des caractères sûrs ; « attachment » force le téléchargement
        $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $attachment['original_name']);
        header('Content-Type: ' . $attachment['mime_type']);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: attachment; filename="' . $name . '"');
        readfile($path);
        exit;
    }

    // ------------------------------------------------------------------

    /** Charge un ticket ou interrompt : 404 s'il n'existe pas, 403 si l'utilisateur n'y a pas droit. */
    private function findVisible(int $id): array
    {
        $ticket = Ticket::find($id) ?? View::abort(404);
        if (!Ticket::canView($ticket, Auth::user())) {
            // Ticket d'un autre déclarant (adresse modifiée à la main : IDOR) : refus journalisé
            Log::write('permission_denied', 'ticket:' . $id);
            View::abort(403);
        }
        return $ticket;
    }

    /** Ticket « Résolu » appartenant à l'utilisateur connecté (seul cas où il peut clôturer ou rouvrir). */
    private function findOwnResolved(int $id): array
    {
        $ticket = $this->findVisible($id);
        if ((int) $ticket['user_id'] !== Auth::id() || $ticket['status_code'] !== 'resolved') {
            View::abort(403);
        }
        return $ticket;
    }

    private function renderForm(array $old, array $errors): void
    {
        $this->render('tickets/create', [
            'title'      => t('ticket.new'),
            'categories' => Category::active(),
            'locations'  => Location::suggestions(),
            'priorities' => Priority::all(),
            'old'        => $old,
            'errors'     => $errors,
        ]);
    }
}
