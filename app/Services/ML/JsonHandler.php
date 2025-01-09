<?php

namespace App\Services\ML;

use JsonStreamingParser\Listener\ListenerInterface;

class JsonHandler implements ListenerInterface
{
    private static array $data = [];
    private array $stack = [];
    private string $currentKey = '';
    private array $currentObject = [];
    private bool $inArray = false;

    public function startDocument(): void
    {
        self::$data = [];
        $this->stack = [];
    }

    public function endDocument(): void
    {
    }

    public function startObject(): void
    {
        $this->currentObject = [];
    }

    public function endObject(): void
    {
        if ($this->inArray) {
            self::$data[] = $this->currentObject;
        }
    }

    public function startArray(): void
    {
        $this->inArray = true;
    }

    public function endArray(): void
    {
        $this->inArray = false;
    }

    public function key(string $key): void
    {
        $this->currentKey = $key;
    }

    public function value($value): void
    {
        if ($this->inArray) {
            $this->currentObject[$this->currentKey] = $value;
        }
    }

    public function whitespace(string $whitespace): void
    {
        // Non facciamo nulla con gli spazi bianchi
    }

    public static function getData(): array
    {
        return self::$data;
    }

    public static function reset(): void
    {
        self::$data = [];
    }
}
