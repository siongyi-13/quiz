<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class GenerateVoucherCodes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;

    protected $voucher_count;
    protected $csv_filename;
    private static $voucher_length = 10;

    public function __construct($voucher_count, $csv_filename)
    {
        $this->csv_filename = $csv_filename;
        $this->voucher_count = $voucher_count;
    }

    public function handle()
    {
        $lower_cases = 'abcdefghijklmnopqrstuvwxyz';
        $upper_cases = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $digits = '0123456789';
        $all_characters = $lower_cases . $upper_cases . $digits;

        $cache_key = '';

        do{
            $prefix = '';
            $prefix .= $lower_cases[random_int(0, strlen($lower_cases) - 1)];
            $prefix .= $upper_cases[random_int(0, strlen($upper_cases) - 1)];
            $prefix .= $digits[random_int(0, strlen($digits) - 1)];
            $prefix = str_shuffle($prefix);
            $cache_key = 'voucher_'.$prefix;
        }
        while(Cache::has($cache_key));

        $data_to_insert = [];
        $existing_codes = [];
        Cache::put($cache_key, true, 3600); // set this to prevent other job from using the same prefix

        // open the CSV file, or create it if it doesn't exist
        $csv_file = fopen($this->csv_filename, 'a');

        // write header to file
        if (filesize($this->csv_filename) === 0) {
            fputcsv($csv_file, ['Voucher Code']);
        }

        // generate the specified number of voucher codes
        for ($count = 0; $count < $this->voucher_count; $count++) {
            do {
                // generate the suffix for the voucher code
                $suffix = '';
                for ($i = strlen($prefix); $i < static::$voucher_length; $i++) {
                    $suffix .= $all_characters[random_int(0, strlen($all_characters) - 1)];
                }

                // shuffle to randomize the order of the characters
                $suffix = str_shuffle($suffix);
                $voucher_code = $prefix . $suffix;

            } while (in_array($voucher_code, $existing_codes)); // check if the code exists

            $existing_codes[] = $voucher_code;
            $data_to_insert[] = [$voucher_code];
        }

        foreach ($data_to_insert as $voucher_data) {
            fputcsv($csv_file, $voucher_data);
        }

        fclose($csv_file);
    }
}
