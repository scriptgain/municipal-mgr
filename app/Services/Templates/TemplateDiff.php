<?php

namespace App\Services\Templates;

/**
 * Line diff between two template revisions.
 *
 * Hand-rolled longest-common-subsequence rather than a package: the product has
 * no build step and adding a composer dependency for forty lines of algorithm
 * is not a trade worth making. Output is a flat list of rows the view renders
 * as markup, so the Blade file stays free of logic.
 */
class TemplateDiff
{
    /**
     * @return array{rows:array,added:int,removed:int}
     */
    public function compare(string $before, string $after): array
    {
        $a = preg_split('/\R/', $before) ?: [];
        $b = preg_split('/\R/', $after) ?: [];

        $rows = $this->walk($a, $b, $this->lcs($a, $b), count($a), count($b));
        $rows = array_reverse($rows);

        return [
            'rows' => $rows,
            'added' => count(array_filter($rows, fn ($r) => $r['type'] === 'add')),
            'removed' => count(array_filter($rows, fn ($r) => $r['type'] === 'remove')),
        ];
    }

    /** Classic LCS table. */
    private function lcs(array $a, array $b): array
    {
        $n = count($a);
        $m = count($b);
        $table = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));

        for ($i = 1; $i <= $n; $i++) {
            for ($j = 1; $j <= $m; $j++) {
                $table[$i][$j] = $a[$i - 1] === $b[$j - 1]
                    ? $table[$i - 1][$j - 1] + 1
                    : max($table[$i - 1][$j], $table[$i][$j - 1]);
            }
        }

        return $table;
    }

    /** Backtrack the table into diff rows (reversed; caller flips them). */
    private function walk(array $a, array $b, array $table, int $i, int $j): array
    {
        $rows = [];

        while ($i > 0 || $j > 0) {
            if ($i > 0 && $j > 0 && $a[$i - 1] === $b[$j - 1]) {
                $rows[] = ['type' => 'same', 'left' => $i, 'right' => $j, 'text' => $a[$i - 1]];
                $i--;
                $j--;
            } elseif ($j > 0 && ($i === 0 || $table[$i][$j - 1] >= $table[$i - 1][$j])) {
                $rows[] = ['type' => 'add', 'left' => null, 'right' => $j, 'text' => $b[$j - 1]];
                $j--;
            } else {
                $rows[] = ['type' => 'remove', 'left' => $i, 'right' => null, 'text' => $a[$i - 1]];
                $i--;
            }
        }

        return $rows;
    }
}
