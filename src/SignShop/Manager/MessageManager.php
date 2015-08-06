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
namespace SignShop\Manager;

use SignShop\SignShop;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\command\CommandSender;

class MessageManager{    
    private $SignShop;
    private $tag = TextFormat::GOLD."[SignShop] ";
    private $dataResources;
    
    private $message = [
        "The_Signs_in_the_world_@@_have_been_put_back" => ["message" => "", "alert" => "success"],
        "The_world_@@_was_not_found" => ["message" => "", "alert" => "error"],
        "All_the_Signs_have_been_put_back" => ["message" => "", "alert" => "succes"],
        "There_are_no_Signs_spammed" => ["message" => "", "alert" => "error"],
        "You_have_authorized_@@_to_use_the_command_/sign" => ["message" => "", "alert" => "success"],
        "The_player_@@_is_not_exists" => ["message" => "", "alert" => "warning"],
        "Invalid_arguments" => ["message" => "", "alert" => "error"], 
        "You_have_unauthorized_@@_to_use_the_command_/sign" => ["message" => "", "alert" => "succsee"],
        "You_have_authorized_@@_to_create_the_Signs_without_the_blocks_in_the_inventory" => ["message" => "", "alert" => "success"],
        "The_player_@@_is_authorized_to_run_the_command_/sign" => ["message" => "", "alert" => "info"],
        "The_player_@@_is_not_authorized_to_run_the_command_/sign" => ["message" => "", "alert" => "info"],
        "The_command_<@@>_was_not_found!_Use_/sign_help" => ["message" => "", "alert" => "error"],     
        "Now_@@_can_use_the_command_/sign" => ["message" => "", "alert" => "success"],
        "all" => ["message" => "", "alert" => "success"],
        "admin" => ["message" => "", "alert" => "success"],
        "The_action_has_been_executed_successfully" => ["message" => "", "alert" => "success"], 
        "Now_touch_on_the_Sign_that_you_want_to_do_this_action" => ["message" => "", "alert" => "info"],
        "Invalid_value_of_@@" => ["message" => "", "alert" => "error"], 
        "amount" => ["message" => "", "alert" => "error"],
        "cost" => ["message" => "", "alert" => "error"], 
        "maker" => ["message" => "", "alert" => "error"],
        "available" => ["message" => "", "alert" => "error"],
        //*************************************************
        "item" => ["message" => "", "alert" => "error"], 
        "cost" => ["message" => "", "alert" => "error"],
        "player" => ["message" => "", "alert" => "error"],
        //*************************************************
        "You_are_not_authorized_to_run_this_command" => ["message" => "", "alert" => "warning"],
        "The_selected_Sign_is_not_your" => ["message" => "", "alert" => "error"],
        "The_Sign_successfully_removed" => ["message" => "", "alert" => "success"],
        "You_need_to_free_up_space_from_your_inventory_to_remove_this_Sign" => ["message" => "", "alert" => "warning"],
        "Sign_successfully_created" => ["message" => "", "alert" => "success"],
        "The_item_was_not_found_or_does_not_have_enough_items" => ["message" => "", "alert" => "error"], 
        "There_is_a_problem_with_the_creation_of_the_Sign" => ["message" => "", "alert" => "error"],
        "The_owner_has_earned_@@" => ["message" => "", "alert" => "info"],
        "This_Sign_is_owned_by_@@" => ["message" => "", "alert" => "info"],
        "There_are_still_@@_blocks/items" => ["message" => "", "alert" => "info"],
        "They_were_sold_@@_blocks/items_with_this_Sign" => ["message" => "", "alert" => "info"],
        "The_Sign_was_stocked_with_success" => ["message" => "", "alert" => "success"],
        "You_do_not_have_enough_blocks_to_fill_the_Sign" =>["message" => "", "alert" => "error"],
        "You_set_the_available_of_the_Sign_in_@@" => ["message" => "", "alert" => "info"],
        "You_set_the_amount_of_the_Sign_in_@@" => ["message" => "", "alert" => "info"],
        "The_cost_of_the_contents_of_this_Sign_is_now_@@" => ["message" => "", "alert" => "info"],
        "Now_this_Sign_is_owned_by_@@" => ["message" => "", "alert" => "success"],
        "You_can_not_buy_from_your_Sign" => ["message" => "", "alert" => "error"],
        "payment_from_@@" => ["message" => "", "alert" => "info"],
        "You_can_not_buy_or_sell_in_creative" => ["message" => "", "alert" => "error"],
        "Now_this_Sign_has_the_unlimited_available" => ["message" => "", "alert" => "success"],
        "You_do_not_have_enough_money" => ["message" => "", "alert" => "warning"],
        "The_content_of_the_Sign_is_sold_out" => ["message" => "", "alert" => "warning"],
        "You_bought_the_contents_of_the_Sign" => ["message" => "", "alert" => "success"],  
        "You_do_not_have_the_space_to_buy_the_contents_of_this_Sign" => ["message" => "", "alert" => "warning"],
        "This_command_must_be_run_from_the_console" => ["message" => "", "alert" => "warning"],
        "You_can_not_create_the_Signs_from_the_creative_mode" => ["message" => "", "alert" => "warning"],        
        "You_need_space_to_get_the_items_from_the_Sign" => ["message" => "", "alert" => "warning"],
        "The_amount_must_be_less_than_@@" => ["message" => "", "alert" => "error"],
        "This_sign_is_not_registered" => ["message" => "", "alert" => "warning"],
        "You_do_not_have_enough_space_to_get_the_contents_of_the_Sign" => ["message" => "", "alert" => "warning"],
        "You_have_successfully_sold_the_items" => ["message" => "", "alert" => "success"],
        "The_Sign_is_full" => ["message" => "", "alert" => "warning"],
        "The_Sign_is_empty" => ["message" => "", "alert" => "warning"],
        "The_Sign_has_been_emptied_successfully" => ["message" => "", "alert" => "success"],
        "This_feature_is_not_yet_available_for_SignSell" =>  ["message" => "", "alert" => "error"],
        "The_amount_of_the_Sign_is_unlimited" => ["message" => "", "alert" => "warning"],
        "You_can_not_sell_your_items_to_your_Sign,_if_you_want_you_can_empty_it_executing_the_command_/sign_empty" => ["message" => "", "alert" => "error"],
        "The_maker_of_Sign_does_not_have_enough_money_to_give_you_the_money" => ["message" => "", "alert" => "error"],
    ];
    
