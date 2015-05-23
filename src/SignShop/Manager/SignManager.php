<?php
/**
 * @author xionbig
 * @link http://xionbig.altervista.org/SignShop 
 * @link http://forums.pocketmine.net/plugins/signshop.668/
 * @version 1.0.0
 */
namespace SignShop\Manager;

use pocketmine\level\Position;
use pocketmine\Server;
use pocketmine\level\Level;
use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\tile\Sign;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\String;
use pocketmine\nbt\tag\Int;
use pocketmine\tile\Tile;

class SignManager {
    private $SignShop;
    private $signs = [];    
    
    public function __construct($SignShop) {
        $this->SignShop = $SignShop;
        if(count($SignShop->getProvider()->getAllSigns()) <= 0) return;
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
        $this->spawnSign($pos, $get);
        
        $this->SignShop->getProvider()->setSign($this->getTextPos($pos), $get);
    } 
    
    private function getWorld($world){
        return $world = str_replace(" ", "%", $world);
    }
    
    public function getSign(Position $pos){
        $pos = $this->getPos($pos);
        return $this->SignShop->getProvider()->getSign($this->getTextPos($pos));
    }
    
    private function spawnSign(Position $pos, $get = false){
        if(!$get || !isset($get))
            $get = $this->SignShop->getProvider()->getSign($this->getTextPos($pos));            
        
        if($get["available"] != "unlimited" && $get["available"] - $get["amount"] <= 0)
            $get["cost"] = "Out Of Stock";
        else{
            if($get["cost"] == 0) 
                $get["cost"] = "Price: FREE";
            else 
                $get["cost"] = "Price: ".$get["cost"].$this->SignShop->getMoneyManager()->getValue();
        }    
        if(!$pos->level->isChunkGenerated($pos->x >> 4, $pos->z >> 4)) 
            $pos->level->generateChunk($pos->x, $pos->z);
        
        if($pos->level->getBlockIdAt($pos->x, $pos->y, $pos->z) != Item::SIGN_POST || $pos->level->getBlockIdAt($pos->x, $pos->y, $pos->z) != Item::WALL_SIGN){
            if($pos->level->getBlockIdAt($pos->x, $pos->y-1, $pos->z) != Item::AIR)
                $pos->level->setBlock($pos, Block::get(Item::SIGN_POST, $get["direction"]), false, true);
            else
                $pos->level->setBlock($pos, Block::get(Item::WALL_SIGN), false, true);
            
        }
            
        
        $tile = $pos->getLevel()->getTile($pos); 

        if($tile instanceof Sign){
            $tile->setText("[SignShop]", $this->SignShop->getItems()->getName($get["id"], $get["damage"]), "Amount: x".$get["amount"], $get["cost"]);
            return;
        }
          
        $sign = new Sign($pos->level->getChunk($pos->x >> 4, $pos->z >> 4, true), new Compound(false, array(
            new Int("x", $pos->x),
            new Int("y", $pos->y),
            new Int("z", $pos->z),
            new String("id", Tile::SIGN),
            new String("Text1", "[SignShop]"),
            new String("Text2", $this->SignShop->getItems()->getName($get["id"], $get["damage"])),
            new String("Text3", "Amount: x".$get["amount"]),
            new String("Text4", $get["cost"])
            )));      
        $pos->level->addTile($sign);
        
    }   
    
    private function getTextPos(Position $pos){
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
        $world = trim($world);
        if(!$world || $world == ""){
            foreach($this->signs as $world => $var){
                $world = str_replace("%", " ", $world);
                $world = Server::getInstance()->getLevelByName($world);
                if($world instanceof Level){
                    foreach($var as $pos => $c){
                        $t = explode(":", $pos);
                        $this->spawnSign(new Position($t[0], $t[1], $t[2], $world));                
                    }               
                }  
            }   
            return true;
        }
    }
}
