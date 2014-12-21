<?php
/* @author xionbig
 * @link http://xionbig.altervista.org/SignShop 
 * @link http://forums.pocketmine.net/plugins/signshop.668/
 * @version 0.9.0 */

namespace SignShop\Provider;

class SQLiteProvider{
    protected $plr, $sign;
    
    public function __construct($SignShop){      
        if(file_exists($SignShop->getDataFolder()."/resources/player.db"))
            $this->plr = new \SQLite3($SignShop->getDataFolder()."/resources/player.db", SQLITE3_OPEN_READWRITE);
        else
            $this->plr = new \SQLite3($SignShop->getDataFolder()."/resources/player.db", SQLITE3_OPEN_CREATE|SQLITE3_OPEN_READWRITE);
        
        if(file_exists($SignShop->getDataFolder()."/resources/sign.db"))
            $this->sign = new \SQLite3($SignShop->getDataFolder()."/resources/sign.db", SQLITE3_OPEN_READWRITE);
        else
            $this->sign = new \SQLite3($SignShop->getDataFolder()."/resources/sign.db", SQLITE3_OPEN_CREATE|SQLITE3_OPEN_READWRITE);
                
        $this->sign->exec('CREATE TABLE IF NOT EXISTS sign (var varchar(255), id varchar(255), damage varchar(255), amount varchar(255), available varchar(255), cost varchar(255), maker varchar(255), time varchar(255), sold varchar(255), earned varchar(255), type  varchar(255), second varchar(255), direction varchar(255), bidder varchar(255))');
        $this->plr->exec('CREATE TABLE IF NOT EXISTS plr (player varchar(255), authorized varchar(255), changed varchar(255), echo varchar(255), earned varchar(255), totEarned varchar(255), totSpent varchar(255))'); 
        
        foreach($this->getAllPlayers() as $var => $c){
            if($c["authorized"] == "super") $c["authorized"] = "super";
            elseif($c["authorized"] == true) $c["authorized"] = "auth";
            else $c["authorized"] = "unauth";
            $this->setPlayer($var, $c);
        }        
    }
    
    public function getAllPlayers(){
        $return = [];
        $query = $this->plr->query("SELECT * FROM plr WHERE 1");
                
        while($data = $query->fetchArray(SQLITE3_ASSOC))
            $return[$data["player"]] = $data;
        
        $query->finalize();
        $query->close();
        return $return;
    }
    
    public function existsPlayer($player){
        $player = strtolower($player);
        if($this->getPlayer($player) != false) 
            return true;
        return false;
    }
    
    public function setPlayer($player, array $data){
        $player = strtolower($player);
        if(!$this->existsPlayer($player))        
            $query = $this->plr->prepare("INSERT INTO plr (player, authorized, changed, echo, earned, totEarned, totSpent) VALUES (:player, :authorized, :changed, :echo, :earned, :totEarned, :totSpent)");
        else
            $query = $this->plr->prepare("UPDATE plr SET authorized = :authorized, changed = :changed, echo = :echo, earned = :earned, totEarned = :totEarned, totSpent = :totSpent WHERE player = :player");
        
        $query->bindValue(":player", $player, SQLITE3_TEXT);
        $query->bindValue(":authorized", $data["authorized"], SQLITE3_TEXT);
        $query->bindValue(":changed", $data["changed"], SQLITE3_TEXT);
        $query->bindValue(":echo", $data["echo"], SQLITE3_TEXT);
        $query->bindValue(":earned", $data["earned"], SQLITE3_TEXT);
        $query->bindValue(":totEarned", $data["totEarned"], SQLITE3_TEXT);
        $query->bindValue(":totSpent", $data["totSpent"], SQLITE3_TEXT);
        
        $query->execute();
        $query->close();
    }
    
