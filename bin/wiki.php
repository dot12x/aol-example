<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Aol\Aol;
use Aol\Http;
use Aol\Http\Sse\SseEvent;
use Aol\Time;
use Demo\WikiEdit;

/**
 * Demo 2 — SSE (imperative) against the real Wikimedia EventStream.
 *
 * https://stream.wikimedia.org/v2/stream/recentchange emits every edit
 * happening across all Wikimedia projects, live. We tail the stream,
 * filter to English Wikipedia non-bot edits with a non-trivial delta,
 * and auto-stop after a fixed budget.
 */

const STREAM_URL = 'https://stream.wikimedia.org/v2/stream/recentchange';
const RUN_SECONDS = 8.0;
const TARGET_WIKI = 'enwiki';

echo "== Wikipedia recent edits (SSE) ==\n";
echo 'Streaming for ' . RUN_SECONDS . 's, filtering to ' . TARGET_WIKI . "\n\n";

$count = 0;
$bytesNet = 0;

Aol::scope(function () use (&$count, &$bytesNet): void {
    $deadline = \microtime(true) + RUN_SECONDS;
    foreach (Http::sse(STREAM_URL) as $event) {
        assert($event instanceof SseEvent);
        if (\microtime(true) >= $deadline) {
            break;
        }
        if ($event->event !== 'message' || $event->data === '') {
            continue;
        }

        $payload = \json_decode($event->data, true);
        if (!\is_array($payload)) {
            continue;
        }

        $edit = WikiEdit::fromPayload($payload);
        if ($edit->wiki !== TARGET_WIKI || $edit->type !== 'edit') {
            continue;
        }
        if (\abs($edit->bytesDelta) < 50) {
            continue;
        }

        $count++;
        $bytesNet += $edit->bytesDelta;
        $sign = $edit->bytesDelta >= 0 ? '+' : '';
        $title = \mb_strimwidth($edit->title, 0, 50, '…');
        $user = \mb_strimwidth($edit->user, 0, 20, '…');
        \printf(
            "%4d. [%5s%5d B]  %-50s  by %-20s\n",
            $count,
            $sign,
            $edit->bytesDelta,
            $title,
            $user,
        );
    }
});

\printf("\nsaw %d notable edits, net +%s bytes across all of enwiki\n", $count, \number_format($bytesNet));
