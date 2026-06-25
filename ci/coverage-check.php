<?php

declare(strict_types=1);

/**
 * Vérifie que la couverture de lignes atteint un seuil minimal.
 *
 * Usage : php ci/coverage-check.php <chemin/clover.xml> <seuil>
 * Sortie : code 0 si la couverture >= seuil, 1 sinon (2 en cas d'erreur d'usage).
 */

$cloverPath = $argv[1] ?? null;
$threshold = isset($argv[2]) ? (float) $argv[2] : null;

if (null === $cloverPath || null === $threshold) {
    fwrite(\STDERR, "Usage: php ci/coverage-check.php <clover.xml> <seuil>\n");
    exit(2);
}

if (!is_file($cloverPath)) {
    fwrite(\STDERR, sprintf("Rapport de couverture introuvable : %s\n", $cloverPath));
    exit(2);
}

$xml = simplexml_load_file($cloverPath);
if (false === $xml || !isset($xml->project->metrics)) {
    fwrite(\STDERR, "Rapport clover illisible ou inattendu.\n");
    exit(2);
}

$metrics = $xml->project->metrics;
$statements = (int) $metrics['statements'];
$covered = (int) $metrics['coveredstatements'];
$coverage = $statements > 0 ? ($covered / $statements) * 100 : 100.0;

printf(
    "Couverture de lignes : %.2f%% (%d/%d) — seuil requis : %.2f%%\n",
    $coverage,
    $covered,
    $statements,
    $threshold
);

// Petite tolérance flottante pour ne pas échouer pile sur le seuil.
if ($coverage + 1e-9 < $threshold) {
    fwrite(\STDERR, "❌ Couverture insuffisante : ajoutez des tests.\n");
    exit(1);
}

echo "✅ Couverture suffisante.\n";
exit(0);
