<?php

namespace App\Notifications\Messages;

use Illuminate\Support\Str;

/**
 * A text message. Short by construction: a notice nobody reads to the end is
 * worse than no notice, and every segment past the first costs money.
 */
class SmsMessage
{
    public function __construct(public string $content) {}

    public static function make(string $content): self
    {
        return new self($content);
    }

    /**
     * Trim to a single 160-character segment, keeping whole words.
     */
    public function toSegment(): string
    {
        $collapsed = trim((string) preg_replace('/\s+/', ' ', $this->content));

        return Str::limit($collapsed, 157, '...');
    }
}
