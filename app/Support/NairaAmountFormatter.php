<?php

namespace App\Support;

class NairaAmountFormatter
{
    public static function toWords(float|int|string $amount): string
    {
        $wholeAmount = (int) floor((float) $amount);

        if ($wholeAmount <= 0) {
            return 'Zero Naira Only';
        }

        return self::convertNumber($wholeAmount) . ' Naira Only';
    }

    private static function convertNumber(int $number): string
    {
        $ones = [
            0 => 'Zero',
            1 => 'One',
            2 => 'Two',
            3 => 'Three',
            4 => 'Four',
            5 => 'Five',
            6 => 'Six',
            7 => 'Seven',
            8 => 'Eight',
            9 => 'Nine',
            10 => 'Ten',
            11 => 'Eleven',
            12 => 'Twelve',
            13 => 'Thirteen',
            14 => 'Fourteen',
            15 => 'Fifteen',
            16 => 'Sixteen',
            17 => 'Seventeen',
            18 => 'Eighteen',
            19 => 'Nineteen',
        ];

        $tens = [
            2 => 'Twenty',
            3 => 'Thirty',
            4 => 'Forty',
            5 => 'Fifty',
            6 => 'Sixty',
            7 => 'Seventy',
            8 => 'Eighty',
            9 => 'Ninety',
        ];

        if ($number < 20) {
            return $ones[$number];
        }

        if ($number < 100) {
            $tenPart = intdiv($number, 10);
            $remainder = $number % 10;

            return $tens[$tenPart] . ($remainder > 0 ? ' ' . $ones[$remainder] : '');
        }

        if ($number < 1000) {
            $hundreds = intdiv($number, 100);
            $remainder = $number % 100;

            return $ones[$hundreds] . ' Hundred' . ($remainder > 0 ? ' and ' . self::convertNumber($remainder) : '');
        }

        $scales = [
            1000000000 => 'Billion',
            1000000 => 'Million',
            1000 => 'Thousand',
        ];

        foreach ($scales as $value => $label) {
            if ($number >= $value) {
                $main = intdiv($number, $value);
                $remainder = $number % $value;

                return self::convertNumber($main) . ' ' . $label . ($remainder > 0 ? ' ' . self::convertNumber($remainder) : '');
            }
        }

        return (string) $number;
    }
}
