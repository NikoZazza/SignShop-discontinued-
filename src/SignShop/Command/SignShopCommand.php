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
namespace SignShop\Command;

use SignShop\SignShop;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginIdentifiableCommand;

class SignShopCommand extends Command implements PluginIdentifiableCommand{
    private $SignShop;
    private $MessageManager;
    
    public function __construct(SignShop $SignShop){
        parent::__construct("sign", "Buy and Sell the items using Signs with virtual-money.", "/sign <command> <value>");
        $this->SignShop = $SignShop;
        $this->MessageManager = $SignShop->messageManager();
    }
    
    public function execute(CommandSender $sender, $label, array $args){       
        $MessageManager = $this->MessageManager;
        $cmds = "";
        
        if(!isset($args) || !isset($args[0])) 
            $args[0] = "help";
        
        foreach($args as $var){
            $cmds = $cmds. " <".$var.">";
        }
        $MessageManager->send($sender, TextFormat::DARK_AQUA."Usage: /sign".$cmds);
           
        if($sender instanceof Player){
            if(!$this->onCommandUser($sender, $args))
                return;
            else{
                if($this->SignShop->getProvider()->getPlayer($sender->getName())["authorized"] != "root" && !$sender->isOp()){
                    $MessageManager->send($sender, "The_command_<@@>_was_not_found!_Use_/sign_help" , $args[0]);
                    return; 
                }
            }
        }
        
        $args[0] = strtolower(trim($args[0]));
        switch($args[0]){  
            case "r":
            case "reload":
                if($this->SignShop->getSignManager()->reload())
                    $MessageManager->send($sender, "All_the_Signs_have_been_put_back");
                else
                    $MessageManager->send($sender, "There_are_no_Signs_spammed");
                return;
                    
            case "?":
            case "h":
            case "help":                
                if(isset($args[1]))
                    $sender->sendMessage($MessageManager->getTag().$this->showHelp($args[1]));
                else
                    $sender->sendMessage($this->showHelp(false, true));
                return;
                 
            case "a":
            case "auth":
            case "allow";
                if(isset($args[1])){
                    if($this->SignShop->getProvider()->existsPlayer($args[1])){
                        $get = $this->SignShop->getProvider()->getPlayer($args[1]);
                                    
                        $get["authorized"] = "allowed";
                        $get["changed"] = time();
                                    
                        $this->SignShop->getProvider()->setPlayer($args[1], $get);
                                    
                        $MessageManager->send($sender, "You_have_authorized_@@_to_use_the_command_/sign", $args[1]);
                    }else
                        $MessageManager->send($sender, "The_player_@@_is_not_exists", $args[1]);
                }else
                    $MessageManager->send($sender, "Invalid_arguments");
                return;
                
            case "d":
            case "unauth":
            case "deny":
                if(isset($args[1])){
                    if($this->SignShop->getProvider()->existsPlayer($args[1])){
                        $get = $this->SignShop->getProvider()->getPlayer($args[1]);

                        $get["authorized"] = "denied";
                        $get["changed"] = time();

                        $this->SignShop->getProvider()->setPlayer($args[1], $get);         

                        $MessageManager->send($sender, "You_have_unauthorized_@@_to_use_the_command_/sign", $args[1]);
                    }else
                        $MessageManager->send($sender, "The_player_@@_is_not_exists", $args[1]);
                }else
                    $MessageManager->send($sender, "Invalid_arguments");
                return;
                
            case "super":
            case "root":
                if(!($sender instanceof ConsoleCommandSender)){
                    $MessageManager->send($sender, "This_command_must_be_run_from_the_console");
                    return;
                }
                
                if(isset($args[1])){
                    if($this->SignShop->getProvider()->existsPlayer($args[1])){
                        $get = $this->SignShop->getProvider()->getPlayer($args[1]);
                                    
                        $get["authorized"] = "root";
                        $get["changed"] = time();
                                    
                        $this->SignShop->getProvider()->setPlayer($args[1], $get);         
                        $MessageManager->send($sender, "You_have_authorized_@@_to_create_the_Signs_without_the_blocks_in_the_inventory", $args[1]);           
                    }else
                        $MessageManager->send($sender, "The_player_@@_is_not_exists", $args[1]);
                }else
                    $MessageManager->send($sender, "Invalid_arguments");
                return;
                
            case "s":
            case "show":
                if(isset($args[1])){
                    if($this->SignShop->getProvider()->existsPlayer($args[1])){
                        if($this->SignShop->getProvider()->getPlayer($args[1])["authorized"] != "denied")
                            $MessageManager->send($sender, "The_player_@@_is_authorized_to_run_the_command_/sign", $args[1]);
                        else
                            $MessageManager->send($sender, "The_player_@@_is_not_authorized_to_run_the_command_/sign", $args[1]);
                            
                    }else
                        $MessageManager->send($sender, "The_player_@@_is_not_exists");
                }else
                    $MessageManager->send($sender, "Invalid_arguments");
                return;

            case "lang":
                if(!($sender instanceof ConsoleCommandSender)){
                    $MessageManager->send($sender, "This_command_must_be_run_from_the_console");
                    return;
                }
                if(!isset($args[1]))
                    $MessageManager->send($sender, "Invalid_arguments");
                else{
                    if(!isset($args[2]))
                        $args[2] = false;
                    $MessageManager->downloadLang($sender, strtolower(trim($args[1])), $args[2]);
                }
                return;
            
            case "setup":
                if(!($sender instanceof ConsoleCommandSender)){
                    $MessageManager->send($sender, "This_command_must_be_run_from_the_console");
                    return;
                }
                if(!isset($args[1])) $args[1] = " ";            
                $args[1] = strtolower($args[1]);
                    
                if($args[1] == "admin" || $args[1] == "all"){                                 
                    $this->SignShop->getSetup()->set("signCreated", $args[1]);
                    $this->SignShop->getSetup()->set("lastChange", time());
                    $this->SignShop->getSetup()->save();
                    
                    foreach($this->SignShop->getProvider()->getAllPlayers() as $var => $c){
                        $auth = "denied";
                        if($args[1] == "all") $auth = "allowed";
                                        
                        if($args[1] == "admin" && $this->getPlayer($var) instanceof Player){
                            if($this->getPlayer($var) instanceof Player && $this->getPlayer($var)->isOp()) $auth = "allowed";                                                     
                        }
                        $get = $this->SignShop->getProvider()->getPlayer($var);
                        $get["authorized"] = $auth;
                        $get["changed"] = time();
                        $this->SignShop->getProvider()->setPlayer($var, $get);
                    }     
                    $MessageManager->send($sender, "Now_@@_can_use_the_command_/sign", $MessageManager->getMessage($args[1]));
                }else
                    $MessageManager->send($sender, "Invalid_arguments");
                return;  
        }   
        $MessageManager->send($sender, "The_command_<@@>_was_not_found!_Use_/sign_help", $args[0]);
    }    
    
