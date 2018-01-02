<?php
namespace Marcel2508;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\CommandExecutor;
use pocketmine\plugin\PluginBase;
use pocketmine\item\ItemFactory;
use pocketmine\Server;


class CommandProcessor {

    private $plugin;
    
    public function __construct(PlayerVaults $plugin) {
        $this->plugin = $plugin;
        $this->inventorySpawner = new InventorySpawner();
    }

    //DB LOGIC:
    private function getVaultContents($player,$vault){
        $query = "SELECT * FROM `vaults` WHERE `player` = ".$player." AND `vault` = '".$this->plugin->db->escapeString($vault)."';";
        $res = $this->plugin->db->query($query);
        if($res){
            
            return $res;
        }
        else{
            return null;
        }
    }
    private function clearVaultContents(int $player,int $vault){
        $query = "DELETE FROM `vaults` WHERE `player` = '".$player."' AND `vault` = '".$this->plugin->db->escapeString($vault)."';";
        $res = $this->plugin->db->exec($query);
        return $res;
    }

    /*USELESS
    private function wipeAllVaults(){
        $query = "DELETE FROM `vaults`;";
        $res = $this->plugin->db->exec($query);
        return $res;
    }*/

    private function wipePlayerVaults($player){
        $query = "DELETE FROM `vaults` WHERE `player` = '".$player."';";
        $res = $this->plugin->db->exec($query);
        return $res;
    }

    //COMMAND LOGIC:

    private function showVault(Player $player, int $targetId,int $vault) : bool{
        if($player->getGamemode()==1&&$player->hasPermission("limitedcreative.permission.creative")){
            $player->sendMessage($this->plugin->msg("Limited creative blocks access to this command while in creative mode!"));
            return true;
        }

        $playerId=$player->getId();
        //Creating Inventory
        $inventory = $this->inventorySpawner->getInventory($player,[$targetId,$vault,($targetId!==$playerId?$playerId:-1)],"Vault #".$vault);
        //Get Item Array from DB File
        $content = $this->getVaultContents($targetId,$vault);
        //Add all Items to the Inventory
        while($itemRaw = $content->fetchArray(SQLITE3_ASSOC)){
            $newItem = ItemFactory::FromString($itemRaw["itemid"].":".$itemRaw["meta"]);
            $newItem->setCount($itemRaw["amount"]);
            if($itemRaw["nbt"]!=null){
                $newItem->setCompoundTag($itemRaw["nbt"]);
            }
            //Add Item
            $inventory->setItem($itemRaw["slot"],clone $newItem);
        }

        //Display Vault
        $player->addWindow($inventory);
        $player->sendMessage($this->plugin->msg("§bOpened §3".($targetId!=$playerId?"players ":"")."§bvault #".$vault));

        return true;
    }

    /* USELESS
    private function showVaultAsAdmin(Player $player, array $args) : bool{
        
        $vault = 1;
        $playerId=$player->getId();
        $targetPlayerId=-1;
        if(count($args)==2){
            if(!is_numeric($args[0])||intval($args[0])<1){
                $player->sendMessage($this->plugin->msg("Wrong identifier. Please use vault numbers from 1 to ".$this->plugin->settings["max-vault-amount"]."!"));
                return true;
            }
            $vault = intval($args[0]);
            if($vault>$this->plugin->settings["max-vault-amount"]){
                $player->sendMessage($this->plugin->msg("You can only use up to ".$this->plugin->settings["max-vault-amount"]." vaults!"));
                return true;
            }
            
            //Find target Player etc..
            $tplayer = $this->plugin->getServer()->getPlayer($args[1]);
            if($tplayer){
                $targetPlayerId=$tplayer->getId();
            }
            else{
                $player->sendMessage($this->plugin->msg("Can't find player with that name!"));
                return true;
            }

        }
        else{
            $player->sendMessage($this->plugin->msg("Exactly 2 arguments required!"));
            return false;
        }

        //Creating Inventory
        $inventory = $this->inventorySpawner->getInventory($player,[$playerId,$vault,$targetPlayerId],"Vault #".$vault." as admin");
        //Get Item Array from DB File
        $content = $this->getVaultContents($targetPlayerId,$vault);
        //Add all Items to the Inventory
        while($itemRaw = $content->fetchArray(SQLITE3_ASSOC)){
            $newItem = ItemFactory::FromString($itemRaw["itemid"].":".$itemRaw["meta"]);
            $newItem->setCount($itemRaw["amount"]);
            if($itemRaw["nbt"]!=null){
                $newItem->setCompoundTag($itemRaw["nbt"]);
            }
            //Add Item
            $inventory->setItem($itemRaw["slot"],clone $newItem);
        }

        //Display Vault
        $player->addWindow($inventory);
        $player->sendMessage($this->plugin->msg("Open vault #".$vault." as admin"));

        return true;
    }*/

