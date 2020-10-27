<?php

namespace Webee\GoogleHotels\Console\Commands;

use Webee\GoogleHotels\Console\DB\DBConnection;
use Illuminate\Console\Command ;
use Illuminate\Support\Collection;
use Goutte\Client;

class ScapeGoogleHotels extends Command{

    /**
     * Hotels Location, will be included in LISTING_URL const
     * @var string
     */
    const LISTING_DESTINATION = 'Sliema';

    /**
     * Listing URL that will be used by the scrapper to fetch the hotels
     * @var string
     */
    const LISTING_URL = 'https://www.google.com/travel/hotels?utm_campaign=sharing&utm_medium=link&utm_source=htls&hrf=CgUIlgEQACIDRVVSKhYKBwjkDxALGAcSBwjkDxALGAgYASgAsAEAWAFoAZoBLxIGU2xpZW1hGiUweDEzMGU0NTM5ODBkY2I4NjU6MHhhNzM1NGQ3MjFmMTQ1OTQ5ogETCgkvbS8wNWY3cHQSBlNsaWVtYZIBAiAB&rp=OAFIAg&destination='.self::LISTING_DESTINATION.'&ap=MABoAA';

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
     * Undocumented variable
     *
     * @var Client
     */
    private $scrapperClient;
    

    public function __construct()
    {
        parent::__construct();
        $this->scrapperClient = new Client();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->info('starting operation');
        
        $this->scrapeListing();

        $this->info('starting completed');

    }


    private function scrapeListing(){
        // Go to the symfony.com website
        $crawler = $this->scrapperClient->request('GET', self::LISTING_URL);

        $rawHTML = $this->getHTML($crawler);

        // dd($rawHTML);
        foreach ($crawler as $domElement) {
            $this->info($domElement->nodeName);
        }
        
        // $crawler->filter('.l5cSPd > c-wiz')->each(function ($node) {
        //     $this->info( $node->text());
        // });



    }

    /**
     * Testing/Debugging Function
     *
     * @param \Symfony\Component\DomCrawler\Crawler $crawler
     * @return string
     */
    private function getHTML(\Symfony\Component\DomCrawler\Crawler $crawler):string{
        $html = '';

        foreach ($crawler as $domElement) {
            $html.= $domElement->ownerDocument->saveHTML();
        }

        return $html;
    }
}
