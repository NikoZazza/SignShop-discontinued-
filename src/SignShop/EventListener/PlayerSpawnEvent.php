<?php
/* @author xionbig
 * @link http://xionbig.altervista.org/SignShop 
 * @link http://forums.pocketmine.net/plugins/signshop.668/
 * @version 0.9.1 */

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
        $this->SignMain->respawnAllSign();    
       
        $authorized = "unauth";
        
        if($this->SignMain->getSetup()->get("signCreated") == "admin" && $player->isOp()) $authorized = "auth";            
        if($this->SignMain->getSetup()->get("signCreated") == "all") $authorized = "auth";
            
        if($this->SignMain->getProvider()->existsPlayer($player->getDisplayName())){
            $get = $this->SignMain->getProvider()->getPlayer($player->getDisplayName());
            if($get["earned"] > 0)
                $player->sendMessage("[SignShop] ". str_replace("@@", $get["earned"], $this->SignMain->getMessages()["You_earned_@@_when_you_were_offline"]));
            
            if($get["changed"] < $this->SignMain->getSetup()->get("lastChanged"))
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