    public function getPlayer($player){
        $player = strtolower($player);
        $query = $this->plr->prepare("SELECT * FROM plr WHERE player = :player");
        $query->bindValue(":player", $player, SQLITE3_TEXT);
        
        $result = $query->execute();
        
        if($result instanceof \SQLite3Result){
            $data = $result->fetchArray(SQLITE3_ASSOC);
            $result->finalize();
            if(isset($data["player"]) and $data["player"] === $player){
                unset($data["player"]);
                $query->close();
                return $data;
            }
        }
        
        $query->close();
        return false;
    }
    
    public function removePlayer($player){
        $player = strtolower($player);
        $query = $this->plr->prepare("DELETE FROM plr WHERE player = :player");
        $query->bindValue(":player", $player, SQLITE3_TEXT);
        
        $query->execute();
        $query->close();
    }
    
    public function getAllSigns(){
        $return = [];
        $query = $this->sign->query("SELECT * FROM sign WHERE 1");
                
        while($data = $query->fetchArray(SQLITE3_ASSOC))
            $return[$data["var"]] = $data;
        $query->finalize();
        
        return $return;
    }
    
    
    
    public function setSign($var, array $data){
        if(!$this->existsSign($var))        
            $query = $this->sign->prepare("INSERT INTO sign (var, id, damage, amount, available, cost, maker, time, sold, earned, type, second, direction, bidder) VALUES (:var, :id, :damage, :amount, :available, :cost, :maker, :time, :sold, :earned, :type, :second, :direction, :bidder)");
        else
            $query = $this->sign->prepare("UPDATE sign SET id = :id, damage = :damage, amount = :amount, available = :available, cost = :cost, maker = :maker, time = :time, sold = :sold, earned = :earned, type = :type, second = :second, direction = :direction, bidder = :bidder WHERE var = :var");
        
        $query->bindValue(":var", $var, SQLITE3_TEXT);
        $query->bindValue(":id", $data["id"], SQLITE3_TEXT);
        $query->bindValue(":damage", $data["damage"], SQLITE3_TEXT);
        $query->bindValue(":amount", $data["amount"], SQLITE3_TEXT);
        $query->bindValue(":available", $data["available"], SQLITE3_TEXT);
        $query->bindValue(":cost", $data["cost"], SQLITE3_TEXT);
        $query->bindValue(":maker", $data["maker"], SQLITE3_TEXT);
        $query->bindValue(":time", $data["time"], SQLITE3_TEXT);
        $query->bindValue(":sold", $data["sold"], SQLITE3_TEXT);
        $query->bindValue(":earned", $data["earned"], SQLITE3_TEXT);
        $query->bindValue(":type", $data["type"], SQLITE3_TEXT);
        $query->bindValue(":second", $data["second"], SQLITE3_TEXT);
        $query->bindValue(":direction", $data["direction"], SQLITE3_TEXT);
        $query->bindValue(":bidder", $data["bidder"], SQLITE3_TEXT);
        
        $query->execute();
        $query->close();
    }
    
    public function getSign($var){        
        $query = $this->sign->prepare("SELECT * FROM sign WHERE var = :var");
        $query->bindValue(":var", $var, SQLITE3_TEXT);
        
        $result = $query->execute();
              
        if($result instanceof \SQLite3Result){
            $data = $result->fetchArray(SQLITE3_ASSOC);
            $result->finalize();
            if(isset($data["var"]) and $data["var"] === $var){
                unset($data["var"]);
                $query->close();
                return $data;
            }
        }
        
        $query->close();
        return false;
    }
    
    public function removeSign($var){
        $query = $this->sign->prepare("DELETE FROM sign WHERE var = :var");
        $query->bindValue(":var", $var, SQLITE3_TEXT);
        
        $query->execute();
        $query->close();
    }
    
    public function onDisable(){
        $this->plr->close();
        $this->sign->close();        
    }

    public function existsSign($var){
        if($this->getSign($var) != false) 
            return true;
        return false;
    
    }

}