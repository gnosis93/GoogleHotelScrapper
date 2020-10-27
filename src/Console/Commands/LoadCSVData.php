<?php

namespace Appsilon\LeadsEPL\Console\Commands;

use Appsilon\LeadsEPL\Console\DB\DBConnection;
use Illuminate\Console\Command ;
use Illuminate\Support\Collection;

class LoadCSVData extends Command{


    /**
     * Holds  a collection of countires
     *
     * @var Collection|null
     */
    private $listOfCountries;

    

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'extract:post';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import all CSV files in data folder into the DB';

     
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('starting operation');
        $posts = new Collection([]);
        $wordpressDB = DBConnection::getInstance('hotdogs_wp');//7328

        $postsToFetch = $wordpressDB->table('wp_posts')
        ->where('post_type','listings')
        ->where('post_status','publish')
        // ->distinct('post_title')
        ->get();

        $imported = 0;
        $count = count($postsToFetch);
        foreach($postsToFetch as $key=>$postToFetch){
            $existingPost = $posts->first(function($post) use ($postToFetch){
                return $post['title'] == $postToFetch->post_title;
            });

            
            if($existingPost === null){
                $this->info('  Importing '.($key+1).'/'.$count);
                $fullPost = $this->getFullPost($postToFetch);

                if(!$fullPost['image_url']){
                    $this->warn('  Skipped Property with no IMAGE '.($key+1).'/'.$count);
                    continue;
                }
                $this->savePostToDirectus($fullPost);
                $posts[] = $fullPost;
                $imported++;
            }else{
                $this->warn('  Skipped Duplicate '.($key+1).'/'.$count);
            }
        }
        

        $this->info("COMPLETED (Imported $imported new properties)");

        // dd(  $posts);
    }

    private function savePostToDirectus(array $redefinedPost):bool{
        $directusDB = DBConnection::getInstance('hotdogs_directus');
        $directusDB->table('properties')->insert([
            'status'      => 'published',
            'created_on'  => date('Y-m-d H:i:s'),
            'modified_by' => 1,
            'name'        => $redefinedPost['title'],
            'price'       => $redefinedPost['price'],
            'beds'        => $redefinedPost['beds'],
            'baths'       => $redefinedPost['baths'],
            'size'        => $redefinedPost['size'],
            'video_url'   => $redefinedPost['video'],
            'lat'         => $redefinedPost['lat']  ?:0,
            'lang'        => $redefinedPost['lang'] ?:0,
            'image_url'   => $redefinedPost['image_url']
            // 'features'    
        ]);
        $propertyID = $directusDB->getPdo()->lastInsertId();
        
        //save property features
        foreach($redefinedPost['features'] as $feature){
            $featureInDB = $directusDB->table('features')->where('name',$feature)->first();
            if($featureInDB == null){
                $directusDB->table('features')->insert([
                    'name' => $feature
                ]);
                $featureID = $directusDB->getPdo()->lastInsertId();
            }else{
                $featureID = $featureInDB->id;
            }

            $directusDB->table('properties_features')->insert([
                'properties_id' => $propertyID,
                'features_id'   => $featureID
            ]);
        }

        //save property images
        foreach($redefinedPost['images'] as $imageUrl){
            $directusDB->table('properties_images')->insert([
                'property_id' => $propertyID,
                'image_url'   => $imageUrl
            ]);
        }
        //save property images
        return true;
    }

    private function getFullPost(\stdClass $post):array{
        $wordpressDB = DBConnection::getInstance('hotdogs_wp');
  
        $postTerms = $wordpressDB->table('wp_term_relationships')
        ->where('object_id',$post->ID)
        ->join('wp_term_taxonomy','wp_term_taxonomy.term_taxonomy_id','=','wp_term_relationships.term_taxonomy_id')
        ->join('wp_terms','wp_terms.term_id','=','wp_term_taxonomy.term_taxonomy_id')
        ->get();

        $postMeta = $wordpressDB->table('wp_postmeta')->where('post_id',$post->ID)->get();

        $latLang =  $this->getPostMeta('_ct_latlng',$postMeta);
        $latLangExploded = explode(',',$latLang);

        $postRedefined = [
            'id'       => $post->ID,
            'title'    => $post->post_title,
            'beds'     => $this->getPostTerm('beds',$postTerms) ?? 0,
            'baths'    => $this->getPostTerm('baths',$postTerms) ?? 0,
            'author'   => $this->getPostTerm('author',$postTerms),
            'pets'     => $this->getPostTerm('pets',$postTerms),
            'video'    => $this->getPostMeta('_ct_video',$postMeta),
            'size'     => $this->getPostMeta('_ct_sqft',$postMeta),
            'price'    => $this->getPostMeta('_ct_price',$postMeta),
            'images'   => $this->getImages($postMeta),
            'features' => $this->getFeatures($postTerms),
            'lat'      => $latLangExploded[0] ?? 0,
            'lang'     => $latLangExploded[1] ?? 0,
            'image_url' => $this->getPostThumbImage($post->ID)
        ];
        return $postRedefined;
    }


    private function getPostThumbImage(int $postID):?string{
        $wordpressDB = DBConnection::getInstance('hotdogs_wp');
        $postImage    = $wordpressDB->table('wp_posts')->where('post_parent',$postID)->first();
        if(!$postImage || !$postImage->post_name && strlen($postImage->post_name) > 6){
            return '';
        }
        $year = substr($postImage->post_name,0,4);
        $month = substr($postImage->post_name,4,2);
        return "https://i2.wp.com/hotdogcondos.com/wp-content/uploads/$year/$month/".$postImage->post_name.'?resize=818%2C540&ssl=1?v='.time();
    }

    private function getPostTerm(string $term,Collection $postTerms):?string{
        $foundTerm = $postTerms->first(function($postTerm) use ($term){
            return $postTerm->taxonomy == $term;
        });
        return !empty($foundTerm->name) ? $foundTerm->name : null;
    }

    private function getPostMeta(string $metaKey,Collection $postMetaData):?string{
        $foundTerm = $postMetaData->first(function($postMetaData) use ($metaKey){
            return $postMetaData->meta_key == $metaKey;
        });
        return !empty($foundTerm->meta_value) ? $foundTerm->meta_value : null;
    }


    private function getFeatures(Collection $postTerms ):array{
        $foundFeatures = [];
        $additionalFeatures = $postTerms->filter(function($postTerm){
            return $postTerm->taxonomy == 'additional_features';
        });
        foreach($additionalFeatures as $feature){
            $foundFeatures[] = $feature->name;
        }
        return $foundFeatures;
    }


    private function getImages(Collection $postMetaData):array{
        $imageMetaData = $postMetaData->first(function($postMetaData){
            return $postMetaData->meta_key == '_ct_slider';
        });
        if(empty($imageMetaData->meta_value)){
            return [];
        }
        $jsonStartPos = strpos($imageMetaData->meta_value,'{');
        $validJSON    = substr($imageMetaData->meta_value,$jsonStartPos,strlen($imageMetaData->meta_value));
        $validJSON    = str_replace('%','',$validJSON);
        $validJSON    = str_replace(';',',',$validJSON);
        
        preg_match_all('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $validJSON, $match);

        return $match[0] ?? [];
    }
}