    private function clearVault(Player $player, int $targetId, int $vault):bool{
        $this->clearVaultContents($targetId,$vault);
        $player->sendMessage($this->plugin->msg("Cleared ".($player->getId()!=$targetId?"players":"your")." vault content!"));
        return true;
    }

    /*USELESS NOW: 
    private function clearVaultAsAdmin(Player $player, array $args):bool{
        
        $vault = 1;
        $playerId=$player->getId();
        $targetPlayerId=-1;
        if(count($args)==2){
            if(!is_numeric($args[0])||intval($args[0])<1){
                $player->sendMessage($this->plugin->msg("Wrong identifier. Please use vault numbers from 1 to ".$this->plugin->settings["max-vault-amount"]."!"));
                return true;
            }
            $vault = intval($args[0]);
            if($vault>$this->plugin->settings["max-vault-amount"]){
                $player->sendMessage($this->plugin->msg("You can only use up to ".$this->plugin->settings["max-vault-amount"]." vaults!"));
                return true;
            }

            $tplayer = $this->plugin->getServer()->getPlayer($args[1]);
            if($tplayer){
                $targetPlayerId=$tplayer->getId();
            }
            else{
                $player->sendMessage($this->plugin->msg("Can't find player with that name!"));
                return true;
            }
        }
        else{
            $player->sendMessage($this->plugin->msg("This command requires exactly 2 arguments!"));
            return false;
        }
        $this->clearVaultContents($targetPlayerId,$vault);
        $player->sendMessage($this->plugin->msg("Cleared the players vaults content!"));
        return true;
    }*/


    private function wipeVaults(Player $player,int $targetId){
        //TODO: REFACTOR 
        $this->wipePlayerVaults($targetId);
        $player->sendMessage($this->plugin->msg("Cleared all ".($player->getId()!=$targetId?" vaults of that player!":"your vaults!")));
        return true;
    }

    private function getHelpPage(Player $player) : bool {

        $output = "LeetVault 2.0.0-B1 Help:\n";
        if($this->hasRight($player,"pv.help",false))$output .= "/pv help - shows this help\n";

        if($this->hasRight($player,"pv.vault.use",false))$output .= "/pv [vaultno] - opens the specified vault - otherwiese the first one\n";

        if($this->hasRight($player,"pv.admin.edit",false)||$this->hasRight($player,"pv.admin.view",false))$output .= "/pva <vaultno> <playername> - opens the specified vault of the specified player\n";

        if($this->hasRight($player,"pv.vault.clear",false))$output .= "/pv clear [#vaultNo/all] - clears the specified vault - otherwise the first one\n";

        if($this->hasRight($player,"pv.admin.clear",false))$output .= "/pva clear <#vaultNo/all> <playername> - clears the specified vault of the specified player\n";

        if($this->hasRight($player,"pv.admin.setlimit",false))$output .= "/pva setlimit <limit> - sets the limit of vaults per player\n";
        $player->sendMessage($this->plugin->msg($output));
        return true;
    }

    private function setVaultLimit(Player $player, int $limit) : bool{
        $this->plugin->setLimit($limit);
        $player->sendMessage($this->plugin->msg("Vault limit has been changed to ".$this->plugin->settings["max-vault-amount"]));
        return true;
    }

