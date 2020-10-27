<?php

namespace Webee\GoogleHotels\Console\Commands;

use Webee\GoogleHotels\Console\DB\DBConnection;
use Illuminate\Console\Command ;
use Illuminate\Support\Collection;

class ScapeGoogleHotels extends Command{


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:google-hotel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape google hotel listings and save them to a csv file';

     
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('starting operation');
       

        // dd(  $posts);
    }

}
