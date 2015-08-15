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
namespace SignShop\Provider;

use SignShop\SignShop;
use pocketmine\utils\Config;

class YAMLProvider{
    private $sign, $plr;

    public function __construct(SignShop $SignShop){
        $dataResources = $SignShop->getDataFolder()."/resources/";
        
        if(file_exists($dataResources. "player_authorized.yml")) rename($dataResources."player_authorized.yml", $dataResources."player.yml");
       
        $this->sign = new Config($dataResources. "sign.yml", Config::YAML);
        $this->plr = new Config($dataResources. "player.yml", Config::YAML);
        
        if($SignShop->getSetup()->get("version") != "oneone"){
            foreach($this->plr->getAll() as $var => $c){
                if(!isset($c["earned"])) $c["earned"] = 0; 
                if(!isset($c["totEarned"])) $c["totEarned"] = 0; 
                if(!isset($c["totSpent"])) $c["totSpent"] = 0;
                if(!isset($c["echo"])) $c["echo"] = true;
                
                if($c["authorized"] == "super") $c["authorized"] = "root";
                if($c["authorized"] == "auth") $c["authorized"] = "allowed";
                if($c["authorized"] == "unauth") $c["authorized"] = "denied";
                
                $this->plr->set($var, $c);
                $this->plr->save();               
            }
            
            foreach($this->sign->getAll() as $var => $c){
                if(!isset($c["type"])) $c["type"] = "buy";
                if(!isset($c["need"])) $c["need"] = 0;
                if(!isset($c["sold"])) $c["sold"] = 0;
                if(!isset($c["earned"])) $c["earned"] = 0;       
                if(!isset($c["direction"])) $c["direction"] = 0;
                    
                if(!isset($c["damage"])){
                    $c["damage"] = $c["meta"];
                    unset($c["meta"]);
                }
                
                $pos = explode(":", $var);
                
                $pos[3] = str_replace("%", " ", $pos[3]);
                $this->sign->remove($var);
                
                $this->sign->set(implode(":",$pos), $c);
                $this->sign->save();
            }        
            
            $SignShop->getSetup()->set("version", "oneone");
            $SignShop->getSetup()->save();   
        }             
    }
            
    public function getAllPlayers(){
        return $this->plr->getAll();
    }

    public function existsPlayer($player){
        return $this->plr->exists(strtolower(trim($player)));
    }
    
    public function setPlayer($player, array $data){
        $player = strtolower(trim($player));

        $this->plr->set($player, $data);    
        $this->plr->save();
    }
    
    public function getPlayer($player){
        $player = strtolower(trim($player));
        if($this->plr->exists($player))
            return $this->plr->get($player);
        return; 
    }  
    
    public function removePlayer($player){
        $player = strtolower(trim($player));
        if($this->plr->exists($player)){
            $this->plr->remove($player);
            $this->plr->save();            
        }
    }
    
    public function getAllSigns(){
        return $this->sign->getAll();
    }
    
    public function existsSign($var){
        return $this->sign->exists($var);
    }
    
    public function setSign($var, array $data){
        $this->sign->set($var, $data);
        $this->sign->save();
    }
    
    public function getSign($var){
        if($this->existsSign($var))
            return $this->sign->get($var);
        return;
    }
    
    public function removeSign($var){
        if($this->existsSign($var)){
            $this->sign->remove($var);
            $this->sign->save();
        }
    }
    
    public function onDisable(){
        if($this->sign instanceof Config)
            $this->sign->save();
        if($this->plr instanceof Config)
            $this->plr->save();
    }
}