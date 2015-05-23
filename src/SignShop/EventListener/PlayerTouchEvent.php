<?php
/**
 * @author xionbig
 * @link http://xionbig.altervista.org/SignShop 
 * @link http://forums.pocketmine.net/plugins/signshop.668/
 * @version 1.0.0
 */
namespace SignShop\EventListener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\Player;

class PlayerTouchEvent implements Listener{
    private $SignShop;

    public function __construct($SignShop){
        $this->SignShop = $SignShop;
    }
    
    public function playerBlockTouch(PlayerInteractEvent $event){ 
        $block = $event->getBlock();
        if($block->getID() == Item::WALL_SIGN || $block->getID() == Item::SIGN_POST){
            $player = $event->getPlayer();   
            $signManager = $this->SignShop->getSignManager(); 
            $message = $this->SignShop->messageManager();
       
            if($signManager->existsSign($block)){
                $get = $signManager->getSign($block);  
                
                if(isset($this->SignShop->temp[$player->getDisplayName()])){   
                    switch($this->SignShop->temp[$player->getDisplayName()]["action"]){
                        case "remove":
                            if(strtolower($get["maker"]) == strtolower($player->getDisplayName())){
                                $signManager->removeSign($block);
                                $message->send($player, "The_Sign_successfully_removed"); 
                            }else                      
                                $message->send($player, "The_selected_Sign_is_not_your");           
                            break;                    
                        
                        case "view":
                            $message->send($player, "This_Sign_is_owned_by_@@", (string)$get["maker"]);
                            $message->send($player, "There_are_still_@@_blocks/items", (string)$get["available"]);
                            $message->send($player, "They_were_sold_@@_blocks/items_with_this_Sign", (string)$get["sold"]);
                            $message->send($player, "The_owner_has_earned_@@", (string)$get["earned"]);
                            break;
                        
                        case "set":
                            if(strtolower($get["maker"]) != strtolower($player->getDisplayName())){
                                $message->send($player, "The_selected_Sign_is_not_your"); 
                                break;
                            }
                            
                            switch($this->SignShop->temp[$player->getDisplayName()]["arg"]){
                                case "amount":
                                    $get["amount"] = $this->SignShop->temp[$player->getDisplayName()]["value"];
                                
                                    $signManager->setSign($block, $get);
                                    $message->send($player, "You_set_the_amount_of_the_Sign_in_@@", $get["amount"]);
                                    break;
                                
                                case "available": 
                                    $value = $this->SignShop->temp[$player->getDisplayName()]["value"];
                                    if(is_numeric($value)){ 
                                        if($get["available"] > $value){
                                            $item = Item::get($get["id"], $get["damage"], $get["available"] - $value);             
                                            if($player->getInventory()->canAddItem($item)){
                                                $player->getInventory()->addItem($item);
                                                $get["available"] = $value;
                                                        
                                                $signManager->setSign($block, $get);
                                                $message->send($player, "The_Sign_was_stocked_with_success"); 
                                            }else 
                                                $message->send($player, "You_need_space_to_get_the_items_from_the_Sign");
                                        }else{
                                            $item = Item::get($get["id"], $get["damage"], $value - $get["available"]);      
                                            if($this->hasItemPlayer($player, $item)){
                                                $this->removeItemPlayer($player, $item);
                                                $get["available"] = $value;

                                                $signManager->setSign($block, $get); 

                                                $message->send($player, "The_Sign_was_stocked_with_success");                                    
                                            }else
                                                $message->send($player, "You_do_not_have_enough_blocks_to_fill_the_Sign");
                                        }
                                    }else{
                                        $get["available"] = "unlimited";
                                        $signManager->setSign($block, $get);
                                    
                                        $message->send($player, "Now_this_Sign_has_the_unlimited_available", $get["cost"]);
                                    }
                                    $message->send($player, "You_set_the_available_of_the_Sign_in_@@", $get["available"]);
                                    break;
                                
                                case "cost":
                                    $get["cost"] = $this->SignShop->temp[$player->getDisplayName()]["value"];
                                
                                    $signManager->setSign($block, $get);
                                    $message->send($player, "The_cost_of_the_contents_of_this_Sign_is_now_@@" , $get["cost"]);
                                    break;

                                case "maker":
                                    $name = strtolower($this->SignShop->temp[$player->getDisplayName()]["name"]);
                                    if($this->SignShop->getProvider()->existsPlayer($name)){
                                        
                                        $get["maker"] = $name;                                
                                        $signManager->setSign($block, $get);
                                    
                                        $message->send($player, "Now_this_Sign_is_owned_by_@@", $name);
                                    }else
                                        $message->send($player, "The_player_@@_is_not_exists");
                                    break;
                            }
                            break;          
                    }
                    unset($this->SignShop->temp[$player->getDisplayName()]);
                    return;
                }
                if(strtolower($event->getPlayer()->getDisplayName()) == strtolower($get["maker"])){
                    $message->send($player, "You_can_not_buy_from_your_Sign");
                    return;
                }
                if($player->getGamemode() == 1){
                    $message->send($player, "You_can_not_buy_in_creative");
                    return;
                }
                        
                if($this->SignShop->getMoneyManager()->getMoney($player->getDisplayName()) < $get["cost"])
                    $message->send($player, "You_do_not_have_enough_money");   
                elseif($get["available"] != "unlimited" && $get["available"] - $get["amount"] < 0)
                    $message->send($player, "The_content_of_the_Sign_is_sold_out");
                else{
                    $item = Item::get($get["id"], $get["damage"], $get["amount"]);             
                    if($player->getInventory()->canAddItem($item)){
                        $this->SignShop->getMoneyManager()->addMoney($get["maker"], $get["cost"]);   
                        $this->SignShop->getMoneyManager()->addMoney($player->getDisplayName(), -($get["cost"]));

                        $player->getInventory()->addItem($item);
                               
                        if($get["available"] != "unlimited") 
                            $get["available"] -= $get["amount"];
                        $get["sold"] += $get["amount"];
                        $get["earned"] += $get["cost"];

                        $signManager->setSign($block, $get); 
                                                        
                        $message->send($player, "You_bought_the_contents_of_the_Sign");
                            
                        $maker = $this->getPlayer($get["maker"]);
                        if($maker instanceof Player && $this->SignShop->getProvider()->getPlayer($get["maker"])["echo"] != false)
                            $message->sendMessage($maker, "+".$get["cost"].$this->SignShop->getMoneyManager()->getValue()." ".str_replace ("@@", $player->getDisplayName(),$message->getMessage("payment_from_@@")));
                    }else
                        $message->send($player, "You_do_not_have_the_space_to_buy_the_contents_of_this_Sign");
                } 
            }
        }   
    }       
    
