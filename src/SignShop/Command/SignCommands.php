<?php
/* @author xionbig
 * @link http://xionbig.altervista.org/SignShop 
 * @link http://forums.pocketmine.net/plugins/signshop.668/
 * @version 0.9.0 */

namespace SignShop\Command;

use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use pocketmine\Player;

class SignCommands{
    protected $SignMain;

    public function __construct($SignShop, CommandSender $sender, array $args){
        $this->SignMain = $SignShop;
        $this->onCommand($sender, $args);
    }
    
    public function onCommand(CommandSender $sender, array $args){
        if(!isset($args) || !isset($args[0])) $args[0] = "help";
        
        $sender->sendMessage(TextFormat::AQUA."[SignShop] Usage: /sign <". $args[0].">");
            
        if($sender instanceof Player) 
            return $this->onCommandUser($sender, $args);
        
        switch(strtolower($args[0])){  
            case "r":
            case "reload":
                $sender->sendMessage(TextFormat::AQUA."[SignShop] ".$this->SignMain->respawnAllSign());
                return;
                    
            case "h":
            case "help":                
                if(isset($args[1])){
                    switch(strtolower($args[1])){
                        case "reload": $sender->sendMessage(TextFormat::AQUA."[SignShop] Use it when there are Signs that have not loaded.");
                            return;
                        case "setup": $sender->sendMessage(TextFormat::AQUA."[SignShop] Sets who can use the command /sign");
                            return;
                        case "player": $sender->sendMessage(TextFormat::AQUA."[SignShop] Allow or Disallows the player to use the command /sign");
                            return;
                    }
                    $sender->sendMessage(TextFormat::RED."[SignShop] ".str_replace("@@", $args[1], "The command <@@> was not found! Use /sign help"));
                }else{
                    $messages = ["player <view-auth-unauth-super> <player>", "reload", "setup <admin-list-all>"];                 
                    foreach($messages as $var)
                        $sender->sendMessage(TextFormat::AQUA."[SignShop] /sign ".$var);
                    }
                return;
                        
            case "p":
            case "plr":
            case "player":
                if(count($args) == 3){
                    switch(strtolower($args[1])){
                        case "auth":
                            if($this->SignMain->getProvider()->existsPlayer($args[2])){
                                $get = $this->SignMain->getProvider()->getPlayer($args[2]);
                                    
                                $get["authorized"] = "auth";
                                $get["changed"] = time();
                                    
                                $this->SignMain->getProvider()->setPlayer($args[2], $get);
                                    
                                $sender->sendMessage(TextFormat::GREEN."[SignShop] ".str_replace("@@", $args[2], "You have authorized @@ to use the command /sign"));
                            }else
                                $sender->sendMessage(TextFormat::YELLOW."[SignShop] ".str_replace("@@", $args[2], "The player @@ is not exists."));
                            return;
                                
                        case "unauth":
                            if($this->SignMain->getProvider()->existsPlayer($args[2])){
                                $get = $this->SignMain->getProvider()->getPlayer($args[2]);
                                    
                                $get["authorized"] = "unauth";
                                $get["changed"] = time();
                                    
                                $this->SignMain->getProvider()->setPlayer($args[2], $get);         
                                   
                                $sender->sendMessage(TextFormat::GREEN."[SignShop] ".str_replace("@@", $args[2], "You have unauthorized @@ to use the command /sign"));
                            }else
                                $sender->sendMessage(TextFormat::YELLOW."[SignShop] ".str_replace("@@", $args[2], "The player @@ is not exists."));
                            return;
                                
                        case "super":
                            if($this->SignMain->getProvider()->existsPlayer($args[2])){
                                $get = $this->SignMain->getProvider()->getPlayer($args[2]);
                                    
                                $get["authorized"] = "super";
                                $get["changed"] = time();
                                    
                                $this->SignMain->getProvider()->setPlayer($args[2], $get);         
                                    
                                $sender->sendMessage(TextFormat::GREEN."[SignShop] ".str_replace("@@", $args[2], "You have authorized @@ to create the Signs without the blocks in the inventory"));
                            }else
                                $sender->sendMessage(TextFormat::YELLOW."[SignShop] ".str_replace("@@", $args[2], "The player @@ is not exists."));
                            return;
                                
                        case "view":
                            if($this->SignMain->getProvider()->existsPlayer($args[2])){
                                if($this->SignMain->getProvider()->getPlayer($args[2])["authorized"] != "unauth")
                                    $sender->sendMessage(TextFormat::AQUA."[SignShop] ".str_replace("@@", $args[2], "The player @@ is authorized to run the command /sign"));
                                else
                                    $sender->sendMessage(TextFormat::AQUA."[SignShop] ".str_replace("@@", $args[2], "The player @@ is not authorized to run the command /sign"));
                                
                                $sender->sendMessage(TextFormat::AQUA."[SignShop] ".str_replace("@@", $args[2], "The player @@ has earned with the Signs: ".$this->SignMain->getProvider()->getPlayer($args[2])["totEarned"]));
                                $sender->sendMessage(TextFormat::AQUA."[SignShop] ".str_replace("@@", $args[2], "The player @@ has spent with the Signs: ".$this->SignMain->getProvider()->getPlayer($args[2])["totSpend"]));
                            }else
                                $sender->sendMessage(TextFormat::YELLOW."[SignShop] ".str_replace("@@", $args[2], "The player @@ is not exists."));
                            return;       
                        }
                        $sender->sendMessage(TextFormat::RED."[SignShop] Invalid arguments!");
                }else
                    $sender->sendMessage(TextFormat::RED."[SignShop] Invalid arguments!");
                return;
                
            case "s":
            case "setup":
                if(!isset($args[1])) $args[1] = " ";
            
                $args[1] = strtolower($args[1]);
                    
                if($args[1] == "admin" || $args[1] == "list" || $args[1] == "all"){                                 
                    $this->SignMain->getSetup()->set("signCreated", $args[1]);
                    $this->SignMain->getSetup()->set("lastChange", time());
                    $this->SignMain->getSetup()->save();
                
                    $sender->sendMessage(TextFormat::AQUA."[SignShop] ".str_replace("@@", $args[1], "Now @@ can use the command /sign"));
                                    
                    foreach($this->SignMain->getProvider()->getAllPlayers() as $var => $c){
                        $auth = "unauth";
                        if($args[1] == "all") $auth = "auth";
                                        
                        if($args[1] == "admin" && $this->SignMain->isOnlinePlayer($var)){
                            if($this->SignMain->getPlayer($var)->isOp()) $auth = "auth";                                                     
                        }
                        $get = $this->SignMain->getProvider()->getPlayer($var);
                        $get["authorized"] = $auth;
                        $get["changed"] = time();
                        $this->SignMain->getProvider()->setPlayer($var, $get);
                    }                                   
                }else
                    $sender->sendMessage(TextFormat::RED."[SignShop] Invalid arguments!");
                return;  
        }   
        $sender->sendMessage(TextFormat::RED."[SignShop] ".str_replace("@@", $args[0], "The command <@@> was not found! Use /sign help"));
    }

