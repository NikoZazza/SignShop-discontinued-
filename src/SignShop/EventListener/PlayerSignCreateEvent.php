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
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\event\Event;
use pocketmine\event\Listener;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\utils\TextFormat;

class PlayerSignCreateEvent implements Listener{
    private $SignShop;

    public function __construct(SignShop $SignShop){
        $this->SignShop = $SignShop;
    }
    
    public function signChangeEvent(SignChangeEvent $event){
        $line = str_replace(["[", "]", "/"], "", strtolower(trim($event->getLine(0))));
        if($line == "signsell")
            $this->signSell($event);
        if($line == "signbuy" || $line == "sign" || $line == "signshop")
            $this->signBuy($event);
        return;
    }

    private function signSell(Event $event){
        if(!$event instanceof SignChangeEvent)
            return;
        $line1 = trim($event->getLine(1));
        $line2 = trim($event->getLine(2));
        $line3 = trim($event->getLine(3));
        
        $player = $event->getPlayer();
        $error = "";
        if($player->getGamemode() == 1){
            if($this->SignShop->getProvider()->getPlayer($player->getName())["authorized"] != "root"){
                $this->SignShop->messageManager()->send($player, "You_can_not_create_the_Signs_from_the_creative_mode");
                return;
            }
        }
        if($this->SignShop->getProvider()->getPlayer($player->getName())["authorized"] != "denied"){      
            if(is_numeric($line1) && $line1 > 0){
                $id = $line1;
                $damage = 0;                    
            }elseif(count(explode(":", $line1)) == 2){                    
                $line1 = explode(":", $line1);
                $id = $line1[0];
                $damage = $line1[1];
                    
                if(!is_numeric($id) || !is_numeric($damage)) 
                    $error = "Item Not_Numeric";
            }else{
                $item = Item::fromString($line1); 
                $id = $item->getID();
                $damage = $item->getDamage();
                if($id == 0) 
                    $error = "Item Invalid";
            } 
            $need = 0;
            if(is_numeric($line2)){
                if($line2 > 0)
                    $need = $line2;                
                else    
                    $error = "Nedded_Items Invalid";           
            }else{
                if($line2 == "unlimited"){
                    if($this->SignShop->getProvider()->getPlayer($player->getName())["authorized"] == "root")
                        $need = -1; 
                    else
                        $error = "Player Not_Authorized";
                }else
                    $error = "Needed_Items Not_Numeric";               
            }
                
            $amount = 0;
            if(count(explode("x", $line3)) == 2 || count(explode(" ", $line3)) == 2){
                if(count(explode("x", $line3)) == 2)
                    $line3 = explode("x", $line3);
                else
                    $line3 = explode(" ", $line3);

                if(is_numeric($line3[0]) && is_numeric($line3[1])){
                    $amount = $line3[1];
                    $cost = $line3[0];
                    if($cost < 0 || $amount < 0 || $amount > 45 * 64)
                        $error = "Invalid Amount_Or_Cost";
                }else 
                    $error = "Amount_Or_Cost NotNumeric";
            }else 
                $error = "Invalid Amount_Or_Cost";
            if($amount > Item::get($id, $damage)->getMaxStackSize())
                $error = "Amount_Must_Be Less_Than_".Item::get($id)->getMaxStackSize();
            /*if($need > 0 && $amount > 0){
                if($this->SignShop->getMoneyManager()->getMoney($player->getName()) < $cost * ($need/$amount) && $this->SignShop->getProvider()->getPlayer($player->getName())["authorized"] != "root"){
                    $error = "Not_enough money";
                }
            }*/
            if($error == ""){
                $this->SignShop->getSignManager()->setSign($event->getBlock(), [
                        "id" => $id, 
                        "damage" => $damage, 
                        "amount" => $amount, 
                        "available" => 0, 
                        "cost" => $cost, 
                        "need" => $need,
                        "maker" => $player->getName(), 
                        "time" => time(), 
                        "sold" => 0, 
                        "earned" => 0, 
                        "type" => "sell",
                        "direction" => $event->getBlock()->getDamage()]);
                    $this->SignShop->getMoneyManager()->addMoney($player->getName(), -($cost * ($need/$amount)));
                    $this->SignShop->messageManager()->send($player, "Sign_successfully_created");
                    
                    if($need == -1)
                        $need = "∞";
                    $event->setLine(0, TextFormat::GOLD."[SignSell]");
                    $event->setLine(1, TextFormat::ITALIC.str_replace(" ", "", Item::get($id, $damage)->getName()));
                    $event->setLine(2, "0/".$need);
                    $event->setLine(3, $cost.$this->SignShop->getMoneyManager()->getValue()." for ".$amount);
            }
            if($error != ""){
                $error = explode(" ", $error);

                $event->setLine(0, TextFormat::GOLD."[SignShop]");
                $event->setLine(1, TextFormat::DARK_RED.":ERROR:");
                $event->setLine(2, $error[0]);
                $event->setLine(3, $error[1]);

                $this->SignShop->messageManager()->send($player, "There_is_a_problem_with_the_creation_of_the_Sign");
            }
        }else{
            $this->SignShop->messageManager()->send($player, "You_are_not_authorized_to_run_this_command");
            $event->setLine(0, TextFormat::GOLD."[SignShop]");
            $event->setLine(1, TextFormat::DARK_RED."Error:");
            $event->setLine(2, "PlayerNot");
            $event->setLine(3, "Authorized");                
        }       
    }
    
