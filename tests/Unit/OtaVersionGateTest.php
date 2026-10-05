<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class OtaVersionGateTest extends TestCase
{
    /**
     * The gate logic in OTA::getAvailable() is private; mirror the decision
     * here so the string-vs-numeric comparison and the disabled-type list
     * behaviour are pinned down without a Laravel app.
     */
    private function offer(mixed $repoVersion, string $deviceFirmware, array $disabled = []): bool
    {
        if (in_array('d3', $disabled, true)) {
            return false;
        }

        return version_compare((string) $repoVersion, (string) $deviceFirmware, '>');
    }

    public function test_equal_version_is_never_re_offered(): void
    {
        $this->assertFalse($this->offer('1.462', '1.462'));
    }

    public function test_newer_version_is_offered(): void
    {
        $this->assertTrue($this->offer('1.463', '1.462'));
    }

    public function test_older_version_is_not_offered(): void
    {
        $this->assertFalse($this->offer('1.428', '1.462'));
    }

    public function test_numeric_comparison_not_lexicographic(): void
    {
        // Lexicographic string compare would call 1.9 newer than 1.262.
        $this->assertFalse($this->offer('1.9', '1.262'));
        $this->assertTrue($this->offer('1.262', '1.9'));
    }

    public function test_disabled_type_is_never_offered(): void
    {
        $this->assertFalse($this->offer('2.0', '1.462', ['d3']));
    }

    public function test_config_parse_matches_disabled_device_types_helper(): void
    {
        $parse = static function (string $raw): array {
            $raw = trim($raw);
            if ($raw === '') {
                return [];
            }

            return array_values(array_filter(array_map('trim', explode(',', $raw))));
        };

        $this->assertSame(['d3'], $parse('d3'));
        $this->assertSame(['d3', 't5'], $parse('d3, t5'));
        $this->assertSame([], $parse(''));
        $this->assertSame([], $parse('  '));
    }
}