<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\View;
use App\Models\Category;
use App\Models\JobTitle;
use App\Models\Location;
use App\Models\Log;

/**
 * Administration des référentiels : catégories d'incidents, lieux et fonctions des personnels.
 *
 * Fonctions : comme les catégories, libellées dans les deux langues et jamais supprimées ;
 * une fonction désactivée n'est plus proposée pour un compte mais reste sur ceux qui l'ont.
 *
 * Catégories : rien n'est supprimé, une catégorie est désactivée (les anciens tickets la
 * gardent) ; il reste toujours au moins une catégorie active.
 *
 * Lieux : les déclarants les saisissent librement ; un lieu inconnu arrive « à vérifier ».
 * L'administrateur le valide (en corrigeant son nom si besoin) ou le fusionne avec un lieu
 * existant : ses tickets passent alors à ce lieu, pour que les statistiques restent justes.
 *
 * Chaque création, modification ou fusion est inscrite au journal d'audit.
 */
final class ReferenceController extends Controller
{
    // ------------------------------------------------------------------
    //  Catégories
    // ------------------------------------------------------------------

    public function categories(): void
    {
        $this->renderCategories([], []);
    }

    public function storeCategory(): void
    {
        $old    = ['label_fr' => $this->input('label_fr'), 'label_en' => $this->input('label_en')];
        $errors = $this->checkLabels($old['label_fr'], $old['label_en'], null, Category::labelTaken(...), 'ref.error.label_taken');

        if ($errors) {
            http_response_code(422);
            $this->renderCategories($old, $errors);
            return;
        }

        $id = Category::create($old['label_fr'], $old['label_en']);
        Log::write('category_created', 'category:' . $id, $old['label_fr'] . ' / ' . $old['label_en']);

        Session::flash('success', t('ref.category_created', ['label' => $old['label_fr']]));
        $this->redirect('/admin/categories');
    }

    public function updateCategory(int $id): void
    {
        $category = Category::find($id) ?? View::abort(404);
        $labelFr  = $this->input('label_fr');
        $labelEn  = $this->input('label_en');
        $active   = $this->input('is_active') === '1';

        // Modification en ligne dans le tableau : la première erreur est affichée en haut de page
        $errors = $this->checkLabels($labelFr, $labelEn, $id, Category::labelTaken(...), 'ref.error.label_taken');
        if (!$active && (int) $category['is_active'] === 1 && Category::countActive() <= 1) {
            $errors['is_active'] = t('ref.error.last_category');
        }
        if ($errors) {
            Session::flash('error', reset($errors));
            $this->redirect('/admin/categories');
        }

        Category::update($id, $labelFr, $labelEn, $active);
        $changes = $this->describeChanges($category, ['label_fr' => $labelFr, 'label_en' => $labelEn], $active);
        if ($changes !== '') {
            Log::write('category_updated', 'category:' . $id, $changes);
        }

        Session::flash('success', t('ref.category_updated', ['label' => $labelFr]));
        $this->redirect('/admin/categories');
    }

    // ------------------------------------------------------------------
    //  Lieux
    // ------------------------------------------------------------------

    public function locations(): void
    {
        $this->renderLocations([], []);
    }

    public function storeLocation(): void
    {
        $old    = ['name' => Location::clean($this->input('name'))];
        $errors = $this->checkLocationName($old['name'], null);

        if ($errors) {
            http_response_code(422);
            $this->renderLocations($old, $errors);
            return;
        }

        $id = Location::create($old['name']);
        Log::write('location_created', 'location:' . $id, $old['name']);

        Session::flash('success', t('ref.location_created', ['room' => $old['name']]));
        $this->redirect('/admin/locations');
    }

    /** Renomme, active ou désactive un lieu. L'enregistrer depuis l'administration le valide. */
    public function updateLocation(int $id): void
    {
        $location = Location::find($id) ?? View::abort(404);
        $name     = Location::clean($this->input('name'));
        $active   = $this->input('is_active') === '1';

        $errors = $this->checkLocationName($name, $id);
        if ($errors) {
            Session::flash('error', reset($errors));
            $this->redirect('/admin/locations');
        }

        $wasVerified = (int) $location['is_verified'] === 1;
        Location::update($id, $name, $active);

        $changes = $this->describeChanges($location, ['name' => $name], $active);
        if (!$wasVerified) {
            $changes = trim('verified ; ' . $changes, ' ;');
        }
        if ($changes !== '') {
            Log::write('location_updated', 'location:' . $id, $changes);
        }

        Session::flash('success', t($wasVerified ? 'ref.location_updated' : 'ref.location_verified', ['room' => $name]));
        $this->redirect('/admin/locations');
    }

    /** Fusionne un lieu dans un autre : ses tickets passent à l'autre lieu, puis il est supprimé. */
    public function mergeLocation(int $id): void
    {
        $source = Location::find($id) ?? View::abort(404);
        $target = Location::find((int) $this->input('target_id'));

        if ($target === null || (int) $target['id'] === $id) {
            Session::flash('error', t('ref.error.merge'));
            $this->redirect('/admin/locations');
        }

        $moved = Location::merge($id, (int) $target['id']);
        Log::write(
            'location_merged',
            'location:' . $target['id'],
            sprintf('« %s » -> « %s » : %d ticket(s)', $source['name'], $target['name'], $moved)
        );

        Session::flash('success', t('ref.location_merged', ['from' => $source['name'], 'to' => $target['name'], 'count' => $moved]));
        $this->redirect('/admin/locations');
    }

