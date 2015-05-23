<?php
/**
 * @author xionbig
 * @link http://xionbig.altervista.org/SignShop 
 * @link http://forums.pocketmine.net/plugins/signshop.668/
 * @version 1.0.0 
 */
namespace SignShop\EventListener;

use pocketmine\event\Listener;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\Player;
use pocketmine\item\Item;

class PlayerSignCreateEvent implements Listener{
    private $SignShop;

    public function __construct($SignShop){
        $this->SignShop = $SignShop;
    }
    
    public function signChangeEvent(SignChangeEvent $event){
        $line0 = strtolower(trim($event->getLine(0)));
        $line1 = trim($event->getLine(1));
        $line2 = trim($event->getLine(2));
        $line3 = trim($event->getLine(3));
        
        if($line0 == "[signshop]" || $line0 == "/signshop" || $line0 == "\sign" || $line0 == "/sign"){
            $player = $event->getPlayer();
            $error = "";
            if($player->getGamemode() == 1){
                if($this->SignShop->getProvider()->getPlayer($player->getDisplayName())["authorized"] != "root"){
                    $this->SignShop->messageManager()->send($player, "You_can_not_create_the_Signs_from_the_creative_mode");
                    return;
                }
            }
            
            if($this->SignShop->getProvider()->getPlayer($player->getDisplayName())["authorized"] != "denied"){      
                if(is_numeric($line1) && $line1 > 0){
                    $id = $line1;
                    $damage = 0;                    
                }elseif(count(explode(":", $line1)) == 2){                    
                    $line1 = explode(":", $line1);
                    $id = $line1[0];
                    $damage = $line1[1];
                    
                    if(!is_numeric($id) || !is_numeric($damage)) $error = "Item Not_Numeric";
                }else{
                    $item = $this->SignShop->getItems()->getBlock($line1); 
                    $id = $item->getID();
                    $damage = $item->getDamage();
                    if($id == 0) $error = "Item Invalid";
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
                        if($cost < 0 || $amount < 0 || $amount > 45 * 64) $error = "Invalid Amount_Or_Cost";
                    }else $error = "Amount_Or_Cost NotNumeric";
                }else $error = "Invalid Amount_Or_Cost";
                              
                $count = $line3;
                if(is_numeric($line3)){
                    if($line3 < $amount) $error = "Invalid Available";                    
                }else{
                    if($line3 == "unlimited"){
                        if($this->SignShop->getProvider()->getPlayer($player->getDisplayName())["authorized"] == "root")
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
                            "maker" => $player->getDisplayName(), 
                            "time" => time(), 
                            "sold" => 0, 
                            "earned" => 0, 
                            "direction" => $event->getBlock()->getDamage()]);
                           
                        $this->SignShop->messageManager()->send($player, "Sign_successfully_created");
                           
                        $event->setLine(0, "[SignShop]");
                        $event->setLine(1, $this->SignShop->getItems()->getName($id, $damage));
                        $event->setLine(2, "Amount: x".$amount);
                        $event->setLine(3, "Price: ".$cost.$this->SignShop->getMoneyManager()->getValue());
                    }else{
                        $this->SignShop->messageManager()->send($player, "The_item_was_not_found_or_does_not_have_enough_items"); 
                        $error = "Player Error";                        
                    }
                }
                if($error != ""){
                    $error = explode(" ", $error);
                    
                    $event->setLine(0, "[SignShop]");
                    $event->setLine(1, ":ERROR:");
                    $event->setLine(2, $error[0]);
                    $event->setLine(3, $error[1]);
                    
                    $this->SignShop->messageManager()->send($player, "There_is_a_problem_with_the_creation_of_the_Sign");
                }
            }else{
                $this->SignShop->messageManager()->send($player, "You_are_not_authorized_to_run_this_command");
                $event->setLine(0, "[SignShop]");
                $event->setLine(1, "Error:");
                $event->setLine(2, "PlayerNot");
                $event->setLine(3, "Authorized");                
            }
        }
    }
    
    private function removeItemPlayer(Player $player, $item){
        if($this->SignShop->getProvider()->getPlayer($player->getDisplayName())["authorized"] == "root") return true; 
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

    private function hasItemPlayer(Player $player, $item){
        if($this->SignShop->getProvider()->getPlayer($player->getDisplayName())["authorized"] == "root") return true; 
        
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
}