<?php
/**
 * @author xionbig
 * @link http://xionbig.altervista.org/SignShop 
 * @link http://forums.pocketmine.net/plugins/signshop.668/
 * @version 1.0.0 
 */
namespace SignShop\EventListener;

use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\item\Item;

class PlayerBlockBreakEvent implements Listener{
    private $SignShop;

    public function __construct($SignShop){
        $this->SignShop = $SignShop;
    }
    
    public function playerBlockBreak(BlockBreakEvent $event){
        $block = $event->getBlock();
        if($block->getID() == Item::WALL_SIGN || $block->getID() == Item::SIGN_POST){
            $player = $event->getPlayer();
            
            $signManager = $this->SignShop->getSignManager();
            
            if($signManager->existsSign($block)){
                $get = $signManager->getSign($block);
                
                if($this->SignShop->getProvider()->getPlayer($player->getDisplayName())["authorized"] == "root"){
                    $signManager->removeSign($block);
                    $this->SignShop->messageManager()->send($player, "The_Sign_successfully_removed");
                    return;
                }
                
                if(strtolower($get["maker"]) == strtolower($player->getDisplayName())){
                    if($get["available"] != "unlimited")
                        $item = Item::get($get["id"], $get["damage"], $get["available"]);
                    else
                        $item = Item::get(0, 0, 0);
                    
                    if($player->getInventory()->canAddItem($item)){
                        $player->getInventory()->addItem($item);
                              
                        $signManager->removeSign($block);
                        
                        $this->SignShop->messageManager()->send($player, "The_Sign_successfully_removed");
                    }else{
                        $this->SignShop->messageManager()->send($player, "You_need_to_free_up_space_from_your_inventory_to_remove_this_Sign");
                        $event->setCancelled();
                    }                        
                }else{
                    $this->SignShop->messageManager()->send($player, "The_selected_Sign_is_not_your");
                    $event->setCancelled();   
                }
            }
        }
    }
}