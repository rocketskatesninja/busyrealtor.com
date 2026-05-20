<?php

namespace App\Support;

/**
 * Tiny formatter for plain-text notification email bodies.
 *
 * Centralises the "header line + ─── divider + key/value pairs + footer"
 * pattern used by dunning, payment receipts, contact-form forwards and
 * appointment notifications, so the formatting only lives in one place.
 *
 * Example:
 *
 *     $body = MailBody::make('Payment received. Thank you!')
 *         ->row('Date',   'May 20, 2026')
 *         ->row('Amount', '$59.00')
 *         ->row('Plan',   'Pro Plan')
 *         ->blank()
 *         ->line('You can view your full billing history from your account dashboard.')
 *         ->toString();
 */
class MailBody
{
    private const DIVIDER_WIDTH = 40;

    /** @var list<string> */
    private array $lines = [];

    public function __construct(?string $header = null)
    {
        if ($header !== null && $header !== '') {
            $this->lines[] = $header;
            $this->lines[] = str_repeat('─', self::DIVIDER_WIDTH);
        }
    }

    public static function make(?string $header = null): self
    {
        return new self($header);
    }

    public function row(string $label, ?string $value): self
    {
        if ($value === null || $value === '') {
            return $this;
        }
        $this->lines[] = "{$label}: {$value}";
        return $this;
    }

    public function line(string $text): self
    {
        $this->lines[] = $text;
        return $this;
    }

    public function blank(): self
    {
        $this->lines[] = '';
        return $this;
    }

    public function toString(): string
    {
        return implode("\n", $this->lines);
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
