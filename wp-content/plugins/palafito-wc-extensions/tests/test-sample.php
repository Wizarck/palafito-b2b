<?php
use PHPUnit\Framework\TestCase;

class SampleTest extends TestCase {
    public function test_one_plus_one_equals_two() {
        $this->assertEquals(2, 1 + 1);
    }
}