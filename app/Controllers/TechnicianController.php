<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Pagination;
use App\Core\Session;
use App\Core\View;
use App\Models\Comment;
use App\Models\Location;
use App\Models\Priority;
use App\Models\Status;
use App\Models\Ticket;
use App\Services\Notifier;
use DomainException;

/**
 * Espace technicien / référent numérique : file d'attente et traitement des tickets.
 * Le routeur garantit déjà le rôle (technician ou admin) ; chaque action revérifie
 * en plus le droit sur LE ticket concerné (niveau de support, affectation).
 */
final class TechnicianController extends Controller
{
    /** Tickets affichés par page dans la file d'attente. */
    private const PER_PAGE = 25;

    /** File d'attente globale, filtrable par statut, priorité, lieu, niveau et affectation. */
    public function queue(): void
    {
        $filters = [
            'status'   => $this->query('status', 'open'),
            'priority' => $this->query('priority'),
            'location' => mb_substr($this->query('location'), 0, 80),
            'level'    => $this->query('level'),
            'scope'    => $this->query('scope', 'all'),
        ];

        $pagination = new Pagination(Ticket::countQueue($filters, Auth::id()), self::PER_PAGE, $this->query('page', '1'));

        $this->render('technician/queue', [
            'title'      => t('nav.queue'),
            'filters'    => $filters,
            'pagination' => $pagination,
            'tickets'    => Ticket::queue($filters, Auth::id(), $pagination->perPage, $pagination->offset()),
            'statuses'   => Status::all(),
            'priorities' => Priority::all(),
            'locations'  => Location::suggestions(),
        ]);
    }

    /** Le technicien s'affecte le ticket (prise en charge ou reprise à un collègue). */
    public function take(int $id): void
    {
        $ticket = Ticket::find($id) ?? View::abort(404);
        if (!Ticket::canTake($ticket, Auth::user())) {
            View::abort(403);
        }

        try {
            Ticket::take($ticket, Auth::id());
            Notifier::declarant(Ticket::find($id), 'taken');
            Session::flash('success', t('tech.taken', ['ref' => $ticket['reference']]));
        } catch (DomainException) {
            Session::flash('error', t('ticket.error.changed'));
        }
        $this->redirect('/tickets/' . $id);
    }

    /** Changement de statut, avec un message au déclarant (obligatoire pour En attente, Résolu, Rejeté). */
    public function status(int $id): void
    {
        $ticket = Ticket::find($id) ?? View::abort(404);
        $target = $this->input('status');
        $note   = $this->input('note');

        // La liste des statuts possibles est recalculée ici : on ne fait pas confiance au formulaire
        if (!Ticket::canChangeStatus($ticket, Auth::user())
            || !in_array($target, Ticket::staffTargets($ticket), true)) {
            View::abort(403);
        }
        if (mb_strlen($note) > 5000 || ($note === '' && in_array($target, Ticket::NOTE_REQUIRED, true))) {
            Session::flash('error', t('tech.error.note_required'));
            $this->redirect('/tickets/' . $id);
        }

        try {
            Ticket::changeStatus($ticket, $target, Auth::id());
            if ($note !== '') {
                Comment::add($id, Auth::id(), $note);
            }
            $updated = Ticket::find($id);
            Notifier::declarant($updated, 'status', $note !== '' ? $note : null);
            Session::flash('success', t('tech.status_changed', ['status' => label($updated, 'status')]));
        } catch (DomainException) {
            Session::flash('error', t('ticket.error.changed'));
        }
        $this->redirect('/tickets/' . $id);
    }

    /** Escalade vers le niveau 2 ou 3. La raison est conservée en note interne. */
    public function escalate(int $id): void
    {
        $ticket = Ticket::find($id) ?? View::abort(404);
        $level  = (int) $this->input('level');
        $note   = $this->input('note');

        if (!Ticket::canChangeStatus($ticket, Auth::user()) || !Ticket::isOpen($ticket)) {
            View::abort(403);
        }
        if ($level <= (int) $ticket['support_level'] || $level > 3 || $note === '' || mb_strlen($note) > 5000) {
            Session::flash('error', t('tech.error.escalate'));
            $this->redirect('/tickets/' . $id);
        }

        Ticket::escalate($ticket, $level, Auth::id());
        Comment::add($id, Auth::id(), $note, true);

        Session::flash('success', t('tech.escalated', ['ref' => $ticket['reference'], 'level' => $level]));
        $this->redirect('/queue');
    }
}
