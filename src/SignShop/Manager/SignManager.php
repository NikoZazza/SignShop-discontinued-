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
 * @link http://xionbig.netsons.org/plugins/SignShop 
 * @link http://forums.pocketmine.net/plugins/signshop.668/
 * @version 1.1.0
 */
namespace SignShop\Manager;

use SignShop\SignShop;
use pocketmine\Server;
use pocketmine\nbt\tag\Int;
use pocketmine\nbt\tag\String;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\tile\Sign;
use pocketmine\tile\Tile;
use pocketmine\nbt\tag\Compound;
use pocketmine\utils\TextFormat;

class SignManager{
    private $SignShop;
    private $signs = [];    
    
    public function __construct(SignShop $SignShop){
        $this->SignShop = $SignShop;
        if(count($SignShop->getProvider()->getAllSigns()) <= 0) 
            return;
        foreach($SignShop->getProvider()->getAllSigns() as $var => $c){
            $pos = explode(":", $var);
            $this->signs[$this->getWorld($pos[3])][$pos[0].":".$pos[1].":".$pos[2]] = true;            
        }
        foreach($this->signs as $world => $sign)
            ksort($this->signs[$world]);
        
        $this->reload();  
    }
    
    public function removeSign(Position $pos){
        $pos = $this->getPos($pos);
        unset($this->signs[$this->getWorld($pos->getLevel()->getName())][$pos->getX().":".$pos->getY().":".$pos->getZ()]);
        
        $this->SignShop->getProvider()->removeSign($this->getTextPos($pos));
        $pos->getLevel()->setBlock($pos, Block::get(0), true, true);
    } 
    
    public function existsSign(Position $pos){
        $pos = $this->getPos($pos);
        return isset($this->signs[$this->getWorld($pos->getLevel()->getName())][$pos->getX().":".$pos->getY().':'.$pos->getZ()]);        
    }
    
    public function setSign(Position $pos, array $get){
        $pos = $this->getPos($pos);
        if(!$this->existsSign($pos)){
            $this->signs[$this->getWorld($pos->getLevel()->getName())][$pos->getX().":".$pos->getY().':'.$pos->getZ()] = true;
            ksort($this->signs[$this->getWorld($pos->getLevel()->getName())]);    
        }
        
        $this->SignShop->getProvider()->setSign($this->getTextPos($pos), $get);
        $this->spawnSign($pos, $get);
    } 
    
    private function getWorld($world){
        return $world = str_replace(" ", "%", $world);
    }
    
    public function getSign(Position $pos){
        $pos = $this->getPos($pos);
        return $this->SignShop->getProvider()->getSign($this->getTextPos($pos));
    }
    
    public function spawnSign(Position $pos, $get = false){
        if(!$get || !isset($get))
            $get = $this->SignShop->getProvider()->getSign($this->getTextPos($pos));     
        
        if($pos->level->getBlockIdAt($pos->x, $pos->y, $pos->z) != Item::SIGN_POST && $pos->level->getBlockIdAt($pos->x, $pos->y, $pos->z) != Item::WALL_SIGN){
            if($pos->level->getBlockIdAt($pos->x, $pos->y - 1, $pos->z) != Item::AIR && $pos->level->getBlockIdAt($pos->x, $pos->y - 1, $pos->z) != Item::WALL_SIGN)
                $pos->level->setBlock($pos, Block::get(Item::SIGN_POST, $get["direction"]), true, true);
            else{
                $direction = 3;
                if($pos->level->getBlockIdAt($pos->x - 1 , $pos->y, $pos->z) != Item::AIR)
                    $direction = 5;
                elseif($pos->level->getBlockIdAt($pos->x + 1 , $pos->y, $pos->z) != Item::AIR)
                    $direction = 4;
                elseif($pos->level->getBlockIdAt($pos->x , $pos->y, $pos->z + 1) != Item::AIR)
                    $direction = 2;                      
                $pos->level->setBlock($pos, Block::get(Item::WALL_SIGN, $direction), true, true);    
            }            
        }            
        
        if($get["type"] == "sell"){
            if($get["need"] == -1)
                $get["need"] = "∞";
            $line = [TextFormat::GOLD."[SignSell]", 
                    TextFormat::ITALIC.$this->SignShop->getItems()->getName($get["id"], $get["damage"]), 
                    $get["available"]."/".$get["need"], 
                    $get["cost"].$this->SignShop->getMoneyManager()->getValue().TextFormat::BLACK." for ".$get["amount"]
                ];
        }else{
            if($get["available"] != "unlimited" && $get["available"] - $get["amount"] <= 0)
                $get["cost"] = TextFormat::DARK_RED."Out Of Stock";
            else{
                if($get["cost"] == 0) 
                    $get["cost"] = "Price: "."FREE";
                else 
                    $get["cost"] = "Price: ".$get["cost"].$this->SignShop->getMoneyManager()->getValue();
            }   
            
            $line = [TextFormat::GOLD."[SignBuy]", 
                    TextFormat::ITALIC.$this->SignShop->getItems()->getName($get["id"], $get["damage"]),
                    "Amount: x".$get["amount"],
                    $get["cost"]
                ];
        }
        
        $tile = $pos->getLevel()->getTile($pos); 
        if($tile instanceof Sign){            
            $tile->setText(... $line);
            return;
        }
        
        $sign = new Sign($pos->level->getChunk($pos->x >> 4, $pos->z >> 4, true), new Compound(false, array(
            new Int("x", $pos->x),
            new Int("y", $pos->y),
            new Int("z", $pos->z),
            new String("id", Tile::SIGN),
            new String("Text1", $line[0]),
            new String("Text2", $line[1]),
            new String("Text3", $line[2]),
            new String("Text4", $line[3])
            )));               
    }   
    
    private function getTextPos(Position $pos){
        $pos = $this->getPos($pos);
        return $pos->getX().":".$pos->getY().":".$pos->getZ().":".$this->getWorld($pos->getLevel()->getName());
    } 
        
    private function getPos(Position $pos){        
        $pos->x = (Int) $pos->getX();
        $pos->y = (Int) $pos->getY();
        $pos->z = (Int) $pos->getZ();
     
        return $pos;
    }
    
    public function reload($world = false){
        if(count($this->signs) <= 0) return false;

        if(empty($world)){
            foreach($this->signs as $world => $var){
                $world = Server::getInstance()->getLevelByName(str_replace("%", " ", $world));
                if($world instanceof Level){
                    foreach($var as $pos => $c){
                        $t = explode(":", $pos);
                        $this->spawnSign(new Position($t[0], $t[1], $t[2], $world));                
                    }               
                }  
            }   
            return true;
        }else{            
            $world = Server::getInstance()->getLevelByName(str_replace("%", " ", trim($world)));
            foreach($this->signs[$world] as $world => $var){                
                foreach($var as $pos => $c){
                    $t = explode(":", $pos);
                    $this->spawnSign(new Position($t[0], $t[1], $t[2], $world));                
                }              
                  
            }
        }
    }
    
    public function onDisable(){
        unset($this->signs);
    }
}
