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
namespace SignShop\EventListener;

use SignShop\SignShop;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerRespawnEvent;

class PlayerSpawnEvent implements Listener{
    private $SignShop;
    
    public function __construct(SignShop $SignShop){
        $this->SignShop = $SignShop; 
    }        
    
    public function playerSpawn(PlayerRespawnEvent $event){
        $player = $event->getPlayer();
              
        $authorized = "denied";
        
        if($this->SignShop->getSetup()->get("signCreated") == "admin" && $player->isOp()) $authorized = "allow";            
        if($this->SignShop->getSetup()->get("signCreated") == "all") $authorized = "allow";
            
        if($this->SignShop->getProvider()->existsPlayer($player->getName())){
            $get = $this->SignShop->getProvider()->getPlayer($player->getName());
            
            if($get["changed"] < $this->SignShop->getSetup()->get("lastChanged")){
                $get["authorized"] = $authorized;
                $get["changed"] = time();
                $this->SignShop->getProvider()->setPlayer($player->getName(), $get);
            }
        }else{
            $this->SignShop->getProvider()->setPlayer($player->getName(), [
                "authorized" => $authorized,
                "changed" => time(),
                "echo" => true]);
        }
    }   
}