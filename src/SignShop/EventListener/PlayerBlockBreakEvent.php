<?php
/* @author xionbig
 * @link http://xionbig.altervista.org/SignShop 
 * @link http://forums.pocketmine.net/plugins/signshop.668/
 * @version 0.9.1 */

namespace SignShop\EventListener;

use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\item\Item;

class PlayerBlockBreakEvent implements Listener{
    private $SignMain;

    public function __construct($SignShop){
        $this->SignMain = $SignShop;
    }
    
    public function playerBlockBreak(BlockBreakEvent $event){
        if($event->getBlock()->getID() == Item::SIGN || $event->getBlock()->getID() == Item::SIGN_POST){
            $player = $event->getPlayer();
            
            $world = str_replace(" ", "%", $event->getBlock()->getLevel()->getName());            
            $var = (Int)$event->getBlock()->getX().":".(Int)$event->getBlock()->getY().":".(Int)$event->getBlock()->getZ().":".$world;
            
            if($this->SignMain->getProvider()->existsSign($var)){
                $get = $this->SignMain->getProvider()->getSign($var);
                
                if(strtolower($get["maker"]) == strtolower($player->getDisplayName())){
                    if($get["available"] != "unlimited")
                        $item = Item::get($get["id"], $get["damage"], $get["available"]);
                    else
                        $item = Item::get(0, 0, 0);
                    
                    if($player->getInventory()->canAddItem($item)){
                        $player->getInventory()->addItem($item);
                              
                        $this->SignMain->getProvider()->removeSign($var);
                        
                        $player->sendMessage("[SignShop] ". $this->SignMain->getMessages()["The_Sign_successfully_removed"]);
                    }else{
                        $player->sendMessage("[SignShop] ". $this->SignMain->getMessages()["You_need_to_free_up_space_from_your_inventory_to_remove_this_Sign"]);
                        $event->setCancelled();
                    }                        
                }else{
                    $event->getPlayer()->sendMessage("[SignShop] ". $this->SignMain->getMessages()["The_selected_Sign_is_not_your"]);
                    $event->setCancelled();   
                }
            }
        }
    }
}