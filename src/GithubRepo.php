<?php

declare(strict_types=1);

namespace Demo;

use Aol\Support\Cast;

/**
 * GitHub repository summary (subset of the /repos/{owner}/{repo} payload).
 *
 * Decoded via Aol\Support\Cast — no `??` at the call site. Cast::pick gives
 * us undefined-safe key access and fluent type coercion against the raw
 * JSON payload.
 */
final readonly class GithubRepo
{
    public function __construct(
        public string $fullName,
        public string $description,
        public int $stargazersCount,
        public int $forksCount,
        public int $openIssuesCount,
        public string $language,
        public string $defaultBranch,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            fullName: Cast::pick($data, 'full_name')->toString(),
            description: Cast::pick($data, 'description')->toString(),
            stargazersCount: Cast::pick($data, 'stargazers_count')->toInt(),
            forksCount: Cast::pick($data, 'forks_count')->toInt(),
            openIssuesCount: Cast::pick($data, 'open_issues_count')->toInt(),
            language: Cast::pick($data, 'language')->toString(),
            defaultBranch: Cast::pick($data, 'default_branch')->defaultValue('main')->toString(),
        );
    }
}
