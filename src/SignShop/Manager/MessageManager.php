<?php
/**
 * @author xionbig
 * @link http://xionbig.altervista.org/SignShop 
 * @link http://forums.pocketmine.net/plugins/signshop.668/
 * @version 1.0.0
 */
namespace SignShop\Manager;

use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class MessageManager{    
    private $SignShop;
    
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
        "You_can_not_buy_in_creative" => ["message" => "", "alert" => "error"],
        "Now_this_Sign_has_the_unlimited_available" => ["message" => "", "alert" => "success"],
        "You_do_not_have_enough_money" => ["message" => "", "alert" => "warning"],
        "The_content_of_the_Sign_is_sold_out" => ["message" => "", "alert" => "warning"],
        "You_bought_the_contents_of_the_Sign" => ["message" => "", "alert" => "success"],  
        "You_do_not_have_the_space_to_buy_the_contents_of_this_Sign" => ["message" => "", "alert" => "warning"],
        "This_command_must_be_run_from_the_console" => ["message" => "", "alert" => "warning"],
        "You_can_not_create_the_Signs_from_the_creative_mode" => ["message" => "", "alert" => "warning"],        
        "You_need_space_to_get_the_items_from_the_Sign" => ["message" => "", "alert" => "warning"],        
    ];
    private $tag;
    
    public function __construct($SignShop, $dataResources){
        $this->SignShop = $SignShop;
        $this->tag = TextFormat::GOLD."[SignShop] ";
       
        $continue = false;
        if(file_exists($dataResources. "messages.yml")){
            $c = new Config($dataResources. "messages.yml", Config::YAML);
            $this->mex = $c->getAll();
            if(isset($this->mex["version_mex"])){
                if($this->mex["version_mex"] != "one")
                    $this->getLogger()->info(TextFormat::RED."Please update the file messages.yml");
                else
                    $continue = true;
            }else
                $SignShop->getServer()->getLogger()->info(TextFormat::RED."Please update file messages.yml");            
        }
               
        if($continue == false){            
            foreach($this->message as $var => $c){
                $c = str_replace("_", " ", $var);
                $this->message[$var]["message"] = $c;
            }                    
        }
    }
    
    public function send($player, $message, $toReplace = ""){
        if(!($player instanceof Player)){
            if(!($player instanceof \pocketmine\command\CommandSender))
                return;
        }
        
        $message = trim($message);
        $message = str_replace(" ", "_", $message);
         
        if(!isset($this->message[$message]) || $this->message[$message] == "")
            return;
        
        $alert = $this->getColor($this->message[$message]["alert"]);
        $message = $this->message[$message]["message"];
        
        
        if($toReplace != "" && explode("@@", $message) != 0)
            $message = str_replace("@@", $toReplace, $message);
        $player->sendMessage($this->tag.$alert.$message);
    }
    
    public function sendMessage($player, $message){
        if(!($player instanceof Player)){
            if(!($player instanceof \pocketmine\command\CommandSender))
                return;
        }
        
        $message = trim($message);
        
        $player->sendMessage($this->tag.$message);
    }
    
    public function getMessage($message){
        $message = strtolower(trim($message));
        if(isset($this->message[$message]))
            return $this->getColor($this->message[$message]["alert"]).$this->message[$message]["message"];
        else
            return " ";
    }
    
    public function getTag(){
        return $this->tag;
    }
    
    public function getColor($var){
        switch($var){
            case "error":
                return TextFormat::RED;
            case "warning":
                return TextFormat::YELLOW;
        }      
        return TextFormat::WHITE;
    }    
}