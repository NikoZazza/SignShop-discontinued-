<?php
/**
 * SignShop Copyright (C) 2015 xionbig
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * @author xionbig
 * @name SignShop
 * @main SignShop\SignShop
 * @link http://xionbig.netsons.org/plugins/SignShop 
 * @link http://forums.pocketmine.net/plugins/signshop.668/
 * @description Buy and Sell the items using Signs with virtual-money.
 * @version 1.1.2
 * @api 1.11.0
 */
namespace SignShop;

use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use SignShop\Provider\MySQLProvider;
use SignShop\Provider\SQLiteProvider;
use SignShop\Provider\YAMLProvider;

class SignShop extends PluginBase implements Listener{ 
    /** @var array */
    public $temp = [];
    /** @var array */
    private $setup, $provider, $manager = [];    
    
    public function onEnable(){
        $dataResources = $this->getDataFolder()."/resources/";
        if(!file_exists($this->getDataFolder())) 
            @mkdir($this->getDataFolder(), 0755, true);
        if(!file_exists($dataResources)) 
            @mkdir($dataResources, 0755, true);
        
        $this->setup = new Config($dataResources. "config.yml", Config::YAML, [
            "economyPlugin" => "EconomyAPI",
                "version" => "oneone",
                "signCreated" => "all",
                "lastChange" => time(),
                "server1" => "http://xionbig.netsons.org/plugins/SignShop/translate/download.php",
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
                $this->getLogger()->critical("The field 'dataProvider' in config.yml is incorrect! Use the provider YAML"); 
                $this->provider = new Provider\YAMLProvider($this);
        }
        
        $this->manager["message"] = new Manager\MessageManager($this, $dataResources);
        $this->manager["command"] = new Command\SignShopCommand($this); 
        $this->manager["money"] = new Manager\MoneyManager($this, $this->getFile());
        $this->manager["sign"] = new Manager\SignManager($this);

        $this->getServer()->getCommandMap()->register("sign", $this->manager["command"]);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener\LevelEvent($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener\PlayerSpawnEvent($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener\PlayerTouchEvent($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener\PlayerBlockBreakEvent($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener\PlayerSignCreateEvent($this), $this);
        $this->getServer()->getLogger()->info(TextFormat::GOLD."SignShop v".$this->getDescription()->getVersion()." Enabled!");
    }


    /**
     * @return MessageManager
     */
    public function messageManager(){
        return $this->manager["message"];
    }
    
    /**
     * @return MoneyManager
     */
    public function getMoneyManager(){
        return $this->manager["money"];
    }
    
    /**
     * @return SignManager
     */
    public function getSignManager(){
        return $this->manager["sign"];
    }
        
    /**
     * @return Config
     */
    public function getSetup(){
        return $this->setup;
    }
    
    /**
     * @return MySQLProvider or SQLiteProvider or YAMLProvider
     */
    public function getProvider(){
        return $this->provider;
    }
             
    public function onDisable(){
        unset($this->temp);
        if($this->setup instanceof Config)
            $this->setup->save();
        if($this->provider instanceof MySQLProvider || $this->provider instanceof SQLiteProvider || $this->provider instanceof YAMLProvider) 
            $this->provider->onDisable();
        $this->getSignManager()->onDisable();
    }
}