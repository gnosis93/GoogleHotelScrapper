<?php
namespace Webee\GoogleHotels\Console\DB;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

class DBConnection{
   
    private function __constructor(){
    }


    private const DB_CONNECTIONS = [
        'hotdogs_wp' => [
            'driver'    =>  'mysql',
            'host'      =>  'localhost:8083',
            'database'  =>  'bitnami_wordpress',
            'username'  =>  'bn_wordpress',
            'password'  =>  '575339d712',
            'charset'   =>  'utf8mb4',
            'collation' =>  'utf8mb4_general_ci',
            'prefix'    =>  ''
        ],
        'hotdogs_directus' => [
            'driver'    =>  'mysql',
            'host'      =>  'localhost:8081',
            'database'  =>  'hotdogs',
            'username'  =>  'root',
            'password'  =>  'A5JSzvcyvPR48LC',
            'charset'   =>  'utf8mb4',
            'collation' =>  'utf8mb4_general_ci',
            'prefix'    =>  ''
        ],

    ];

    /**
     * Undocumented variable
     *
     * @var array
     */
    private static $db_instances = [];
    
    /**
     * 
     *
     * @var Capsule
     */
    private static $capsule;


    public static function getInstance(string $dbName):\Illuminate\Database\Connection{
        if(key_exists($dbName,self::$db_instances) ){
            return self::$capsule->connection($dbName);
        }
        if(key_exists($dbName,self::DB_CONNECTIONS) === false){
            throw new \Exception('Invalid DB Connection name given to DBConnection, make sure '.$dbName.' is defined in DBConnection.php');
        }
        self::$db_instances[$dbName] = 1;

        $capsule = self::loadDB($dbName);
        return $capsule->connection($dbName);
    }

    /**
     * load the db by its name
     *
     * @param string $dbName
     * @return Capsule
     */
    private static function loadDB(string $dbName){
        if(!self::$capsule){
            self::$capsule = new Capsule();
        }
        
        self::$capsule->addConnection(self::DB_CONNECTIONS[$dbName],$dbName);
        // dd($connection);
        // Set the event dispatcher used by Eloquent models... (optional)
      
        self::$capsule->setEventDispatcher(new Dispatcher(new Container));
        
        // Make this Capsule instance available globally via static methods... (optional)
        self::$capsule->setAsGlobal();
        
        // Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
        self::$capsule->bootEloquent();

        return self::$capsule;
    }
}