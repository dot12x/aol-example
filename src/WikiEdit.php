<?php

declare(strict_types=1);

namespace Demo;

use Aol\Support\Arr;
use Aol\Support\Cast;

/**
 * Wikipedia recentchange event from the Wikimedia EventStream
 * (https://stream.wikimedia.org/v2/stream/recentchange).
 *
 * Decoded via Aol\Support\Cast and Arr — no `??` at the call site:
 * Cast::pick handles flat keys, Arr::from()->cast() reaches into the
 * nested `length` sub-object for old/new byte counts.
 */
final readonly class WikiEdit
{
    public function __construct(
        public string $wiki,
        public string $title,
        public string $user,
        public string $type,
        public ?string $comment,
        public int $timestamp,
        public int $bytesDelta,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        $length = Cast::pick($payload, 'length')->toArrayOrNull();
        $oldLen = $length === null ? 0 : Cast::pick($length, 'old')->toInt();
        $newLen = $length === null ? 0 : Cast::pick($length, 'new')->toInt();
        $rawComment = Arr::from($payload)->get('comment');

        return new self(
            wiki: Cast::pick($payload, 'wiki')->toString(),
            title: Cast::pick($payload, 'title')->toString(),
            user: Cast::pick($payload, 'user')->toString(),
            type: Cast::pick($payload, 'type')->toString(),
            comment: \is_string($rawComment) ? $rawComment : null,
            timestamp: Cast::pick($payload, 'timestamp')->toInt(),
            bytesDelta: $newLen - $oldLen,
        );
    }
}
