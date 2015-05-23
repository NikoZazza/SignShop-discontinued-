<?php
/**
 * @author xionbig
 * @link http://xionbig.altervista.org/SignShop 
 * @link http://forums.pocketmine.net/plugins/signshop.668/
 * @version 1.0.0 
 */
namespace SignShop\EventListener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerRespawnEvent;

class PlayerSpawnEvent implements Listener{
    protected $SignShop;
    
    public function __construct($SignShop){
        $this->SignShop = $SignShop; 
    }        
    
    public function playerSpawn(PlayerRespawnEvent $event){
        $player = $event->getPlayer();
        
        $authorized = "denied";
        
        if($this->SignShop->getSetup()->get("signCreated") == "admin" && $player->isOp()) $authorized = "allow";            
        if($this->SignShop->getSetup()->get("signCreated") == "all") $authorized = "allow";
            
        if($this->SignShop->getProvider()->existsPlayer($player->getDisplayName())){
            $get = $this->SignShop->getProvider()->getPlayer($player->getDisplayName());
            
            if($get["changed"] < $this->SignShop->getSetup()->get("lastChanged")){
                $get["authorized"] = $authorized;
                $get["changed"] = time();
                $this->SignShop->getProvider()->setPlayer($player->getDisplayName(), $get);
            }
        }else{
            $this->SignShop->getProvider()->setPlayer($player->getDisplayName(), [
                "authorized" => $authorized,
                "changed" => time(),
                "echo" => true]);
        }
    }   
}