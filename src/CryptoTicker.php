<?php

declare(strict_types=1);

namespace Demo;

use Aol\Attribute\OnTick;
use Aol\Attribute\Worker;
use Aol\Support\Cast;
use Aol\WebSocket\Attribute\OnClose;
use Aol\WebSocket\Attribute\OnMessage;
use Aol\WebSocket\Attribute\OnOpen;
use Aol\WebSocket\Attribute\WebSocket;
use Aol\WebSocket\Attribute\WsConnection;
use Aol\WebSocket\Connection;
use Aol\WebSocket\Message;

/**
 * Declarative WebSocket client subscribing to Binance's combined trade
 * stream for BTC/ETH/SOL — three real markets, one connection.
 *
 * The Wrapper opens the connection at Aol::wrap() time, hydrates the
 * #[WsConnection] property, fires #[OnOpen], pumps every received frame
 * through #[OnMessage], and prints a 1-second summary via #[OnTick] —
 * all from attributes, no event-loop plumbing in user code.
 */
#[Worker]
#[WebSocket('wss://stream.binance.com:9443/stream?streams=btcusdt@trade/ethusdt@trade/solusdt@trade')]
final class CryptoTicker
{
    #[WsConnection]
    public ?Connection $ws = null;

    public int $tradesSeen = 0;

    public float $volumeUsd = 0.0;

    /** @var array<string, float> latest trade price per symbol */
    public array $lastPrice = [];

    /** @var array<string, int> trade-count per symbol */
    public array $perSymbolCount = [];

    public bool $disconnected = false;

    #[OnOpen]
    public function connected(): void
    {
        echo "→ connected to Binance combined stream (BTC/ETH/SOL)\n\n";
    }

    #[OnMessage]
    public function ingest(Message $message): void
    {
        $envelope = \json_decode($message->payload, true);
        if (!\is_array($envelope)) {
            return;
        }

        $data = Cast::pick($envelope, 'data')->toArrayOrNull();
        if ($data === null) {
            return;
        }

        $trade = BinanceTrade::fromBinancePayload($data);
        if ($trade->symbol === '') {
            return;
        }

        $this->tradesSeen++;
        $this->volumeUsd += $trade->notionalUsd();
        $this->lastPrice[$trade->symbol] = $trade->price;
        $this->perSymbolCount[$trade->symbol] = ($this->perSymbolCount[$trade->symbol] ?? 0) + 1;
    }

    #[OnTick(every: 1.0)]
    public function snapshot(): void
    {
        if ($this->tradesSeen === 0) {
            return;
        }

        $parts = [];
        foreach ($this->lastPrice as $symbol => $price) {
            $count = $this->perSymbolCount[$symbol] ?? 0;
            $parts[] = \sprintf('%s $%s (%d)', $symbol, \number_format($price, 2), $count);
        }
        \printf(
            "[t=%s]  %d trades · $%s vol · %s\n",
            \date('H:i:s'),
            $this->tradesSeen,
            \number_format($this->volumeUsd, 0),
            \implode('  ·  ', $parts),
        );
    }

    #[OnClose]
    public function bye(?int $code, ?string $reason): void
    {
        $this->disconnected = true;
        \printf("\n← disconnected (code=%s)\n", $code ?? '?');
    }
}