    public function onCommandUser(CommandSender $sender, array $args){
        if($this->SignMain->getProvider()->getPlayer($sender->getName())["authorized"] != "unauth"){       
            switch(strtolower($args[0])){
                case "h":
                case "help":
                    $message = [
                            "earned <none|format>",
                            "echo <on|off>",
                            "refill <".$this->SignMain->getMessages()["amount"].">",
                            "view <none>",
                            "set <amount|cost|maker|unlimited> <value>"
                        ]; 
                    if($sender->isOp())
                        $message[count($message)+1] = "reload <none>"; 

                    foreach($message as $var)
                        $sender->sendMessage("[SignShop] /sign ".$var);
                    return;
                    
                case "e":
                case "earned":
                    if(isset($args[1]) && strtolower($args[1]) == "format"){
                        $get = $this->SignMain->getProvider()->getPlayer($sender->getName());
                    
                        $get["totEarned"] = 0;
                        $get["totSpent"] = 0;
                        $get["earned"] = 0;
                    
                        $this->SignMain->getProvider()->setPlayer($sender->getName(), $get);       
                        $sender->sendMessage("[SignShop] ". $this->SignMain->getMessages()["The_formatting_is_finished_successfully"]);
                        return;
                    }
                    $value = $this->SignMain->getMoneyManager()->getValue();
                    $getPlayer = $this->SignMain->getProvider()->getPlayer($sender->getName());
                    $message = [ 
                        str_replace("@@", $getPlayer["totEarned"].$value, $this->SignMain->getMessages()["In_total_you_have_earned_@@_with_Signs"]),
                        str_replace("@@", $getPlayer["totEarned"].$value, $this->SignMain->getMessages()["In_total_you_have_spent_@@_with_Signs"]),
                        $this->SignMain->getMessages()["To_format_this_information,_use_/sign_earned_format"]];
                    foreach($message as $var)
                        $sender->sendMessage("[SignShop] ".$var);
                    return;
                
                case "echo":
                    $get = $this->SignMain->getProvider()->getPlayer($sender->getName());
                    switch(strtolower(trim($args[1]))){
                        case "on":
                        case "true":
                            $get["echo"] = true; 
                            $this->SignMain->getProvider()->setPlayer($sender->getName(), $get);                                      
                            $sender->sendMessage("[SignShop] ". $this->SignMain->getMessages()["The_action_has_been_executed_successfully"]);
                            return;
                        
                        case "off":
                        case "false":
                            $get["echo"] = false; 
                            $this->SignMain->getProvider()->setPlayer($sender->getName(), $get);        
                            $sender->sendMessage("[SignShop] ". $this->SignMain->getMessages()["The_action_has_been_executed_successfully"]);
                            return;  
                    }
                    $sender->sendMessage("[SignShop] ".$this->SignMain->getMessages()["Invalid_arguments"]);
                    return;
                   
                case "r":
                case "refill":
                    if(count($args) != 2){
                        $sender->sendMessage("[SignShop] ". $this->SignMain->getMessages()["Invalid_arguments"]);
                        return;                                    
                    }
                    if(!is_numeric($args[1]) || $args[1] < 0 || $args[1] > (64 * 45)){
                        $sender->sendMessage("[SignShop] ". str_replace("@@", $this->SignMain->getMessages()["amount"], $this->SignMain->getMessages()["Invalid_value_of_@@"]));
                        return;
                    }
                    $this->SignMain->temp[$sender->getName()] = ["action" => "refill", "amount" => $args[1]];
                    $sender->sendMessage("[SignShop] ". $this->SignMain->getMessages()["Now_touch_on_the_Sign_that_you_want_to_do_this_action"]);
                    return;
                 
                case "v":
                case "view":
                    $this->SignMain->temp[$sender->getName()] = ["action" => "view"];
                    $sender->sendMessage("[SignShop] ". $this->SignMain->getMessages()["Touch_on_the_Sign_that_you_want_to_know_the_information"]);
                    return;
                    
                case "s":
                case "set":
                    if(count($args) == 3){
                        switch(strtolower($args[1])){
                            case "amount":
                                if(is_numeric($args[2]) && $args[2] > 0 && $args[2] < (64 * 45)){                                        
                                    $this->SignMain->temp[$sender->getName()] = ["action" => "set", "arg" => "amount", "value" => $args[2]];
                                    $sender->sendMessage("[SignShop] ". $this->SignMain->getMessages()["Now_touch_on_the_Sign_that_you_want_to_do_this_action"]);
                                }else
                                    $sender->sendMessage("[SignShop] ". str_replace("@@", $this->SignMain->getMessages()["amount"], $this->SignMain->getMessages()["Invalid_value_of_@@"]));
                                return;
                                
                            case "cost":
                                if(is_numeric($args[2]) && $args[2] > 0){                                        
                                    $this->SignMain->temp[$sender->getName()] = ["action" => "set", "arg" => "cost", "value" => $args[2]];
                                    $sender->sendMessage("[SignShop] ". $this->SignMain->getMessages()["Now_touch_on_the_Sign_that_you_want_to_do_this_action"]);
                                }else
                                    $sender->sendMessage("[SignShop] ". str_replace("@@", $this->SignMain->getMessages()["cost"], $this->SignMain->getMessages()["Invalid_value_of_@@"]));
                                return;
                                
                            case "maker": 
                                if($args[2] != " "){
                                    $this->SignMain->temp[$sender->getName()] = ["action" => "set", "arg" => "maker", "name" => $args[2]];
                                    $sender->sendMessage("[SignShop] ". $this->SignMain->getMessages()["Now_touch_on_the_Sign_that_you_want_to_do_this_action"]);
                                }else
                                    $sender->sendMessage("[SignShop] ". str_replace("@@", $this->SignMain->getMessages()["cost"], $this->SignMain->getMessages()["Invalid_value_of_@@"]));
                                return;
                                
                            case "unlimited": 
                                if($this->SignMain->getProvider()->getPlayer($sender->getName())["authorized"] == "super"){
                                    $this->SignMain->temp[$sender->getName()] = ["action" => "set", "arg" => "unlimited"];
                                    $sender->sendMessage("[SignShop] ".$this->SignMain->getMessages()["Now_touch_on_the_Sign_that_you_want_to_do_this_action"]);  
                                }else
                                    $sender->sendMessage("[SignShop] ". $this->SignMain->getMessages()["You_are_not_authorized_to_run_this_command"]);
                                return;
                        }                            
                    }
                    $sender->sendMessage("[SignShop] ".$this->SignMain->getMessages()["Invalid_arguments"]);
                    return;
                
                case "reload":
                    if($sender->isOp()) 
                        $sender->sendMessage("[SignShop] ". $this->SignMain->respawnAllSign());
                    else 
                        $sender->sendMessage("[SignShop] ". $this->SignMain->getMessages()["You_are_not_authorized_to_run_this_command"]);
                    return;
            }
            $sender->sendMessage(TextFormat::RED."[SignShop] ".str_replace("@@", $args[0], $this->SignMain->getMessages()["The_command_<@@>_was_not_found!_Use_/sign_help"]));
        }else
            $sender->sendMessage("[SignShop] ". $this->SignMain->getMessages()["You_are_not_authorized_to_run_this_command"]);
    }
    
}