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
 * @link http://xionbig.eu/plugins/SignShop 
 * @link http://forums.pocketmine.net/plugins/signshop.668/
 * @version 1.1.0
 */
namespace SignShop\Provider;

use SignShop\SignShop;
use SignShop\Task\TaskPingMySQL;

class MySQLProvider{
    private $database;    
    
    public function __construct(SignShop $SignShop){
        $config = $SignShop->getSetup()->get("dataProviderSettings");
                
        if(!isset($config["host"]) or !isset($config["user"]) or !isset($config["password"]) or !isset($config["database"])){
            $SignShop->getLogger()->critical("Invalid MySQL settings");
            $SignShop->getServer()->shutdown();
            return;
	}
	
        $this->database = new \mysqli($config["host"], $config["user"], $config["password"], $config["database"], isset($config["port"]) ? $config["port"] : 3306);
        
        if($this->database->connect_error){
            $SignShop->getLogger()->critical("Couldn't connect to MySQL: ". $this->database->connect_error);
            $SignShop->getServer()->shutdown();
            return;
	}
        
        $this->database->query('CREATE TABLE IF NOT EXISTS sign (var varchar(255), id int(5), damage int(5), amount int(2), available varchar(15), cost int(15), maker varchar(255), sold int(50), earned int(50), direction int(2), type VARCHAR(4) DEFAULT "buy", need INT(15))');
        $this->database->query('CREATE TABLE IF NOT EXISTS plr (player varchar(255), authorized varchar(10), changed int(15), echo varchar(6))'); 
	
        if($SignShop->getSetup()->get("version") != "oneone"){
            $this->database->query("ALTER TABLE sign ADD type VARCHAR(4) DEFAULT 'buy', need INT(15)");
            
            $SignShop->getSetup()->set("version", "oneone");
        }
        
        
        $SignShop->getServer()->getScheduler()->scheduleRepeatingTask(new TaskPingMySQL($SignShop), 600);
    }
    
    public function getAllPlayers(){
        $return = [];
        
        $query = $this->database->query("SELECT * FROM plr WHERE 1");
        if($query instanceof \mysqli_result){
            while($data = $query->fetch_array(MYSQLI_ASSOC))
                $return[$data["player"]] = $data;
            
            $query->free();
            return $return;
        }
        return false;
    }
    
    public function existsPlayer($player){
        $player = strtolower($player);
        if($this->getPlayer($player) != false) 
            return true;
        return false;
    }
    
    public function setPlayer($player, array $data){
        $player = strtolower(trim($player));
        if(!$this->existsPlayer($player))        
            $this->database->query("INSERT INTO plr (player, authorized, changed, echo) VALUES ('".$this->database->escape_string($player)."', '".$this->database->escape_string($data["authorized"])."', ".intval($data["changed"]).", '".$this->database->escape_string($data["echo"])."')");
        else
            $this->database->query("UPDATE plr SET authorized = '".$this->database->escape_string($data["authorized"])."', changed = ".intval($data["changed"]).", echo = '".$this->database->escape_string($data["echo"])."' WHERE player = '".$this->database->escape_string($player)."' ");
    }
    
    public function getPlayer($player){
        $player = strtolower(trim($player));
        $query = $this->database->query("SELECT * FROM plr WHERE player = '".$this->database->escape_string($player)."' ");
        
        if($query instanceof \mysqli_result){
            $data = $query->fetch_array(MYSQLI_ASSOC);
            if(isset($data["player"]) && $data["player"] === $player){
                unset($data["player"]);
                $query->free();
                return $data;
            }
        }
        return false;
    }
    
    public function removePlayer($player){
        $player = strtolower(trim($player));
        $query = $this->database->query("DELETE FROM plr WHERE player = '".$this->database->escape_string($player)."' ");
        $query->free();
    }
    
    public function getAllSigns(){
        $return = [];
        $query = $this->database->query("SELECT * FROM sign WHERE 1");
        if($query instanceof \mysqli_result){
            while($data = $query->fetch_array(MYSQLI_ASSOC))
                $return[$data["var"]] = $data;
            
            $query->free();
            return $return;
        }
        return false;
    }
    
    public function setSign($var, array $data){
        $var = trim($var);
        if(!$this->existsSign($var))        
            $this->database->query("INSERT INTO sign (var, id, damage, amount, available, cost, maker, sold, earned, direction, need, type) VALUES ('".$this->database->escape_string($var)."' , ".intval($data["id"])." , ".intval($data["damage"])." , ".intval($data["amount"])." , '".$this->database->escape_string($data["available"])."' , ".intval($data["cost"])." , '".$this->database->escape_string($data["maker"])."' , ".intval($data["sold"])." , ".intval($data["earned"])." , ".intval($data["direction"]).", '".intval($data["need"])."', '".$this->database->escape_string($data["available"])."' )");
        else 
            $this->database->query("UPDATE sign SET id = ".intval($data["id"]).", damage = ".intval($data["damage"]).", amount = ".intval($data["amount"]).", available = '".$this->database->escape_string($data["available"])."', cost = ".intval($data["cost"]).", maker = '".$this->database->escape_string($data["maker"])."', sold = ".intval($data["sold"]).", earned = ".intval($data["earned"]).", direction = ".intval($data["direction"]).", need = ".intval($data["need"]).", type = '".$this->database->escape_string($data["available"])."' WHERE var = '".$this->database->escape_string($var)."'");        
    }
    
    public function getSign($var){        
        $query = $this->database->query("SELECT * FROM sign WHERE var = '".$this->database->escape_string($var)."' ");
        
        if($query instanceof \mysqli_result){
            $data = $query->fetch_array(MYSQLI_ASSOC);
            if(isset($data["var"]) && $data["var"] === $var){
                unset($data["var"]);
                $query->free();
                return $data;
            }
        }
        return false;
    }
    
    public function removeSign($var){
        $query = $this->database->query("SELECT * FROM sign WHERE var = '".$this->database->escape_string($var)."' ");
    }
    
    public function existsSign($var){
        if($this->getSign($var) != false) 
            return true;
        return false;
    }
    
    public function ping(){
        $this->database->ping();
    }
    
    public function onDisable(){
        if($this->database instanceof \mysqli)
            $this->database->close();
    }
}