    // ------------------------------------------------------------------
    //  Fonctions des personnels
    // ------------------------------------------------------------------

    public function jobTitles(): void
    {
        $this->renderJobTitles([], []);
    }

    public function storeJobTitle(): void
    {
        $old    = ['label_fr' => $this->input('label_fr'), 'label_en' => $this->input('label_en')];
        $errors = $this->checkLabels($old['label_fr'], $old['label_en'], null, JobTitle::labelTaken(...), 'ref.error.title_taken');

        if ($errors) {
            http_response_code(422);
            $this->renderJobTitles($old, $errors);
            return;
        }

        $id = JobTitle::create($old['label_fr'], $old['label_en']);
        Log::write('job_title_created', 'job_title:' . $id, $old['label_fr'] . ' / ' . $old['label_en']);

        Session::flash('success', t('ref.title_created', ['label' => $old['label_fr']]));
        $this->redirect('/admin/titles');
    }

    public function updateJobTitle(int $id): void
    {
        $title   = JobTitle::find($id) ?? View::abort(404);
        $labelFr = $this->input('label_fr');
        $labelEn = $this->input('label_en');
        $active  = $this->input('is_active') === '1';

        $errors = $this->checkLabels($labelFr, $labelEn, $id, JobTitle::labelTaken(...), 'ref.error.title_taken');
        if ($errors) {
            Session::flash('error', reset($errors));
            $this->redirect('/admin/titles');
        }

        JobTitle::update($id, $labelFr, $labelEn, $active);
        $changes = $this->describeChanges($title, ['label_fr' => $labelFr, 'label_en' => $labelEn], $active);
        if ($changes !== '') {
            Log::write('job_title_updated', 'job_title:' . $id, $changes);
        }

        Session::flash('success', t('ref.title_updated', ['label' => $labelFr]));
        $this->redirect('/admin/titles');
    }

    // ------------------------------------------------------------------
    //  Contrôles
    // ------------------------------------------------------------------

    /**
     * Libellés obligatoires dans les deux langues (contrainte C2), de 2 à 80 caractères, sans doublon.
     * @param callable $taken      Category::labelTaken ou JobTitle::labelTaken
     * @param string   $takenError clé de traduction du message de doublon
     */
    private function checkLabels(string $labelFr, string $labelEn, ?int $exceptId, callable $taken, string $takenError): array
    {
        $errors = [];
        foreach (['label_fr' => $labelFr, 'label_en' => $labelEn] as $field => $value) {
            $length = mb_strlen($value);
            if ($length < 2 || $length > 80) {
                $errors[$field] = t('ref.error.label');
            }
        }
        if (!$errors && ($lang = $taken($labelFr, $labelEn, $exceptId)) !== null) {
            $errors['label_' . $lang] = t($takenError);
        }
        return $errors;
    }

    /** Nom de lieu de 2 à 80 caractères, avec au moins une lettre ou un chiffre, sans doublon. */
    private function checkLocationName(string $name, ?int $exceptId): array
    {
        $length = mb_strlen($name);
        if ($length < Location::MIN_LENGTH || $length > Location::MAX_LENGTH || Location::key($name) === '') {
            return ['name' => t('ref.error.location_name')];
        }
        if (Location::nameTaken($name, $exceptId)) {
            return ['name' => t('ref.error.location_taken')];
        }
        return [];
    }

    /** Résumé des changements pour le journal : « name: A101 -> A102 ; disabled ». */
    private function describeChanges(array $before, array $after, bool $active): string
    {
        $changes = [];
        foreach ($after as $field => $value) {
            if ((string) $before[$field] !== $value) {
                $changes[] = sprintf('%s: %s -> %s', $field, $before[$field], $value);
            }
        }
        if ($active !== ((int) $before['is_active'] === 1)) {
            $changes[] = $active ? 'enabled' : 'disabled';
        }
        return implode(' ; ', $changes);
    }

    private function renderCategories(array $old, array $errors): void
    {
        $this->render('admin/categories', [
            'title'      => t('nav.categories'),
            'categories' => Category::allWithCounts(),
            'old'        => $old,
            'errors'     => $errors,
        ]);
    }

    private function renderLocations(array $old, array $errors): void
    {
        $this->render('admin/locations', [
            'title'      => t('nav.locations'),
            'locations'  => Location::allWithCounts(),
            'unverified' => Location::countUnverified(),
            'old'        => $old,
            'errors'     => $errors,
        ]);
    }

    private function renderJobTitles(array $old, array $errors): void
    {
        $this->render('admin/titles', [
            'title'     => t('nav.titles'),
            'jobTitles' => JobTitle::allWithCounts(),
            'old'       => $old,
            'errors'    => $errors,
        ]);
    }
}