    public function __construct(SignShop $SignShop, $dataResources){
        $this->SignShop = $SignShop;
        
        $file_message = new Config($dataResources. "messages.yml", Config::YAML, $this->message);
        $this->dataResources = $dataResources;
        if($file_message->get("version_mex") != "oneone"){
            $SignShop->getServer()->getLogger()->info(TextFormat::RED."Please update the file messages.yml");            
        }
        
        foreach($this->message as $var => $c){
            if($file_message->exists($var) && isset($file_message->get($var)["message"]) && !empty($file_message->get($var)["message"]))
                $c = $file_message->get($var)["message"];
            else
                $c = str_replace("_", " ", $var);
            $this->message[$var]["message"] = $c;                    
        }
        $this->message["version_mex"] = "oneone";
        $file_message->setAll($this->message);
        $file_message->save();
    }
    
    public function send($player, $message, $toReplace = ""){
        if(!($player instanceof Player)){
            if(!($player instanceof CommandSender))
                return;
        }
        
        $message = trim($message);
        $smessage = str_replace(" ", "_", $message);
        
        if(!isset($this->message[$message]) || $this->message[$message]["message"] == ""){
            $player->sendMessage($this->tag.$this->getColor("").$message);
            return;
        } 
        $message = $smessage;
        $alert = $this->getColor($this->message[$message]["alert"]);
        $message = $this->message[$message]["message"];
        
        
        if($toReplace != "" && explode("@@", $message) != 0)
            $message = str_replace("@@", $toReplace, $message);
        $player->sendMessage($this->tag.$alert.$message);
    }
        
    public function downloadLang($player, $lang, $passwd = false){
        $player->sendMessage($this->tag.$this->getColor("warning")."Connecting to the server");
        if($lang == "en"){
            $player->sendMessage($this->tag.$this->getColor("warning")."Restore the original language...");
            foreach($this->message as $var => $c){
                $array[$var] = str_replace("_", " ", $var);
            }
        }else{   
            $url = $this->SignShop->getSetup()->get("server")."?lang=".$lang."&password=".$passwd."&version=".$this->SignShop->getSetup()->get("version");
            $array = json_decode($this->url_get_contents($url), false, 512, JSON_UNESCAPED_UNICODE);
            if(count($array) < 1){
                $player->sendMessage($this->tag.$this->getColor("error")."Server not found");  
                return;
            }
        }
        foreach($array as $var => $c){            
            if($var != "_empty_"){
                if(!isset($this->message[$var]["alert"]))
                    $alert = "";
                else
                    $alert = $this->message[$var]["alert"];
                $this->message[$var] = ["message" => trim($c), "alert" => $alert];
            
            }
            if(trim($c) == "" && $var != "_empty_"){
                if(strlen($lang) > 2)
                    $player->sendMessage($this->tag.$this->getColor("error")."The user or password is incorrect");  
                else
                    $player->sendMessage($this->tag.$this->getColor("error")."The language '$lang' was not found");  
                return;
            }
        }         
        $file_message = new Config($this->dataResources. "messages.yml", Config::YAML, $this->message);
        $file_message->setAll($this->message);
        $file_message->save();
        $player->sendMessage($this->tag.$this->getColor("success")."Language Packs successfully downloaded");
    }
    
    private function url_get_contents($url) {
        if(function_exists('curl_exec')){ 
            $conn = curl_init($url);
            curl_setopt($conn, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($conn, CURLOPT_FRESH_CONNECT,  true);
            curl_setopt($conn, CURLOPT_RETURNTRANSFER, 1);
            $url_get_contents_data = (curl_exec($conn));
            curl_close($conn);
        }elseif(function_exists('file_get_contents'))
            $url_get_contents_data = file_get_contents($url);
        elseif(function_exists('fopen') && function_exists('stream_get_contents')){
            $handle = fopen ($url, "r");
           $url_get_contents_data = stream_get_contents($handle);
        }else
            $url_get_contents_data = false;
        return $url_get_contents_data;
    } 
    
    public function getMessage($message){
        $message = strtolower(trim($message));
        if(isset($this->message[$message]))
            return $this->getColor($this->message[$message]["alert"]).$this->message[$message]["message"];
        else
            return $message;
    }
    
    public function getTag(){
        return $this->tag;
    }
    
    public function getColor($var){
        switch($var){
            case "success":
                return TextFormat::DARK_GREEN;
            case "error":
                return TextFormat::RED;
            case "warning":
                return TextFormat::YELLOW;
        }      
        return TextFormat::WHITE;
    }    
}