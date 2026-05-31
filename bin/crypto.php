<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Aol\Aol;
use Aol\Time;
use Demo\CryptoTicker;

/**
 * Demo 3 — Real-time Binance trades via declarative WebSocket.
 *
 * The CryptoTicker class describes the entire WebSocket lifecycle in
 * attributes: #[WebSocket(url)] opens the connection at wrap time;
 * #[OnMessage] handles each frame; #[OnTick] prints a 1Hz summary;
 * #[OnClose] cleans up. Aol::scope() owns the connection lifetime.
 */

const RUN_SECONDS = 10.0;

echo "== Binance live trades (WebSocket, declarative) ==\n";
echo 'Streaming for ' . RUN_SECONDS . "s, then cleanly disconnecting.\n\n";

$ticker = new CryptoTicker();

Aol::scope(function () use ($ticker): void {
    Aol::wrap($ticker);
    Time::sleep(RUN_SECONDS);
});

echo "\n== Final tape ==\n";
\printf("trades observed:  %d\n", $ticker->tradesSeen);
\printf("notional volume:  $%s\n", \number_format($ticker->volumeUsd, 2));

if ($ticker->lastPrice !== []) {
    echo "last prices:\n";
    foreach ($ticker->lastPrice as $symbol => $price) {
        \printf("  %-10s  $%s\n", $symbol, \number_format($price, 2));
    }
}