    private function signBuy(Event $event){
        if(!$event instanceof SignChangeEvent)
            return;
        
        $line1 = trim($event->getLine(1));
        $line2 = trim($event->getLine(2));
        $line3 = trim($event->getLine(3));
        
        $player = $event->getPlayer();
        $error = "";
        if($player->getGamemode() == 1){
            if($this->SignShop->getProvider()->getPlayer($player->getName())["authorized"] != "root"){
                $this->SignShop->messageManager()->send($player, "You_can_not_create_the_Signs_from_the_creative_mode");
                return;
            }
        }
            
        if($this->SignShop->getProvider()->getPlayer($player->getName())["authorized"] != "denied"){      
            if(is_numeric($line1) && $line1 > 0){
                $id = $line1;
                $damage = 0;                    
            }elseif(count(explode(":", $line1)) == 2){                    
                $line1 = explode(":", $line1);
                $id = $line1[0];
                $damage = $line1[1];
                    
                if(!is_numeric($id) || !is_numeric($damage)) 
                    $error = "Item Not_Numeric";
            }else{
                $item = Item::fromString($line1); 
                $id = $item->getID();
                $damage = $item->getDamage();
                if($id == 0) 
                    $error = "Item Invalid";
            }
                              
            $amount = 0;
            if(count(explode("x", $line2)) == 2 || count(explode(" ", $line2)) == 2){
                if(count(explode("x", $line2)) == 2)
                    $line2 = explode("x", $line2);
                else
                    $line2 = explode(" ", $line2);

                if(is_numeric($line2[0]) && is_numeric($line2[1])){
                    $amount = $line2[0];
                    $cost = $line2[1];
                    if($cost < 0 || $amount < 0 || $amount > 45 * 64)
                        $error = "Invalid Amount_Or_Cost";
                }else 
                    $error = "Amount_Or_Cost NotNumeric";
            }else 
                $error = "Invalid Amount_Or_Cost";
            
            if($amount > Item::get($id, $damage)->getMaxStackSize())
                $error = "Amount_Must_Be Less_Than_".Item::get($id)->getMaxStackSize();
            
            $count = $line3;
            if(is_numeric($line3)){
                if($line3 < $amount) 
                    $error = "Invalid Available";                    
            }else{
                if($line3 == "unlimited"){
                    if($this->SignShop->getProvider()->getPlayer($player->getName())["authorized"] == "root")
                        $count = 0;    
                    else
                        $error = "Player Not_Authorized";
                }else
                    $error = "Available Not_Numeric";
            } 

            if($error == ""){
                $item = Item::get($id, $damage, $count);
                if($player->getGamemode() == 1 || ($this->hasItemPlayer($player, $item) && $this->removeItemPlayer($player, $item))){

                    $this->SignShop->getSignManager()->setSign($event->getBlock(), [
                        "id" => $id, 
                        "damage" => $damage, 
                        "amount" => $amount, 
                        "available" => $line3, 
                        "cost" => $cost, 
                        "need" => 0,
                        "maker" => $player->getName(), 
                        "time" => time(), 
                        "sold" => 0, 
                        "earned" => 0, 
                        "type" => "buy",
                        "direction" => $event->getBlock()->getDamage()]);

                    $this->SignShop->messageManager()->send($player, "Sign_successfully_created");

                    $event->setLine(0, TextFormat::GOLD."[SignBuy]");
                    $event->setLine(1, TextFormat::ITALIC.str_replace(" ", "", Item::get($id, $damage)->getName()));
                    $event->setLine(2, "Amount: x".$amount);
                    $event->setLine(3, "Price: ".$cost.$this->SignShop->getMoneyManager()->getValue());
                    }else{
                    $this->SignShop->messageManager()->send($player, "The_item_was_not_found_or_does_not_have_enough_items"); 
                    $error = "Player Error";                        
                }
            }
            if($error != ""){
                $error = explode(" ", $error);

                $event->setLine(0, TextFormat::GOLD."[SignShop]");
                $event->setLine(1, TextFormat::DARK_RED.":ERROR:");
                $event->setLine(2, $error[0]);
                $event->setLine(3, $error[1]);

                $this->SignShop->messageManager()->send($player, "There_is_a_problem_with_the_creation_of_the_Sign");
            }
        }else{
            $this->SignShop->messageManager()->send($player, "You_are_not_authorized_to_run_this_command");
            $event->setLine(0, TextFormat::GOLD."[SignShop]");
            $event->setLine(1, TextFormat::DARK_RED."Error:");
            $event->setLine(2, "PlayerNot");
            $event->setLine(3, "Authorized");                
        }       
    }

    /**
     * @param Player $player
     * @param Item $item
     * @return boolean
     */
    private function removeItemPlayer(Player $player, $item){
        if($this->SignShop->getProvider()->getPlayer($player->getName())["authorized"] == "root") return true;

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

    /**
     * @param Player $player
     * @param Item $item
     * @return boolean
     */
    private function hasItemPlayer(Player $player, Item $item){
        if ($this->SignShop->getProvider()->getPlayer(strtolower($player->getName()))["authorized"] == "root") return true;
        $ris = 0;
        if ($player->getGamemode() != 1) {
            for ($i = 0; $i <= $player->getInventory()->getSize(); ++$i) {
                $inv = $player->getInventory()->getItem($i);
                if ($inv->getID() == $item->getID() && $inv->getDamage() == $item->getDamage())
                    $ris = $ris + $inv->getCount();
            }
        }
        if ($ris >= $item->getCount())
            return true;
        return false;
    }
}