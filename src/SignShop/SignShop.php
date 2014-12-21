<?php
/* @author xionbig
 * @link http://xionbig.altervista.org/SignShop 
 * @link http://forums.pocketmine.net/plugins/signshop.668/
 * @version 0.9.0 */

namespace SignShop;

use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\tile\Sign;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\String;
use pocketmine\nbt\tag\Int;
use pocketmine\tile\Tile;
use pocketmine\level\Position;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\block\Block;
use SignShop\Provider\YAMLProvider;
use SignShop\Provider\SQLiteProvider;
use SignShop\Provider\MySQLProvider;

class SignShop extends PluginBase implements Listener{    
    public $temp = [];
    protected $setup, $provider, $listItems, $mex, $moneyManager;
    
    public function onEnable(){  
        $dataResources = $this->getDataFolder()."/resources/";
        if (!file_exists($this->getDataFolder())) @mkdir($this->getDataFolder(), 0755, true);
        if (!file_exists($dataResources)) @mkdir($dataResources, 0755, true);
        
        $continue = false;
        if(file_exists($dataResources. "messages.yml")){
            $c = new Config($dataResources. "messages.yml", Config::YAML);
            $this->mex = $c->getAll();
            if(isset($this->mex["version_mex"])){
                if($this->mex["version_mex"] != "ninety")
                    $this->getLogger()->info(TextFormat::RED."Please update the file messages.yml");
                else
                    $continue = true;
            }else
                $this->getLogger()->info(TextFormat::RED."Please update the file messages.yml");            
        }
               
        if($continue == false){            
            $this->mex = ["version_mex" => "ninety", "The_Sign_successfully_removed" => "", "You_need_to_free_up_space_from_your_inventory_to_remove_this_Sign" => "", "The_Sign_was_stocked_with_success" => "", "You_do_not_have_enough_blocks_to_fill_the_Sign" => "", "The_selected_Sign_is_not_your" => "", "You_do_not_have_enough_money" => "", "The_content_of_the_Sign_is_sold_out" => "", "You_bought_the_contents_of_the_Sign" => "", "You_do_not_have_the_space_to_buy_the_contents_of_this_Sign" => "", "Sign_successfully_created" => "", "The_item_was_not_found_or_does_not_have_enough_items" => "", "Invalid_arguments" => "", "Invalid_value_of_@@" => "", "item" => "", "amount" => "", "cost" => "", "available" => "", "cost" => "", "player" => "", "You_are_not_authorized_to_run_this_command" => "", "Now_touch_on_the_Sign_that_you_want_to_do_this_action" => "", "The_cost_of_the_contents_of_this_Sign_is_now_@@" => "", "This_Sign_is_owned_by_@@" => "", "This_Sign_was_created_@@" => "", "There_are_still_@@_blocks/items" => "", "They_were_sold_@@_blocks/items_with_this_Sign" => "", "The_owner_has_earned_@@" => "", "You_set_the_amount_of_the_Sign_in_@@" => "", "Touch_on_the_Sign_that_you_want_to_know_the_information" => "", "Now_this_Sign_is_owned_by_@@" => "", "The_player_was_not_found" => "", "You_can_not_buy_in_creative" => "", "There_is_a_problem_with_the_creation_of_the_Sign" => "", "Now_this_Sign_has_the_unlimited_available" => "",  "You_earned_@@_when_you_were_offline" => "", "You_can_not_buy_from_your_Sign" => "", "The_action_has_been_executed_successfully" => "", "The_formatting_is_finished_successfully" => "", "To_format_this_information,_use_/sign_earned_format" => "", "In_total_you_have_spent_@@_with_Signs" => "", "In_total_you_have_earned_@@_with_Signs" => "", "The_command_<@@>_was_not_found!_Use_/sign_help" => ""];
            foreach($this->mex as $var => $c){
                $c = str_replace("_", " ", $var);
                $this->mex[$var] = $c;
            }                      
        }        
        
        $this->setup = new Config($dataResources. "config.yml", Config::YAML, [
                "version" => "ninety",
                "signCreated" => "admin",
                "lastChange" => time(),
                "dataProvider" => "YAML"
            ]);
        
        switch(strtolower($this->setup->get("dataProvider"))){
            default:
                $this->getLogger()->info(TextFormat::RED."The field 'dataProvider' in config.yml is incorrect!");            
            case "yml":
            case "yaml":
                $this->provider = new YAMLProvider($this);
                break;
            case "sql":
            case "sqlite":
            case "sqlite3":
                $this->provider = new SQLiteProvider($this);
                break;
        }
        
        $this->listItems = new ListItems();
        $this->moneyManager = new MoneyManager($this);

        $this->getServer()->getPluginManager()->registerEvents(new EventListener\PlayerSpawnEvent($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener\PlayerTouchEvent($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener\PlayerBlockBreakEvent($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener\PlayerSignCreateEvent($this), $this);
                            
        $this->respawnAllSign();
    } 
    public function getSetup(){
        return $this->setup;
    }    
    public function getProvider(){
        return $this->provider;
    }
    public function getItems(){
        return $this->listItems;
    }
    public function getMessages(){
        return $this->mex;
    }
    public function getMoneyManager(){
        return $this->moneyManager;
    }
    
    public function isOnlinePlayer($player){
        $player = strtolower($player);
        foreach(Server::getInstance()->getOnlinePlayers() as $var){
            if(strtolower($var->getDisplayName()) == $player) return true;       
        }
        return false;        
    } 
 
    public function getPlayer($player){
        $player = strtolower($player);
        if($this->isOnlinePlayer($player)){
            return Server::getInstance()->getPlayer($player);
        }
        return false;     
    }
            
    public function onCommand(CommandSender $sender, Command $command, $label, array $args){
        return new \SignShop\Command\SignCommands($this, $sender, $args);
    }
    
    private function spawnSign(Position $pos, $get){           
        if($get["available"] != "unlimited" && $get["available"] - $get["amount"] <= 0)
            $get["cost"] = "Out Of Stock";
        else{
            if($get["cost"] == 0) 
                $get["cost"] = "Price: FREE";
            else 
                $get["cost"] = "Price: ".$get["cost"].$this->getMoneyManager()->getValue();
        }    
        if(!$pos->level->isChunkGenerated($pos->x, $pos->z)) $pos->level->generateChunk($pos->x, $pos->z);
        
        if($pos->level->getBlockIdAt($pos->x, $pos->y, $pos->z) != Item::SIGN_POST || $pos->level->getBlockIdAt($pos->x, $pos->y, $pos->z) != Item::SIGN)
            $pos->level->setBlock($pos, Block::get(Item::SIGN_POST, $get["direction"]), false, true);
        
        $sign = new Sign($pos->level->getChunk($pos->x >> 4, $pos->z >> 4, true), new Compound(false, array(
            new Int("x", $pos->x),
            new Int("y", $pos->y),
            new Int("z", $pos->z),
            new String("id", Tile::SIGN),
            new String("Text1", "[".$get["maker"]."]"),
            new String("Text2", $this->getItems()->getName($get["id"], $get["damage"])),
            new String("Text3", "Amount: x".$get["amount"]),
            new String("Text4", $get["cost"])
            )));
        $sign->saveNBT();
        $sign->spawnToAll();
    }   
    
    public function respawnSign($var){  
        if($this->getProvider()->existsSign($var)){
            $g = explode(":", $var);
            if(!isset($g[3]))
                $g[3] = Server::getInstance()->getDefaultLevel()->getName();
                        
            $g[3] = str_replace("%", " ", $g[3]);
            if(Server::getInstance()->isLevelGenerated($g[3]) == true)
                $this->spawnSign(new Position($g[0], $g[1], $g[2], Server::getInstance()->getLevelByName($g[3])), $this->getProvider()->getSign($var));            
        }
    }
    
    public function respawnAllSign(){
        if(count($this->getProvider()->getAllSigns()) <= 0)
            return "There is not Signs in the worlds";
        else{
            foreach($this->getProvider()->getAllSigns() as $var => $c)
                $this->respawnSign($var);                
            return "All Signs respawned";
        }
    }
    
    public function onDisable(){
        $this->getSetup()->save();
        $this->getProvider()->onDisable();
    }
}