<?php
/**
 * @author xionbig
 * @link http://xionbig.altervista.org/SignShop 
 * @link http://forums.pocketmine.net/plugins/signshop.668/
 * @version 1.0.0
 */
namespace SignShop\Provider;

class SQLiteProvider {
    private $plr, $sign;
    
    public function __construct($SignShop){      
        if(file_exists($SignShop->getDataFolder()."/resources/player.db"))
            $this->plr = new \SQLite3($SignShop->getDataFolder()."/resources/player.db", SQLITE3_OPEN_READWRITE);
        else
            $this->plr = new \SQLite3($SignShop->getDataFolder()."/resources/player.db", SQLITE3_OPEN_CREATE|SQLITE3_OPEN_READWRITE);
        
        if(file_exists($SignShop->getDataFolder()."/resources/sign.db"))
            $this->sign = new \SQLite3($SignShop->getDataFolder()."/resources/sign.db", SQLITE3_OPEN_READWRITE);
        else
            $this->sign = new \SQLite3($SignShop->getDataFolder()."/resources/sign.db", SQLITE3_OPEN_CREATE|SQLITE3_OPEN_READWRITE);
                      
        $this->sign->exec('CREATE TABLE IF NOT EXISTS sign (var VARCHAR(255) PRIMARY KEY, id INT(4), damage INT(2), amount INT(10)), available VARCHAR(255), cost INT(255), maker VARCHAR(255), sold INT(255), earned INT(255), direction INT(10))');
        $this->plr->exec('CREATE TABLE IF NOT EXISTS plr (player varchar(255), authorized varchar(10), changed int(15), echo varchar(6))');
       
        if($SignShop->getSetup()->get("version") != "one"){
            foreach($this->getAllPlayers() as $var => $c){
                if($c["authorized"] == "super") $c["authorized"] = "root";
                elseif($c["authorized"] == "auth") $c["authorized"] = "allow";
                elseif($c["authorized"] == "unauth") $c["authorized"] = "denied";
                $this->setPlayer($var, $c);
            }     

            foreach($this->getAllSigns() as $var => $c){
                $pos = explode(":", $var);
                
                $pos[3] = str_replace("%", " ", $pos[3]);
                $this->removeSign($var);
                
                $this->setSign($pos, $c);                
            }
        }
    }
    
    public function getAllPlayers(){
        $return = [];
        $query = $this->plr->query("SELECT * FROM plr WHERE 1");
        if($result instanceof \SQLite3Result){
            while($data = $query->fetchArray(SQLITE3_ASSOC))
                $return[$data["player"]] = $data;
            
            $query->finalize();
            $query->close();
            return $return;
        }
        return false;
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
            $query = $this->plr->prepare("INSERT INTO plr (player, authorized, changed, echo) VALUES (:player, :authorized, :changed, :echo)");
        else
            $query = $this->plr->prepare("UPDATE plr SET authorized = :authorized, changed = :changed, echo = :echo WHERE player = :player");
        
        $query->bindValue(":player", $player, SQLITE3_TEXT);
        $query->bindValue(":authorized", $data["authorized"], SQLITE3_TEXT);
        $query->bindValue(":changed", $data["changed"], SQLITE3_INTEGER);
        $query->bindValue(":echo", $data["echo"], SQLITE3_TEXT);
        
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
            if(isset($data["player"]) && $data["player"] === $player){
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
        if($result instanceof \SQLite3Result){
            while($data = $query->fetchArray(SQLITE3_ASSOC))
                $return[$data["var"]] = $data;
        }
        $query->finalize();
        return $return;
    }
    
    public function setSign($var, array $data){
        if(!$this->existsSign($var))        
            $query = $this->sign->prepare("INSERT INTO sign (var, id, damage, amount, available, cost, maker, sold, earned, direction) VALUES (:var, :id, :damage, :amount, :available, :cost, :maker, :sold, :earned, :direction )");
        else
            $query = $this->sign->prepare("UPDATE sign SET id = :id, damage = :damage, amount = :amount, available = :available, cost = :cost, maker = :maker, sold = :sold, earned = :earned, direction = :direction WHERE var = :var");
        
        $query->bindValue(":var", $var, SQLITE3_TEXT);
        $query->bindValue(":id", $data["id"], SQLITE3_INTEGER);
        $query->bindValue(":damage", $data["damage"], SQLITE3_INTEGER);
        $query->bindValue(":amount", $data["amount"], SQLITE3_INTEGER);
        $query->bindValue(":available", $data["available"], SQLITE3_TEXT);
        $query->bindValue(":cost", $data["cost"], SQLITE3_INTEGER);
        $query->bindValue(":maker", $data["maker"], SQLITE3_TEXT);
        $query->bindValue(":sold", $data["sold"], SQLITE3_INTEGER);
        $query->bindValue(":earned", $data["earned"], SQLITE3_INTEGER);
        $query->bindValue(":direction", $data["direction"], SQLITE3_INTEGER);
        
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
            if(isset($data["var"]) && $data["var"] === $var){
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
