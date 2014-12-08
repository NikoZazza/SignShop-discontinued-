<?php
/* @author xionbig
 * @link http://xionbig.altervista.org/SignShop 
 * @link http://forums.pocketmine.net/plugins/signshop.668/
 * @version 0.8.0 */

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
use pocketmine\math\Vector3;

class SignShop extends PluginBase implements Listener{    
    public $config, $temp = [];
    protected $provider, $listItems, $mex, $moneyManager, $tempAuction;
    
    public function onEnable(){  
        $dataResources = $this->getDataFolder()."/resources/";
        if (!file_exists($this->getDataFolder())) @mkdir($this->getDataFolder(), 0755, true);
        if (!file_exists($dataResources)) @mkdir($dataResources, 0755, true);
        
        $continue = false;
        if(file_exists($dataResources. "messages.yml")){
            $c = new Config($dataResources. "messages.yml", Config::YAML);
            $this->mex = $c->getAll();
            if(isset($this->mex["version_mex"])){
                if($this->mex["version_mex"] != "eighty")
                    $this->getLogger()->info(TextFormat::RED."Please update the file messages.yml");
                else
                    $continue = true;
            }else
                $this->getLogger()->info(TextFormat::RED."Please update the file messages.yml");            
        }
               
        if($continue == false){            
            $this->mex = ["version_mex" => "eighty", "The_Sign_successfully_removed" => "", "You_need_to_free_up_space_from_your_inventory_to_remove_this_Sign" => "", "The_Sign_was_stocked_with_success" => "", "You_do_not_have_enough_blocks_to_fill_the_Sign" => "", "The_selected_Sign_is_not_your" => "", "You_do_not_have_enough_money" => "", "The_content_of_the_Sign_is_sold_out" => "", "You_bought_the_contents_of_the_Sign" => "", "You_do_not_have_the_space_to_buy_the_contents_of_this_Sign" => "", "Sign_successfully_created" => "", "The_item_was_not_found_or_does_not_have_enough_items" => "", "Invalid_arguments" => "", "Invalid_value_of_@@" => "", "item" => "", "amount" => "", "cost" => "", "available" => "", "cost" => "", "player" => "", "You_are_not_authorized_to_run_this_command" => "", "Now_touch_on_the_Sign_that_you_want_to_do_this_action" => "", "You_have_authorized_@@_to_use_the_command_/sign" => "", "You_have_unauthorized_@@_to_use_the_command_/sign" => "", "Now_@@_can_use_the_command_/sign" => "", "There_is_not_Sign_in_the_world" => "", "All_Signs_respawned" => "", "The_player_@@_is_not_exists" => "", "The_cost_of_the_contents_of_this_Sign_is_now_@@" => "", "This_Sign_is_owned_by_@@" => "", "This_Sign_was_created_@@" => "", "There_are_still_@@_blocks/items" => "", "They_were_sold_@@_blocks/items_with_this_Sign" => "", "The_owner_has_earned_@@" => "", "You_set_the_amount_of_the_Sign_in_@@" => "", "The_player_@@_is_not_authorized_to_run_the_command_/sign" => "", "The_player_@@_is_authorized_to_run_the_command_/sign" => "", "Touch_on_the_Sign_that_you_want_to_know_the_information" => "", "You_have_authorized_@@_to_create_the_Signs_without_the_blocks_in_the_inventory" => "", "Now_this_Sign_is_owned_by_@@" => "", "The_player_was_not_found" => "", "You_can_not_buy_in_creative" => "", "There_is_a_problem_with_the_creation_of_the_Sign" => ""];
            foreach($this->mex as $var => $c){
                $c = str_replace("_", " ", $var);
                $this->mex[$var] = $c;
            }                      
        }        

        $this->config = new Config($dataResources. "config.yml", Config::YAML, [
                "version" => "eighty",
                "signCreated" => "admin",
                "lastChange" => time(),
                "dataProvider" => "YAML",
                "database" => [
                    "host" => "none",
                    "user" => "none",
                    "database" => "none",
                    "port" => "none"],
                "temp" => [],
            ]);
        $this->temp = $this->config->get("temp");
        
        switch(strtolower($this->config->get("dataProvider"))){
            default:
                $this->getLogger()->info(TextFormat::RED."The field dataProvider in config.yml is incorrect!");            
            case "yml":
            case "yaml":
                $this->provider = new \SignShop\provider\YAML($this);
                break;
            /*case "mysql": TODO
                $this->provider = new \SignShop\provider\MySQL($this);
                break;*/
            case "sql":
            case "sqlite":
            case "sqlite3":
                $this->provider = new \SignShop\provider\SQLite($this);
                break;
        }
        
        $this->listItems = new ListItems();
        $this->moneyManager = new MoneyManager($this);

        $this->getServer()->getPluginManager()->registerEvents(new EventListener\PlayerSpawnEvent($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener\PlayerTouchEvent($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener\PlayerBlockBreakEvent($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener\PlayerSignCreateEvent($this), $this);
        
        Server::getInstance()->getScheduler()->scheduleRepeatingTask(new TaskSignAuction($this), 20);
        
        foreach($this->getProvider()->getAllSigns() as $var => $c)
            if($c["type"] == "auction" && $c["second"] > 0) $this->addSignAuction($var);        
            
        $this->respawnAllSign();
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
        if(Server::getInstance()->getPlayer($player)->isOnline()){
            return Server::getInstance()->getPlayer($player);
        }
        return false;     
    }
    
    public function update(){
        if(count($this->tempAuction) > 0){
            foreach($this->tempAuction as $var => $c){
                $get = $this->getProvider()->getSign($var);
                
                $get["second"] = $get["second"] - 1;

                $this->getProvider()->setSign($var, $get);
                                
                $g = explode(":", $var);
                $g[3] = str_replace("%", " ", $g[3]);
                
                $tile = Server::getInstance()->getLevelByName($g[3])->getTile(new Vector3($g[0], $g[1], $g[2]));
                
                $tile->setText("[SignAuction]", $this->getItems()->getName($get["id"]), "Cost: ".$get["cost"]." x".$get["amount"], $get["second"]." sec");
                if($get["second"] <= 0){
                    $this->getMoneyManager()->addMoney($get["bidder"], -$get["cost"]);
                    $this->getMoneyManager()->addMoney($get["maker"], $get["cost"]);
                    
                    $player = $this->getPlayer($get["bidder"]);
                    
                    $player->getInventory()->addItem(Item::get($get["id"], $get["damage"], $get["amount"]));
                    $player->sendMessage("[SignShop] You've won the auction.");
                    
                    $tile->setText("[SignAuction]", $this->getItems()->getName($get["id"]), "Cost: ".$get["cost"]." x".$get["amount"], "AuctionEnded");

                    $this->getProvider()->removeSign($var);
                    $this->removeSignAuction($var);
                }           
            }
        }
    }    
    
    public function addSignAuction($var){
        $this->tempAuction[$var] = true;
    }
    
    public function removeSignAuction($var){
        if(isset($this->tempAuction[$var]))
            unset($this->tempAuction[$var]);
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
        $this->config->set("temp", array_merge($this->temp));
        $this->config->save();
        $this->getProvider()->onDisable();
    }
}