<?php
/**
 * @author xionbig
 * @link http://xionbig.altervista.org/SignShop 
 * @link http://forums.pocketmine.net/plugins/signshop.668/
 * @version 1.0.0
 */
namespace SignShop;

use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;

class SignShop extends PluginBase implements Listener{ 
    public $temp = [];
    private $setup, $provider;
    private $manager = [];    
        
    public function onEnable(){
        $dataResources = $this->getDataFolder()."/resources/";
        if (!file_exists($this->getDataFolder())) 
            @mkdir($this->getDataFolder(), 0755, true);
        if (!file_exists($dataResources)) 
            @mkdir($dataResources, 0755, true);
        
        $this->setup = new Config($dataResources. "config.yml", Config::YAML, [
                "version" => "one",
                "signCreated" => "all",
                "lastChange" => time(),
                "dataProvider" => "YAML",
                "dataProviderSettings" => ["host" => "127.0.0.1",         
                                            "port" => 3306,
                                            "user" => "usernameDatabase",
                                            "password" => "passwordDatabase",
                                            "database" => "databaseName"]
            ]);
        if($this->setup->get("signCreated") == "list")
            $this->setup->set("signCreated", "admin");
        $this->setup->save();
        
        switch(strtolower($this->setup->get("dataProvider"))){           
            case "yml":
            case "yaml":
                $this->provider = new Provider\YAMLProvider($this);
                break;
            case "sql":
            case "sqlite":
            case "sqlite3":
                $this->provider = new Provider\SQLiteProvider($this);
                break;
            case "mysqli":
            case "mysql":
                $this->provider = new Provider\MySQLProvider($this);
                break;
            default:
                $this->getLogger()->info(TextFormat::RED."The field 'dataProvider' in config.yml is incorrect!"); 
                $this->getServer()->shutdown();
        }
        
        $this->manager["message"] = new Manager\MessageManager($this, $dataResources);
        $this->manager["command"] = new Command\SignShopCommand($this); 
        $this->manager["items"] = new Manager\ListItems();
        $this->manager["money"] = new Manager\MoneyManager($this);
        $this->manager["sign"] = new Manager\SignManager($this);

        $this->getServer()->getCommandMap()->register("sign", $this->manager["command"]);

        $this->getServer()->getPluginManager()->registerEvents(new EventListener\PlayerSpawnEvent($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener\PlayerTouchEvent($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener\PlayerBlockBreakEvent($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener\PlayerSignCreateEvent($this), $this);
    }
    
    public function messageManager(){
        return $this->manager["message"];
    }
    public function getMoneyManager(){
        return $this->manager["money"];
    }
    
    public function getSignManager(){
        return $this->manager["sign"];
    }
    
    public function getItems(){
        return $this->manager["items"];
    }    
    
    public function getSetup(){
        return $this->setup;
    }
    
    public function getProvider(){
        return $this->provider;
    }
             
    public function onDisable(){
        $this->setup->save();
        $this->provider->onDisable();
    }
}