    //All commands begin with /pv
    private function playerCommand(Player $player, array $args) : bool{
        $argCount = count($args);
        $playerId = $player->getId();
        if($argCount==0){
            if(!$this->hasRight($player,"pv.vault.use"))return true;
            //SHOW FIRST VAULT
            $this->showVault($player,$playerId,1);
            return true;
        }
        else{
            if(strtolower($args[0])=="clear"){
                if(!$this->hasRight($player,"pv.vault.clear"))return true;
                //STATE: IS CLEAR COMMAND
                if($argCount==2){
                    if(strtolower($args[1])=="all"){
                        $this->wipeVaults($player,$playerId);
                        return true;
                    }
                    else{
                        if(is_numeric($args[1])&&($intArg=intval($args[1]))>0){
                            //IF: check max amount
                            if($intArg>0&&$intArg<=$this->plugin->settings["max-vault-amount"]){
                                //OKay, clear vault #[$intArg]
                                $this->clearVault($player,$playerId,$intArg);
                                return true;
                            }
                            else{
                                //MAX VAULT EXCEED
                                $player->sendMessage($this->plugin->msg("You can only use up to ".$this->plugin->settings["max-vault-amount"]." vaults!"));
                                return true;
                            }
                        }
                        else{
                            //ERROR - Invalid Identifier!
                            $player->sendMessage($this->plugin->msg("Wrong command syntax! Type /pv help for more info!"));
                            return false;
                        }
                    }
                }
                else{
                    //CLEAR VAULT 1
                    $this->clearVault($player,$playerId,1);
                    return true;
                }
            }
            else if(strtolower($args[0])=="help"){
                if(!$this->hasRight($player,"pv.vault.help"))return true;
                //SHOW HELP
                $this->getHelpPage($player);
                return true;
            }
            else{
                if(!$this->hasRight($player,"pv.vault.use"))return true;
                if(is_numeric($args[0])&&($intArg=intval($args[0]))>0){
                    //IF: Test for limit
                    if($intArg>0&&$intArg<=$this->plugin->settings["max-vault-amount"]){
                        //OKay, show vault #[$intArg]
                        $this->showVault($player,$playerId,$intArg);
                        return true;
                    }
                    else{
                        //MAX VAULT EXCEED
                        $player->sendMessage($this->plugin->msg("You can only use up to ".$this->plugin->settings["max-vault-amount"]." vaults!"));
                        return true;
                    }
                }
                else{
                    //ERROR - Invalid Identifier!
                    $player->sendMessage($this->plugin->msg("Wrong command syntax! Type /pv help for more info!"));
                    return false;
                }
            }
        }
        return false;
    }

