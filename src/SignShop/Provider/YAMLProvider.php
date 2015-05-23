<?php
/**
 * @author xionbig
 * @link http://xionbig.altervista.org/SignShop 
 * @link http://forums.pocketmine.net/plugins/signshop.668/
 * @version 1.0.0
 */
namespace SignShop\Provider;

use pocketmine\utils\Config;

class YAMLProvider{
    private $sign, $plr;
    
    public function __construct($SignShop){
        $dataResources = $SignShop->getDataFolder()."/resources/";
        
        if(file_exists($dataResources. "player_authorized.yml")) rename($dataResources."player_authorized.yml", $dataResources."player.yml");
       
        $this->sign = new Config($dataResources. "sign.yml", Config::YAML);
        $this->plr = new Config($dataResources. "player.yml", Config::YAML);
        
        if($SignShop->getSetup()->get("version") != "ninety" && $SignShop->getSetup()->get("version") != "one"){
            foreach($this->plr->getAll() as $var => $c){
                $c["earned"] = 0; 
                $c["totEarned"] = 0; 
                $c["totSpent"] = 0;
                $c["echo"] = true;
                
                if($c["authorized"] == "super") $c["authorized"] = "root";
                elseif($c["authorized"] == "auth") $c["authorized"] = "allow";
                elseif($c["authorized"] == "unauth") $c["authorized"] = "denied";
                
                $this->plr->set($var, array_merge($c));
                $this->plr->save();               
            }
            foreach($this->sign->getAll() as $var => $c){
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
                
                $this->sign->set($pos, array_merge($c));
                $this->sign->save();
            }        
            
            $SignShop->getSetup()->set("version", "one");
            $SignShop->getSetup()->save();   
        }             
    }
            
    public function getAllPlayers(){
        return $this->plr->getAll();
    }

    public function existsPlayer($player){
        return $this->plr->exists(strtolower($player));
    }
    
    public function setPlayer($player, array $data){
        $player = strtolower($player);

        $this->plr->set($player, array_merge($data));    
        $this->plr->save();
    }
    
    public function getPlayer($player){
        $player = strtolower($player);
        if($this->plr->exists($player))
            return $this->plr->get($player);
        return; 
    }  
    
    public function removePlayer($player){
        $player = strtolower($player);
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
        $this->sign->set($var, array_merge($data));
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
        $this->sign->save();
        $this->plr->save();
    }
}