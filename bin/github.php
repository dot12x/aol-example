<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Aol\Aol;
use Aol\Http;
use Aol\Pending;
use Demo\GithubApi;
use Demo\GithubRepo;

/**
 * Demo 1 — Declarative HTTP + auto-graph parallelism.
 *
 * Reads four PHP-ecosystem repositories in parallel. Note how passing
 * `Pending<GithubRepo>` straight to the formatter inside the same scope
 * works without any await: scope close resolves the graph.
 */

$gh = Http::fromInterface(GithubApi::class);

/** @var array<string, GithubRepo> $repos */
$repos = Aol::scope(function () use ($gh): array {
    $targets = [
        ['symfony', 'symfony'],
        ['laravel', 'framework'],
        ['dot12x', 'php-aol'],
        ['amphp', 'http-client'],
    ];

    $pending = [];
    foreach ($targets as [$owner, $name]) {
        $pending["{$owner}/{$name}"] = $gh->repo($owner, $name);
    }
    return $pending;
});

echo "== GitHub repo snapshot (4 in parallel) ==\n\n";

\printf("%-30s  %10s  %10s  %10s  %-12s\n", 'Repo', '★ stars', '⑂ forks', '◔ issues', 'language');
\printf("%s\n", \str_repeat('-', 80));

foreach ($repos as $repo) {
    \printf(
        "%-30s  %10d  %10d  %10d  %-12s\n",
        $repo->fullName,
        $repo->stargazersCount,
        $repo->forksCount,
        $repo->openIssuesCount,
        $repo->language === '' ? '—' : $repo->language,
    );
}

echo "\ndone.\n";
