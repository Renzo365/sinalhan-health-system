<?php
// tests/TestCase.php

class TestCase {
    public $assertionsCount = 0;

    public function setUp() {
        // Can be overridden in child classes
    }

    public function tearDown() {
        // Can be overridden in child classes
    }

    protected function assertEquals($expected, $actual, $message = '') {
        $this->assertionsCount++;
        if ($expected !== $actual) {
            $expectedStr = var_export($expected, true);
            $actualStr = var_export($actual, true);
            throw new Exception($message ?: "Failed asserting that {$actualStr} matches expected value {$expectedStr}.");
        }
    }

    protected function assertTrue($value, $message = '') {
        $this->assertionsCount++;
        if ($value !== true) {
            $valStr = var_export($value, true);
            throw new Exception($message ?: "Failed asserting that {$valStr} is true.");
        }
    }

    protected function assertFalse($value, $message = '') {
        $this->assertionsCount++;
        if ($value !== false) {
            $valStr = var_export($value, true);
            throw new Exception($message ?: "Failed asserting that {$valStr} is false.");
        }
    }

    protected function assertNull($value, $message = '') {
        $this->assertionsCount++;
        if ($value !== null) {
            $valStr = var_export($value, true);
            throw new Exception($message ?: "Failed asserting that {$valStr} is null.");
        }
    }

    protected function assertNotNull($value, $message = '') {
        $this->assertionsCount++;
        if ($value === null) {
            throw new Exception($message ?: "Failed asserting that value is not null.");
        }
    }

    protected function assertNotEmpty($value, $message = '') {
        $this->assertionsCount++;
        if (empty($value)) {
            $valStr = var_export($value, true);
            throw new Exception($message ?: "Failed asserting that {$valStr} is not empty.");
        }
    }

    protected function assertException(callable $callback, $expectedExceptionClass, $message = '') {
        $this->assertionsCount++;
        try {
            $callback();
        } catch (Throwable $e) {
            if ($e instanceof $expectedExceptionClass || get_class($e) === $expectedExceptionClass) {
                return; // Passed
            }
            $actualClass = get_class($e);
            throw new Exception($message ?: "Expected exception of type {$expectedExceptionClass}, but caught {$actualClass} instead: " . $e->getMessage());
        }
        throw new Exception($message ?: "Expected exception of type {$expectedExceptionClass} was not thrown.");
    }
}
