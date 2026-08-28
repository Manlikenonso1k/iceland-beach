<?php

namespace Tests\Unit;

use App\Support\NairaAmountFormatter;
use PHPUnit\Framework\TestCase;

class NairaAmountFormatterTest extends TestCase
{
    public function test_it_formats_zero_amount(): void
    {
        $this->assertSame('Zero Naira Only', NairaAmountFormatter::toWords(0));
    }

    public function test_it_formats_large_amount_into_words(): void
    {
        $this->assertSame(
            'One Hundred and Eighty Three Thousand Naira Only',
            NairaAmountFormatter::toWords(183000)
        );
    }
}
