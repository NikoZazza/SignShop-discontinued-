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
namespace SignShop\EventListener;

use SignShop\SignShop;
use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;

class PlayerBlockBreakEvent implements Listener{
    private $SignShop;

    public function __construct(SignShop $SignShop){
        $this->SignShop = $SignShop;
    }
    
    public function playerBlockBreak(BlockBreakEvent $event){
        $block = $event->getBlock();
        if($block->getID() == Item::WALL_SIGN || $block->getID() == Item::SIGN_POST){
            $player = $event->getPlayer();
            $signManager = $this->SignShop->getSignManager();
            
            if($signManager->existsSign($block)){
                $get = $signManager->getSign($block);
                
                if(strtolower($get["maker"]) == strtolower($player->getName())){
                    if(is_numeric($get["available"]) && $get["need"] > -1){
                        while($get["available"] > 0){                            
                            $item = new Item($get["id"], $get["damage"], $get["amount"]);
                            if($player->getInventory()->canAddItem($item)){
                                if($get["available"] - $get["amount"] < 0){
                                    break;
                                }
                                $player->getInventory()->addItem($item);                                
                                $get["available"] -= $get["amount"]; 
                            }else{
                                $this->SignShop->messageManager()->send($player, "You_need_to_free_up_space_from_your_inventory_to_remove_this_Sign");
                                $event->setCancelled(); 
                                return;
                            }                            
                        }   
                    }
                    $signManager->removeSign($block);
                    $this->SignShop->messageManager()->send($player, "The_Sign_successfully_removed");   
                    return;
                }else{
                    $this->SignShop->messageManager()->send($player, "The_selected_Sign_is_not_your");
                    $event->setCancelled();   
                }
            }
        }
        $pos = $event->getBlock();
        if($pos->level->getBlockIdAt($pos->x, $pos->y + 1, $pos->z) == Item::SIGN_POST){
            $pos->level->setBlock($pos, Block::get(0), true, true);
            
            $pos->y += 1;
            
            if($this->SignShop->getSignManager()->existsSign($pos))
                $this->SignShop->getSignManager()->spawnSign($pos);
        }       
    }    
}