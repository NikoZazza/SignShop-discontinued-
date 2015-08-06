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
 * @link http://xionbig.eu/plugins/SignShop 
 * @link http://forums.pocketmine.net/plugins/signshop.668/
 * @version 1.1.0
 */
namespace SignShop\EventListener;

use SignShop\SignShop;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\item\Item;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;

class PlayerTouchEvent implements Listener{
    private $SignShop;
    private $signManager, $message;
    
    public function __construct(SignShop $SignShop){
        $this->SignShop = $SignShop;
        $this->signManager = $this->SignShop->getSignManager(); 
        $this->message = $this->SignShop->messageManager();     
    }
    
    public function playerBlockTouch(PlayerInteractEvent $event){ 
        $block = $event->getBlock();
        if($block->getID() == Item::WALL_SIGN || $block->getID() == Item::SIGN_POST){
            $player = $event->getPlayer();   
            $signManager = $this->signManager; 
            $message = $this->message;            
            
            if($signManager->existsSign($block)){
                $get = $signManager->getSign($block);  
                                
                if(isset($this->SignShop->temp[$player->getName()])){   
                    switch($this->SignShop->temp[$player->getName()]["action"]){
                        case "empty":
                            if($get["type"] != "sell")
                            if($get["available"] <= 0){
                                $message->send($player, "The_Sign_is_empty");
                                return;
                            }
                            while($get["available"] > 0){                            
                                $item = new Item($get["id"], $get["damage"], $get["amount"]);
                                if($player->getInventory()->canAddItem($item)){
                                    if($get["available"] - $get["amount"] < 0){
                                        $message->send($player, "The_Sign_has_been_emptied_successfully");                                    
                                        break;
                                    }
                                    $player->getInventory()->addItem($item);                                
                                    $get["available"] -= $get["amount"];                                
                                }else{
                                    $message->send($player, "You_do_not_have_enough_space_to_get_the_contents_of_the_Sign");    
                                    break;
                                }                            
                            }                              
                            $signManager->setSign($block, $get);      
                            return;
                            
                        case "remove":
                            if(strtolower($get["maker"]) == strtolower($player->getName())){
                                $signManager->removeSign($block);
                                $message->send($player, "The_Sign_successfully_removed"); 
                                return;
                            }else{                
                                if($this->SignShop->getProvider()->getPlayer($player->getName())["authorized"] == "root"){
                                    $signManager->removeSign($block);
                                    $message->send($player, "The_Sign_successfully_removed");
                                    return;
                                }else
                                    $message->send($player, "The_selected_Sign_is_not_your");           
                            }
                            break;                    
                        
                        case "view":
                            $message->send($player, "This_Sign_is_owned_by_@@", (string)$get["maker"]);
                            $message->send($player, "There_are_still_@@_blocks/items", (string)$get["available"]);
                            if($get["type"] != "sell"){
                                $message->send($player, "They_were_sold_@@_blocks/items_with_this_Sign", (string)$get["sold"]);
                                $message->send($player, "The_owner_has_earned_@@", (string)$get["earned"]);
                            }
                            break;
                        
                        case "set":
                            if(strtolower($get["maker"]) != strtolower($player->getName())){
                                $message->send($player, "The_selected_Sign_is_not_your"); 
                                break;
                            }
                            
                            switch($this->SignShop->temp[$player->getName()]["arg"]){
                                case "amount":
                                    if($this->SignShop->temp[$player->getName()]["value"] > Item::get($get["id"], $get["damage"])->getMaxStackSize())
                                        $message->send($player, "The_amount_must_be_less_than_@@", Item::get($get["id"], $get["damage"])->getMaxStackSize());
                                    else{                                        
                                        $get["amount"] = $this->SignShop->temp[$player->getName()]["value"];

                                        $signManager->setSign($block, $get);
                                        $message->send($player, "You_set_the_amount_of_the_Sign_in_@@", $get["amount"]);
                                    }
                                    break;
                                case "available": 
                                    if($get["type"] == "sell"){
                                        $message->send($player, "This_feature_is_not_yet_available_for_SignSell");
                                        break;
                                    }                               
                                    $value = $this->SignShop->temp[$player->getName()]["value"];
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
                                    $cost = $this->SignShop->temp[$player->getName()]["value"];
                                    if($get["type"] == "sell"){
                                        if($this->SignShop->getMoneyManager()->getMoney($player->getName()) < $cost * ($need/$amount) && $this->SignShop->getProvider()->getPlayer($player->getName())["authorized"] != "root"){
                                            $cost = $get["cost"];
                                        }                                        
                                    }
                                    $get["cost"] = $cost;
                                    $signManager->setSign($block, $get);
                                    $message->send($player, "The_cost_of_the_contents_of_this_Sign_is_now_@@" , $get["cost"]);
                                    break;

                                case "maker":
                                    $name = strtolower($this->SignShop->temp[$player->getName()]["name"]);
                                    if($this->SignShop->getProvider()->existsPlayer($name)){
                                        
                                        $get["maker"] = $name;                                
                                        $signManager->setSign($block, $get);
                                    
                                        $message->send($player, "Now_this_Sign_is_owned_by_@@", $name);
                                    }else
                                        $message->send($player, "The_player_@@_is_not_exists", $name);
                                    break;
                            }
                            break;          
                    }
                    $signManager->spawnSign($block, $get);
                    unset($this->SignShop->temp[$player->getName()]);
                    return;
                }
                
                if($get["type"] == "sell"){
                    if($player->getGamemode() == 1){
                        $message->send($player, "You_can_not_buy_or_sell_in_creative");
                        $signManager->spawnSign($block, $get);
                        return;
                    }
                    
                    if(strtolower($event->getPlayer()->getName()) == strtolower($get["maker"])){
                        $message->send($player, "You_can_not_sell_your_items_to_your_Sign,_if_you_want_you_can_empty_it_executing_the_command_/sign_empty");
                        return;
                    }
                    
                    if($get["need"] != -1 && $get["need"] < $get["amount"] + $get["available"]){
                        $message->send($player, "The_Sign_is_full");
                    }else{
                        if(!$this->SignShop->getMoneyManager()->isExists($get["maker"])){
                            $message->send($player, "The_player_@@_is_not_exists", $get["maker"]);
                            $signManager->spawnSign($block, $get);
                            return;
                        }
                        $item = new Item($get["id"], $get["damage"], $get["amount"]);
                        if($this->hasItemPlayer($player, $item)){
                            $this->removeItemPlayer($player, $item);
                            $message->send($player, "You_have_successfully_sold_the_items");
                            
                            if($this->SignShop->getProvider()->getPlayer($get["maker"])["authorized"] != "root"){
                                if($this->SignShop->getMoneyManager()->getMoney($get["maker"]) - $get["cost"] >= 0){
                                   $this->SignShop->getMoneyManager()->addMoney($get["maker"], -$get["cost"]); 
                                }else{
                                    $message->send($player, "The_maker_of_Sign_does_not_have_enough_money_to_give_you_the_money");
                                    $signManager->spawnSign($block, $get);
                                    return;
                                }
                            }
                            
                            $this->SignShop->getMoneyManager()->addMoney($player->getName(), $get["cost"]);
                            $get["available"] += $get["amount"];
                        }else
                            $message->send($player, "The_item_was_not_found_or_does_not_have_enough_items");
                        $signManager->setSign($block, $get);       
                        return;
                    } 
                }else{ //SignBuy
                    if(strtolower($event->getPlayer()->getName()) == strtolower($get["maker"])){
                        $signManager->spawnSign($block, $get);
                        $message->send($player, "You_can_not_buy_from_your_Sign");
                        return;
                    }
                    if($player->getGamemode() == 1){
                        $message->send($player, "You_can_not_buy_or_sell_in_creative");
                        $signManager->spawnSign($block, $get);
                        return;
                    }

                    if($this->SignShop->getMoneyManager()->getMoney($player->getName()) < $get["cost"])
                        $message->send($player, "You_do_not_have_enough_money");   
                    elseif($get["available"] != "unlimited" && $get["available"] - $get["amount"] < 0)
                        $message->send($player, "The_content_of_the_Sign_is_sold_out");
                    else{
                        $item = Item::get($get["id"], $get["damage"], $get["amount"]);             
                        if($player->getInventory()->canAddItem($item)){
                            $this->SignShop->getMoneyManager()->addMoney($get["maker"], $get["cost"]);   
                            $this->SignShop->getMoneyManager()->addMoney($player->getName(), -($get["cost"]));

                            $player->getInventory()->addItem($item);

                            if($get["available"] != "unlimited") 
                                $get["available"] -= $get["amount"];
                            $get["sold"] += $get["amount"];
                            $get["earned"] += $get["cost"];

                            $signManager->setSign($block, $get); 

                            $message->send($player, "You_bought_the_contents_of_the_Sign");

                            $maker = $this->getPlayer($get["maker"]);
                            if($maker instanceof Player && $this->SignShop->getProvider()->getPlayer($get["maker"])["echo"] != false && $get["cost"] != 0)
                                $message->send($maker, "+".$get["cost"].$this->SignShop->getMoneyManager()->getValue()." ".str_replace ("@@", $player->getName(),$message->getMessage("payment_from_@@")));
                            return;                            
                        }else
                            $message->send($player, "You_do_not_have_the_space_to_buy_the_contents_of_this_Sign");
                    } 
                }
                $signManager->spawnSign($block, $get);
                return;
            }
            if(isset($this->SignShop->temp[$player->getName()])){  
                $message->send($player, "This_sign_is_not_registered");
                unset($this->SignShop->temp[$player->getName()]);
            }            
        }   
    }       
    
    private function removeItemPlayer(Player $player, Item $item){
        if($this->SignShop->getProvider()->getPlayer(strtolower($player->getName()))["authorized"] == "root") 
            return true;
        $ris = $item->getCount();
        
        if($player->getGamemode() != 1){
            for($i = 0; $i <= $player->getInventory()->getSize(); $i = $i + 1){
                $inv = $player->getInventory()->getItem($i);
                if($inv->getID() == $item->getID() && $inv->getDamage() == $item->getDamage()){
                    $ris = $inv->getCount() - $ris;
                    if($ris <= 0){
                        $player->getInventory()->clear($i);
                        $ris = -($ris);
                    }else{
                        $player->getInventory()->setItem($i, Item::get($item->getID(), $item->getDamage(), $ris));
                        return true;
                    }
                }
            }
        }
        return true;
    }

    private function hasItemPlayer(Player $player, Item $item){
        if($this->SignShop->getProvider()->getPlayer(strtolower($player->getName()))["authorized"] == "root") return true;
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
        return Server::getInstance()->getPlayer($player);
    }   
}