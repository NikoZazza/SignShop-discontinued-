<?php
/* @author xionbig
 * @link http://xionbig.altervista.org/SignShop 
 * @link http://forums.pocketmine.net/plugins/signshop.668/
 * @version 0.8.0 */

namespace SignShop\EventListener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerRespawnEvent;

class PlayerSpawnEvent implements Listener{
    protected $SignMain;
    
    public function __construct($SignShop){
        $this->SignMain = $SignShop; 
    }        
    
    public function playerSpawn(PlayerRespawnEvent $event){
        $player = $event->getPlayer();
        //$this->SignMain->respawnAllSign();    
       
        $authorized = false;
        
        if($this->SignMain->config->get("signCreated") == "admin" && $player->isOp()) $authorized = true;            
        if($this->SignMain->config->get("signCreated") == "all") $authorized = true;
            
        if($this->SignMain->getProvider()->existsPlayer($player->getDisplayName())){
            $get = $this->SignMain->getProvider()->getPlayer($player->getDisplayName());
            if($get["earned"] > 0)
                $player->sendMessage("[SignShop] You earned ". $get["earned"]." when you were offline");
            
            if($get["changed"] < $this->SignMain->config->get("lastChanged"))
                $get["authorized"] = $authorized;

            $get["earned"] = 0;
            $this->SignMain->getProvider()->setPlayer($player->getDisplayName(), $get);
        }else
            $this->SignMain->getProvider()->setPlayer($player->getDisplayName(), [
                "authorized" => $authorized,
                "changed" => time(),
                "echo" => true,
                "earned" => 0,
                "totEarned" => 0,
                "totSpent" => 0]);
    }   
}