<?php

declare(strict_types=1);

namespace App\Modules\Invoice\Support;

final class TicketBuilder
{
    private string $buffer = '';

    private int $widthCharacters;

    public function __construct(string $paperWidth = '80mm')
    {
        // 58mm -> 32 cols, 80mm/88mm -> 48 cols
        $this->widthCharacters = ($paperWidth === '58mm') ? 32 : 48;

        // Inicializar impresora ESC/POS (ESC @)
        $this->buffer .= "\x1b@";
    }

    public function align(string $alignment = 'left'): self
    {
        $code = match ($alignment) {
            'center' => "\x1ba\x01",
            'right' => "\x1ba\x02",
            default => "\x1ba\x00",
        };
        $this->buffer .= $code;

        return $this;
    }

    public function bold(bool $enable = true): self
    {
        $this->buffer .= $enable ? "\x1bE\x01" : "\x1bE\x00";

        return $this;
    }

    public function doubleHeight(bool $enable = true): self
    {
        // ESC ! n (n=0 normal, n=16 double height, n=32 double width, n=48 both)
        $this->buffer .= $enable ? "\x1b!\x10" : "\x1b!\x00";

        return $this;
    }

    public function text(string $content): self
    {
        $this->buffer .= $content;

        return $this;
    }

    public function line(string $content = ''): self
    {
        $this->buffer .= $content."\n";

        return $this;
    }

    public function separator(string $char = '-'): self
    {
        $this->buffer .= str_repeat($char, $this->widthCharacters)."\n";

        return $this;
    }

    public function row(string $left, string $right): self
    {
        $leftLen = mb_strlen($left);
        $rightLen = mb_strlen($right);
        $spacesNeeded = $this->widthCharacters - ($leftLen + $rightLen);

        if ($spacesNeeded <= 0) {
            // Si sobrepasa, imprimimos por separado
            $this->buffer .= $left."\n".str_pad($right, $this->widthCharacters, ' ', STR_PAD_LEFT)."\n";
        } else {
            $this->buffer .= $left.str_repeat(' ', $spacesNeeded).$right."\n";
        }

        return $this;
    }

    public function cut(): self
    {
        // Comando GS V 66 0 (Corte parcial)
        $this->buffer .= "\x1dVB\x00";

        return $this;
    }

    public function build(): string
    {
        return $this->buffer;
    }
}
