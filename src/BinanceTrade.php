<?php

declare(strict_types=1);

namespace Demo;

use Aol\Support\Cast;

/**
 * Single trade tick decoded from Binance's WebSocket stream
 * (https://binance-docs.github.io/apidocs/spot/en/#trade-streams).
 *
 * Binance uses one-letter keys ('s' = symbol, 'p' = price, etc.) — we
 * map them through the readable property names of this value type.
 */
final readonly class BinanceTrade
{
    public function __construct(
        public string $symbol,
        public float $price,
        public float $quantity,
        public int $tradeId,
        public int $eventTimeMs,
        public bool $buyerIsMaker,
    ) {
    }

    public function notionalUsd(): float
    {
        return $this->price * $this->quantity;
    }

    public function side(): string
    {
        return $this->buyerIsMaker ? 'SELL' : 'BUY';
    }

    /**
     * @param array<string, mixed> $payload Binance @trade frame as decoded JSON
     */
    public static function fromBinancePayload(array $payload): self
    {
        return new self(
            symbol: Cast::pick($payload, 's')->toString(),
            price: Cast::pick($payload, 'p')->toFloat(),
            quantity: Cast::pick($payload, 'q')->toFloat(),
            tradeId: Cast::pick($payload, 't')->toInt(),
            eventTimeMs: Cast::pick($payload, 'T')->toInt(),
            buyerIsMaker: Cast::pick($payload, 'm')->toBool(),
        );
    }
}