    private function removeItemPlayer(Player $player, Item $item){
        if($this->SignShop->getProvider()->getPlayer(strtolower($player->getDisplayName()))["authorized"] == "root") return true;
        $ris = $item->getCount();
        if($player->getGamemode() != 1){
            for($i = 0; $i <= $player->getInventory()->getSize(); $i = $i + 1){
                $inv = $player->getInventory()->getItem($i);
                if($inv->getID() == $item->getID() && $inv->getDamage() == $item->getDamage()){
                    $ris = $inv->getCount() - $ris;
                    if($ris <= 0){
                        $player->getInventory()->clear($i);
                        $ris = -($ris);
                    }else
                        $player->getInventory()->setItem($i, Item::get($item->getID(), $item->getDamage(), $ris));
                }
            }
        }
        return true;
    }

    private function hasItemPlayer(Player $player, Item $item){
        if($this->SignShop->getProvider()->getPlayer(strtolower($player->getDisplayName()))["authorized"] == "root") return true;
        $ris = 0;
        if($player->getGamemode() != 1){
            for($i = 0; $i <= $player->getInventory()->getSize(); ++$i){
                $inv = $player->getInventory()->getItem($i);
                if($inv->getID() == $item->getID() && $inv->getDamage() == $item->getDamage())
                    $ris = $ris + $inv->getCount();      
            }
        }
        if($ris >= $item->getCount())
            return true;
        return false;
    }
    
    public function getPlayer($player){
        $player = strtolower($player);
        return \pocketmine\Server::getInstance()->getPlayer($player);
    }   
}