<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateVoucherCodes;

class GenerateVoucherController extends Controller
{
    public function index() 
    {
        $csv_filename = 'voucher_codes.csv';
        $total_voucher_count = 3000000; // 3 million
        $batch_size = 10000; // number of voucher codes per job

        $jobs_needed = ceil($total_voucher_count / $batch_size);

        for ($i = 0; $i < $jobs_needed; $i++) {
            GenerateVoucherCodes::dispatch($batch_size, $csv_filename);
        }

        sleep(100); // wait for jobs to finish

        return response()->download($csv_filename);
    }
}