    public function onCommandUser(CommandSender $sender, array $args){
        $MessageManager = $this->MessageManager;

        $MessageManager = $this->SignShop->messageManager();
        if($this->SignShop->getProvider()->getPlayer($sender->getName())["authorized"] != "denied"){       
            switch(strtolower($args[0])){
                case "?":
                case "h":
                case "help":
                    if($sender->isOp() || $this->SignShop->getProvider()->getPlayer($sender->getName())["authorized"] == "root")
                        $sender->sendMessage($this->showHelp(false, true, true));
                    else
                        $sender->sendMessage($this->showHelp());                  
                    return;
                    
                case "echo":
                case "say":
                    $get = $this->SignShop->getProvider()->getPlayer($sender->getName());
                    switch(strtolower(trim($args[1]))){
                        case "on":
                        case "true":
                            $get["echo"] = true; 
                            $this->SignShop->getProvider()->setPlayer($sender->getName(), $get);                                      
                            $MessageManager->send($sender, "The_action_has_been_executed_successfully");
                            return;
                        
                        case "off":
                        case "false":
                            $get["echo"] = false; 
                            $this->SignShop->getProvider()->setPlayer($sender->getName(), $get);        
                            $MessageManager->send($sender, "The_action_has_been_executed_successfully");
                            return;  
                    }
                    $MessageManager->send($sender, "Invalid_arguments");
                    return;
                    
                case "v":
                case "view":
                    $this->SignShop->temp[$sender->getName()] = ["action" => "view"];
                    $MessageManager->send($sender, "Now_touch_on_the_Sign_that_you_want_to_do_this_action");
                    return;
                    
                case "remove":
                    $this->SignShop->temp[$sender->getName()] = ["action" => "remove"];
                    $MessageManager->send($sender, "Now_touch_on_the_Sign_that_you_want_to_do_this_action");
                    return;
                
                case "empty":
                    $this->SignShop->temp[$sender->getName()] = ["action" => "empty"];
                    $MessageManager->send($sender, "Now_touch_on_the_Sign_that_you_want_to_do_this_action");
                    return;
                    
                case "s":
                case "set":
                    if(count($args) == 3){
                        switch(strtolower($args[1])){
                            case "amount":
                                if(is_numeric($args[2]) && $args[2] > 0){                                        
                                    $this->SignShop->temp[$sender->getName()] = ["action" => "set", "arg" => "amount", "value" => $args[2]];
                                    $MessageManager->send($sender, "Now_touch_on_the_Sign_that_you_want_to_do_this_action");
                                }else
                                    $MessageManager->send($sender, "Invalid_value_of_@@",  $MessageManager->getMessage("cost"));
                                return;                      
                            
                            case "available":
                                if(is_numeric($args[2]) && $args[2] > 0 && $args[2] < (64 * 45)){                                        
                                    $this->SignShop->temp[$sender->getName()] = ["action" => "set", "arg" => "available", "value" => $args[2]];
                                    $MessageManager->send($sender, "Now_touch_on_the_Sign_that_you_want_to_do_this_action");
                                }else{
                                    if(strtolower($args[2]) == "unlimited"){
                                        if($this->SignShop->getProvider()->getPlayer($sender->getName())["authorized"] == "root"){
                                            $this->SignShop->temp[$sender->getName()] = ["action" => "set", "arg" => "unlimited"];
                                            $MessageManager->send($sender,"Now_touch_on_the_Sign_that_you_want_to_do_this_action");  
                                        }else
                                            $MessageManager->send($sender, "You_are_not_authorized_to_run_this_command");
                                    }else
                                        $MessageManager->send($sender, "Invalid_value_of_@@",  $MessageManager->getMessage("available"));
                                }
                                return;
                                
                            case "cost":
                                if(is_numeric($args[2]) && $args[2] >= 0){                                        
                                    $this->SignShop->temp[$sender->getName()] = ["action" => "set", "arg" => "cost", "value" => $args[2]];
                                    $MessageManager->send($sender, "Now_touch_on_the_Sign_that_you_want_to_do_this_action");
                                }else
                                    $MessageManager->send($sender, "Invalid_value_of_@@",  $MessageManager->getMessage("cost"));
                                return;
                                
                            case "maker": 
                                if($args[2] != " "){
                                    $this->SignShop->temp[$sender->getName()] = ["action" => "set", "arg" => "maker", "name" => $args[2]];
                                    $MessageManager->send($sender, "Now_touch_on_the_Sign_that_you_want_to_do_this_action");
                                }else
                                    $MessageManager->send($sender, "Invalid_value_of_@@",  $MessageManager->getMessage("maker"));
                                return;
                        }                            
                    }
                    $MessageManager->send($sender, "Invalid_arguments");
                return;
            }
            return true;          
        }else
            $MessageManager->send($sender, "You_are_not_authorized_to_run_this_command");
        return;
    }
        
