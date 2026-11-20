<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Découpage d'une liste en pages.
 *
 * Le numéro de page vient de l'URL (?page=3) : il est ramené entre 1 et le
 * nombre de pages, donc une valeur absurde ou négative ne provoque pas d'erreur.
 */
final class Pagination
{
    public readonly int $page;
    public readonly int $pages;

    public function __construct(
        public readonly int $total,
        public readonly int $perPage,
        mixed $requestedPage
    ) {
        $this->pages = max(1, (int) ceil($total / $perPage));
        $page        = is_numeric($requestedPage) ? (int) $requestedPage : 1;
        $this->page  = max(1, min($this->pages, $page));
    }

    /** Nombre de lignes à sauter (clause OFFSET). */
    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    /** Rang de la première ligne affichée (1 pour la première page). */
    public function from(): int
    {
        return $this->total === 0 ? 0 : $this->offset() + 1;
    }

    /** Rang de la dernière ligne affichée. */
    public function to(): int
    {
        return min($this->total, $this->offset() + $this->perPage);
    }

    /**
     * Numéros de page à afficher : la première, la dernière et deux de chaque côté
     * de la page courante. null marque un saut (affiché « … »).
     * Exemple pour la page 7 sur 20 : [1, null, 5, 6, 7, 8, 9, null, 20]
     */
    public function links(): array
    {
        $shown = [];
        $last  = 0;
        for ($n = 1; $n <= $this->pages; $n++) {
            if ($n === 1 || $n === $this->pages || abs($n - $this->page) <= 2) {
                if ($last !== 0 && $n - $last > 1) {
                    $shown[] = null;
                }
                $shown[] = $n;
                $last    = $n;
            }
        }
        return $shown;
    }
}
