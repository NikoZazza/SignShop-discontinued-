<?php
/* @author xionbig
 * @link http://xionbig.altervista.org/SignShop 
 * @link http://forums.pocketmine.net/plugins/signshop.668/
 * @version 0.9.1 */

namespace SignShop\EventListener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;

class PlayerTouchEvent implements Listener{
    private $SignMain;

    public function __construct($SignShop){
        $this->SignMain = $SignShop;
    }
    
    public function playerBlockTouch(PlayerInteractEvent $event){ 
        if($event->getBlock()->getID() == Item::SIGN || $event->getBlock()->getID() == Item::SIGN_POST){
            $player = $event->getPlayer();   
                       
            $world = str_replace(" ", "%", $event->getBlock()->getLevel()->getName());
            $var = (Int)$event->getBlock()->getX().":".(Int)$event->getBlock()->getY().":".(Int)$event->getBlock()->getZ().":".$world;
            
            if($this->SignMain->getProvider()->existsSign($var)){
                $get = $this->SignMain->getProvider()->getSign($var);  
                
                if(isset($this->SignMain->temp[$player->getDisplayName()])){   
                    switch($this->SignMain->temp[$player->getDisplayName()]["action"]){
                        case "refill":
                            if(strtolower($get["maker"]) == strtolower($player->getDisplayName())){
                                $item = Item::get($get["id"], $get["damage"], $this->SignMain->temp[$player->getDisplayName()]["amount"]);
                                if($this->hasItemPlayer($player, $item) && $this->removeItemPlayer($player, $item)){
                                    $get["available"] += $item->getCount();
                                    
                                    $this->SignMain->getProvider()->setSign($var, $get); 
                                                    
                                    $this->SignMain->respawnSign($var);
                                    $event->getPlayer()->sendMessage("[SignShop] ". $this->SignMain->getMessages()["The_Sign_was_stocked_with_success"]);                                    
                                }else
                                    $event->getPlayer()->sendMessage("[SignShop] ".$this->SignMain->getMessages()["You_do_not_have_enough_blocks_to_fill_the_Sign"]);
                            }else                      
                                $event->getPlayer()->sendMessage("[SignShop] ". $this->SignMain->getMessages()["The_selected_Sign_is_not_your"]); 
                            break; 

                        case "view": 
                            $mex = [str_replace("@@", $get["maker"], $this->SignMain->getMessages()["This_Sign_is_owned_by_@@"]),
                                    str_replace("@@", date("M-d h:ia", $get["time"]), $this->SignMain->getMessages()["This_Sign_was_created_@@"]), 
                                    str_replace("@@", $get["available"], $this->SignMain->getMessages()["There_are_still_@@_blocks/items"]), 
                                    str_replace("@@", $get["sold"], $this->SignMain->getMessages()["They_were_sold_@@_blocks/items_with_this_Sign"]), 
                                    str_replace("@@", $get["earned"]. $this->SignMain->getMoneyManager()->getValue(), $this->SignMain->getMessages()["The_owner_has_earned_@@"])
                                ];
                            
                            foreach($mex as $message)
                                $event->getPlayer()->sendMessage("[SignShop] ". $message);
                            break;
                        
                        case "set":
                            if(strtolower($get["maker"]) != strtolower($player->getDisplayName())){
                                $event->getPlayer()->sendMessage("[SignShop] ". $this->SignMain->getMessages()["The_selected_Sign_is_not_your"]); 
                                break;
                            }
                            
                            switch($this->SignMain->temp[$player->getDisplayName()]["arg"]){
                                case "amount":
                                    $get["amount"] = $this->SignMain->temp[$player->getDisplayName()]["value"];
                                
                                    $this->SignMain->getProvider()->setSign($var, $get);
                                
                                    $event->getPlayer()->sendMessage("[SignShop] ". str_replace("@@", $get["amount"], $this->SignMain->getMessages()["You_set_the_amount_of_the_Sign_in_@@"]));
                                    break;
                                
                                case "cost":
                                    $get["cost"] = $this->SignMain->temp[$player->getDisplayName()]["value"];
                                
                                    $this->SignMain->getProvider()->setSign($var, $get);
                            
                                    $event->getPlayer()->sendMessage("[SignShop] ". str_replace("@@", $get["cost"], $this->SignMain->getMessages()["The_cost_of_the_contents_of_this_Sign_is_now_@@"]));
                                    break;

                                case "maker":
                                    $name = strtolower($this->SignMain->temp[$player->getDisplayName()]["name"]);
                                    if($this->SignMain->getProvider()->existsPlayer($name)){
                                        
                                        $get["maker"] = $name;                                
                                        $this->SignMain->setSign($var, $get);
                                    
                                        $event->getPlayer()->sendMessage("[SignShop] ". str_replace("@@", $name, $this->SignMain->getMessages()["Now_this_Sign_is_owned_by_@@"]));
                                    }else
                                        $event->getPlayer()->sendMessage("[SignShop] ".$this->SignMain->getMessages()["The_player_was_not_found"]);
                                    break;
                                
                                case "unlimited": 
                                    $get["available"] = "unlimited";
                                    $this->SignMain->getProvider()->setSign($var, $get);
                                    
                                    $event->getPlayer()->sendMessage("[SignShop] ". str_replace("@@", $get["cost"], "Now_this_Sign_has_the_unlimited_available"));
                                    break;
                            }
                            $this->SignMain->respawnSign($var);
                            break;          
                    }
                    unset($this->SignMain->temp[$player->getDisplayName()]);
                    return;
                }
                if(strtolower($event->getPlayer()->getDisplayName()) == strtolower($get["maker"])){
                    $player->sendMessage("[SignShop] ".$this->SignMain->getMessages()["You_can_not_buy_from_your_Sign"]);
                    return;
                }
                if($player->getGamemode() == 1){
                    $player->sendMessage("[SignShop] ". $this->SignMain->getMessages()["You_can_not_buy_in_creative"]);
                    return;
                }
                        
                if($this->SignMain->getMoneyManager()->getMoney($player->getDisplayName()) < $get["cost"])
                    $player->sendMessage("[SignShop] ". $this->SignMain->getMessages()["You_do_not_have_enough_money"]);   
                elseif($get["available"] != "unlimited" && $get["available"] - $get["amount"] < 0)
                    $player->sendMessage("[SignShop] ". $this->SignMain->getMessages()["The_content_of_the_Sign_is_sold_out"]);
                else{
                    $item = Item::get($get["id"], $get["damage"], $get["amount"]);             
                    if($player->getInventory()->canAddItem($item)){
                        $this->SignMain->getMoneyManager()->addMoney($get["maker"], $get["cost"]);   
                        $this->SignMain->getMoneyManager()->addMoney($player->getDisplayName(), -($get["cost"]));

                        $player->getInventory()->addItem($item);
                               
                        if($get["available"] != "unlimited") 
                            $get["available"] -= $get["amount"];
                        $get["sold"] += $get["amount"];
                        $get["earned"] += $get["cost"];

                        $this->SignMain->getProvider()->setSign($var, $get); 
                                                        
                        $player->sendMessage("[SignShop] ". $this->SignMain->getMessages()["You_bought_the_contents_of_the_Sign"]);
                            
                        $maker = $this->getPlayer($get["maker"]);
                        if($maker != false && $this->SignMain->getProvider()->getPlayer($get["maker"])["echo"] != false)
                            $maker->sendMessage("[SignShop] +".$get["cost"].$this->SignMain->getMoneyManager()->getValue()." payment from ".$player->getDisplayName());
                        else{
                            $getPlayer = $this->SignMain->getProvider()->getPlayer($get["maker"]);
                            $getPlayer["earned"] += $get["cost"]; 
                            $this->SignMain->getProvider()->setPlayer($get["maker"], $getPlayer);
                        }
                    }else
                        $event->getPlayer()->sendMessage("[SignMain] ". $this->SignMain->getMessages()["You_do_not_have_the_space_to_buy_the_contents_of_this_Sign"]);
                } 
                           
                $this->SignMain->respawnSign($var);
            }
        }   
    }       
    
    private function removeItemPlayer(Player $player, Item $item){
        if($this->SignMain->getProvider()->getPlayer(strtolower($player->getDisplayName()))["authorized"] == "super") return true;
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
        if($this->SignMain->getProvider()->getPlayer(strtolower($player->getDisplayName()))["authorized"] == "super") return true;
        $ris = 0;
        if($player->getGamemode() != 1){
            for($i = 0; $i <= $player->getInventory()->getSize(); ++$i){
                $inv = $player->getInventory()->getItem($i);
                if($inv->getID() == $item->getID() && $inv->getDamage() == $item->getDamage())
                    $ris = $ris + $inv->getCount();      
            }
        }
        if($ris >= $item->getCount()) return true;
        return false;
    }
    
    public function getPlayer($player){
        return $this->SignMain->getPlayer($player);    
    }
}