    public function showHelp($cmd = false, $op = false, $plr = false){
        $tag = $this->MessageManager->getTag();
        $cmd = strtolower(trim($cmd));
        if($cmd){ //TODO
            $var = false;
            switch($cmd){
                case "allow":
                    return;
                case "deny":
                    return;
                case "super":
                case "root":
                    return;
                case "setup":
                    return;
                case "reload":
                    return;
                case "set":
                    return;
                case "view":
                    return;
                case "show":
                    return;
                case "remove":
                    return;
                case "echo":
                    return;    
                case "empty":
                    return;
            }
            return str_replace("@@", $cmd, $this->SignShop->messageManager()->getMessage("The_command_<@@>_was_not_found!_Use_/sign_help"));
        }
        $message = "";
        if($op){
            $var = ["allow <player>", "deny <player>", "root <player>", "show <player>", "reload", "setup <all-admin>"];
            foreach($var as $c)
                $message = $message.$tag.TextFormat::AQUA."/sign ".$c."\n";
                
            if($plr)                 
                $message = $message."\n".$this->showHelp(false, false);
        }else{
            $var = ["set <amount|available|cost|maker> <value>", "view", "echo <on|off>", "empty"];
            foreach($var as $c)
                $message = $message.$tag.TextFormat::AQUA."/sign ".$c."\n";
        }
        return $message;
    }    

    public function getPlayer($player){
        return Server::getInstance()->getPlayer(trim($player));
    }  
    
    public function getPlugin() {
        return $this->SignShop;
    }
}