<?php

declare(strict_types=1);

namespace App\CsvUploader\Application\Service;

/**
 * Accumulates type information about a single column while scanning the data.
 */
final class ColumnStats
{
    private bool $sawValue = false;
    private bool $hasEmpty = false;
    private bool $allInteger = true;
    private bool $allNumeric = true;
    private int $maxScale = 0;
    private int $maxIntegerDigits = 0;
    private int $maxLength = 0;

    public function observe(string $value): void
    {
        $value = \trim($value);

        if ($value === '') {
            $this->hasEmpty = true;

            return;
        }

        $this->sawValue = true;
        $this->maxLength = \max($this->maxLength, \mb_strlen($value));

        if (!\preg_match('/^-?\d+$/', $value)) {
            $this->allInteger = false;
        }

        if (\is_numeric($value)) {
            $this->trackNumericPrecision($value);
        } else {
            $this->allNumeric = false;
        }
    }

    public function toSqlType(int $varcharCap): string
    {
        $nullability = $this->hasEmpty ? 'NULL' : 'NOT NULL';

        if (!$this->sawValue) {
            return \sprintf('VARCHAR(%d) %s', $varcharCap, $nullability);
        }

        if ($this->allInteger) {
            return 'INT ' . $nullability;
        }

        if ($this->allNumeric) {
            $scale = \max($this->maxScale, 1);
            $precision = $this->maxIntegerDigits + $scale;

            return \sprintf('DECIMAL(%d, %d) %s', \max($precision, $scale + 1), $scale, $nullability);
        }

        if ($this->maxLength > $varcharCap) {
            return 'TEXT ' . $nullability;
        }

        return \sprintf('VARCHAR(%d) %s', $this->normalizeLength($varcharCap), $nullability);
    }

    private function trackNumericPrecision(string $value): void
    {
        $digits = \ltrim($value, '-');
        $dotPosition = \strpos($digits, '.');

        if ($dotPosition === false) {
            $this->maxIntegerDigits = \max($this->maxIntegerDigits, \strlen($digits));

            return;
        }

        $integerPart = \substr($digits, 0, $dotPosition);
        $fractionPart = \substr($digits, $dotPosition + 1);

        $this->maxIntegerDigits = \max($this->maxIntegerDigits, \strlen($integerPart));
        $this->maxScale = \max($this->maxScale, \strlen($fractionPart));
    }

    private function normalizeLength(int $cap): int
    {
        if ($this->maxLength <= 0) {
            return $cap;
        }

        // Round up to the next multiple of 16 for a little headroom.
        $rounded = (int) (\ceil($this->maxLength / 16) * 16);

        return \min($rounded, $cap);
    }
}