    //All Commands begin with /pva
    private function adminCommand(Player $player, array $args) : bool{

        $argCount = count($args);
        $playerId = $player->getId();
        if($argCount<1){
            //SHOW FIRST VAULT
            $player->sendMessage($this->plugin->msg("Wrong command syntax! Type /lv help for more info!"));
            return false;
        }
        else{
            if(strtolower($args[0])=="clear"){
                if(!$this->hasRight($player,"pv.admin.clear"))return true;
                //STATE: IS CLEAR COMMAND
                if($argCount==3){
                    $tPlayer = $this->plugin->getServer()->getPlayer($args[2]);
                    if($tPlayer){
                        $tPlayerId = $tPlayer->getId();
                        if(strtolower($args[1])=="all"){
                            $this->wipeVaults($player,$tPlayerId);
                            return true;
                        }
                        else{
                            if(is_numeric($args[1])&&($intArg=intval($args[1]))>0){
                                //IF: check max amount
                                if($intArg>0&&$intArg<=$this->plugin->settings["max-vault-amount"]){
                                    //OKay, clear vault #[$intArg]
                                    $this->clearVault($player,$tPlayerId,$intArg);
                                    return true;
                                }
                                else{
                                    //MAX VAULT EXCEED
                                    $player->sendMessage($this->plugin->msg("You can only use up to ".$this->plugin->settings["max-vault-amount"]." vaults!"));
                                    return true;
                                }
                            }
                            else{
                                //ERROR - Invalid Identifier!
                                $player->sendMessage($this->plugin->msg("Wrong command syntax! Type /pv help for more info!"));
                                return false;
                            }
                        }
                    }
                    else{
                        $player->sendMessage($this->plugin->msg("Can't find a player with that name!"));
                        return false;
                    }
                }
                else{
                    //ERROR NOT ENOUGH ARGUMENTS!
                    $player->sendMessage($this->plugin->msg("Wrong command syntax! Type /pv help for more info!"));
                    return false;
                }
            }
            //END CLEAR
            //JUST FOR COMPABILITY. NOT OfFICALLY DOCUMENTED!
            else if(strtolower($args[0])=="help"){
                if(!$this->hasRight($player,"pv.vault.help"))return true;
                //SHOW HELP
                $this->getHelpPage($player);
                return true;
            }
            //END HELP
            else if(strtolower($args[0])=="setlimit"){
                if(!$this->hasRight($player,"pv.admin.setlimit"))return true;
                //Set LIMIT COMMAND
                if($argCount==2){
                    if(is_numeric($args[1])&&($intArg=intval($args[1]))>0){
                        //change config
                        $this->setVaultLimit($player,$intArg);
                        return true;
                    }
                    else{
                        //Error not numeric
                        $player->sendMessage($this->plugin->msg("Wrong command syntax! Type /pv help for more info!"));
                        return false;
                    }
                }
                else{
                    //ERROR to few arguments
                    $player->sendMessage($this->plugin->msg("Wrong command syntax! Type /pv help for more info!"));
                    return false;
                }
            }
            //END SETLIMIT
            else{
                if(!($this->hasRight($player,"pv.admin.view")||$this->hasRight($player,"pv.admin.edit")))return true;
                if($argCount==2){
                    $tPlayer = $this->plugin->getServer()->getPlayer($args[1]);
                    if($tPlayer){
                        $tPlayerId = $tPlayer->getId();
                        //PLAYER FOUND proceed
                        if(is_numeric($args[0])&&($intArg=intval($args[0]))>0){
                            //IF: Test for limit
                            if($intArg>0&&$intArg<=$this->plugin->settings["max-vault-amount"]){
                                //OKay, show vault #[$intArg]
                                $this->showVault($player,$tPlayerId,$intArg);
                                return true;
                            }
                            else{
                                //MAX VAULT EXCEED
                                $player->sendMessage($this->plugin->msg("You can only use up to ".$this->plugin->settings["max-vault-amount"]." vaults!"));
                                return true;
                            }
                        }
                        else{
                            //ERROR - Invalid Identifier!
                            $player->sendMessage($this->plugin->msg("Wrong command syntax! Type /pv help for more info!"));
                            return false;
                        }

                    }
                    else{
                        //ERROR PLAYER NOT FOUND
                        $player->sendMessage($this->plugin->msg("Can't find a player with that name!"));
                        return false;
                    }
                }
                else{
                    //ERROR: INVALID COMMAND
                    $player->sendMessage($this->plugin->msg("Wrong command syntax! Type /pv help for more info!"));
                    return false;
                }
            }
        }
        return false;
    }

    private function hasRight(Player $player, string $permissionNode,bool $showMessage=true) : bool{
        $nodeList = [];
        $parts = explode(".",$permissionNode);
        $akt = "";
        for($i=0; $i<count($parts)-1;$i++){
            $akt .= $parts[$i].".";
            if($player->hasPermission($akt."*")){
                return true;
            }
        }
        if($player->hasPermission($permissionNode)||$player->hasPermission("*")||$player->isOp()){
            return true;
        }
        if($showMessage)$player->sendMessage($this->plugin->msg("You are not allowed to use this command!"));
        return false;
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if(!$sender instanceof Player){
            $this->plugin->getServer()->getLogger()->info("This command is only made for ingame use!");
            return true;
        }
    
        $commandStr = strtolower($command->getName());
        switch($commandStr){
            case "playervault":
            case "pv":
                return $this->playerCommand($sender,$args);
            case "playervaultadmin":
            case "pva":
                return $this->adminCommand($sender,$args);
        }
        return false;
    }

}
