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
    
    public function __construct(LeetVault $plugin) {
        $this->plugin = $plugin;
        $this->inventorySpawner = new InventorySpawner();
    }

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

    private function wipeAllVaults(){
        $query = "DELETE FROM `vaults`;";
        $res = $this->plugin->db->exec($query);
        return $res;
    }

    private function wipePlayerVaults($player){
        $query = "DELETE FROM `vaults` WHERE `player` = '".$player."';";
        $res = $this->plugin->db->exec($query);
        return $res;
    }

    private function showVault(Player $player, array $args) : bool{

        $vault = 1;
        $playerId=$player->getId();
        if(count($args)==1){
            if(!is_numeric($args[0])||intval($args[0])<1){
                $player->sendMessage($this->plugin->msg("Wrong identifier. Please use vault numbers from 1 to ".$this->plugin->settings["max-vault-amount"]."!"));
                return true;
            }
            $vault = intval($args[0]);
            if($vault>$this->plugin->settings["max-vault-amount"]){
                $player->sendMessage($this->plugin->msg("You can only use up to ".$this->plugin->settings["max-vault-amount"]." vaults!"));
                return true;
            }
        }

        //Creating Inventory
        $inventory = $this->inventorySpawner->getInventory($player,[$playerId,$vault,-1],"Vault #".$vault);
        //Get Item Array from DB File
        $content = $this->getVaultContents($playerId,$vault);
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
        $player->sendMessage($this->plugin->msg("Open vault #".$vault));

        return true;
    }

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
    }

    private function clearVault(Player $player, array $args):bool{

        $vault = 1;
        $playerId=$player->getId();
        if(count($args)==1){
            if(!is_numeric($args[0])||intval($args[0])<1){
                $player->sendMessage($this->plugin->msg("Wrong identifier. Please use vault numbers from 1 to ".$this->plugin->settings["max-vault-amount"]."!"));
                return true;
            }
            $vault = intval($args[0]);
            if($vault>$this->plugin->settings["max-vault-amount"]){
                $player->sendMessage($this->plugin->msg("You can only use up to ".$this->plugin->settings["max-vault-amount"]." vaults!"));
                return true;
            }
        }

        $this->clearVaultContents($playerId,$vault);
        $player->sendMessage($this->plugin->msg("Cleared your vaults content!"));
        return true;
    }

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
    }


    private function wipeVaults($sender,$args){
        $targetPlayerId=-1;
        if(count($args)==1){
            
            if($args[0]=="ALL"){
                $targetPlayerId=-2;
            }
            else{
                $tplayer = $this->plugin->getServer()->getPlayer($args[0]);
                if($tplayer){
                    $targetPlayerId=$tplayer->getId();
                }
                else{
                    $player->sendMessage($this->plugin->msg("Can't find player with that name!"));
                    return true;
                }
            }
        }
        else{
            $player->sendMessage($this->plugin->msg("This command requires exactly 1 argument!"));
            return false;
        }
        if($targetPlayerId>=0){
            $this->wipePlayerVaults($targetPlayerId);
        }
        else if($targetPlayerId===-2){
            $this->wipeAllVaults();
        }
        else{
            $player->sendMessage($this->plugin->msg("Use \"/leetvaultadminwipe ALL\" to wipe all data"));
            return false;
        }
        return true;
    }

    private function getHelpPage(Player $player) : bool {

        $output = "LeetVault 1.0 Help:\n";
        if($player->hasPermission("leetvault.help"))$output .= "/lvh - shows this help\n";
        if($player->hasPermission("leetvault.help")&&$player->hasPermission("leetvault.admin.use"))$output .= "  leetvault.help\n";

        if($player->hasPermission("leetvault.vault.use"))$output .= "/lv [vaultnr] - opens the specified vault\n";
        if($player->hasPermission("leetvault.vault.use")&&$player->hasPermission("leetvault.admin.use"))$output .= "  leetvault.vault.use\n";

        if($player->hasPermission("leetvault.admin.use"))$output .= "/lva <vaultnr> <playername> - opens the specified vault of the specified player\n";
        if($player->hasPermission("leetvault.admin.use"))$output .= "  leetvault.admin.use\n";

        if($player->hasPermission("leetvault.vault.clear"))$output .= "/lvc [vaultnr] - clears the specified vault\n";
        if($player->hasPermission("leetvault.admin.use")&&$player->hasPermission("leetvault.vault.clear"))$output .= "  leetvault.vault.clear\n";

        if($player->hasPermission("leetvault.admin.clear"))$output .= "/lvac <vaultnr> <playername> - clears the specified vault of the specified player\n";
        if($player->hasPermission("leetvault.admin.clear"))$output .= "  leetvault.admin.clear\n";

        if($player->hasPermission("leetvault.admin.wipe"))$output .= "/lvac <playername/ALL> - Wipes the players / all players vaults\n";
        if($player->hasPermission("leetvault.admin.wipe"))$output .= "  leetvault.admin.wipe\n";

        if($player->hasPermission("leetvault.admin.setlimit"))$output .= "/leetvaultadminsetlimit <limit> - sets the limit of vaults per player\n";
        if($player->hasPermission("leetvault.admin.setlimit"))$output .= "  leetvault.admin.setlimit\n";
        $player->sendMessage($this->plugin->msg($output));
        return true;
    }

    private function setVaultLimit(Player $player, array $args) : bool{
        if(count($args)==1){
            
            if(!is_numeric($args[0])||intval($args[0])<1){
                $player->sendMessage($this->plugin->msg("Wrong identifier. Please use a valid number between 1 and your maximum!"));
                return true;
            }
            else{
                $this->plugin->setLimit(intval($args[0]));
                $player->sendMessage($this->plugin->msg("Vault limit has been changed to ".$this->plugin->settings["max-vault-amount"]));
                return true;
            }
        }
        else{
            $player->sendMessage($this->plugin->msg("This command needs exactly 1 parameter!"));
            return false;
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if(!$sender instanceof Player){
            $this->plugin->getServer()->getLogger()->info("This command is only for ingame use!");
            return true;
        }
        switch(strtolower($command->getName())){
            case "leetvault":
            case "lv":
                if($sender->getGamemode()==1&&$sender->hasPermission("limitedcreative.permission.creative")){
                    $sender->sendMessage($this->plugin->msg("LimitedCreative disallows usage of vaults while in creative mode!"));
                    return true;
                }
                else{
                    if($sender->hasPermission("leetvault.vault.use"))
                        return $this->showVault($sender,$args);
                    else{
                        $sender->sendMessage($this->plugin->msg("You're not allowed to use this command!"));
                        return true;
                    }
                }
            case "leetvaultadmin":
            case "lva":
                if($sender->hasPermission("leetvault.admin.use"))
                    return $this->showVaultAsAdmin($sender,$args);
                else{
                    $sender->sendMessage($this->plugin->msg("You're not allowed to use this command!"));
                    return true;
                }
            case "leetvaultclear":
            case "lvc":
                if($sender->hasPermission("leetvault.vault.clear"))
                    return $this->clearVault($sender,$args);
                else{
                    $sender->sendMessage($this->plugin->msg("You're not allowed to use this command!"));
                    return true;
                }
            case "leetvaultadminclear":
            case "lvac":
                if($sender->hasPermission("leetvault.admin.clear"))
                    return $this->clearVaultAsAdmin($sender,$args);
                else{
                    $sender->sendMessage($this->plugin->msg("You're not allowed to use this command!"));
                    return true;
                }
            case "leetvaultadminwipe":
                if($sender->hasPermission("leetvault.admin.wipe"))
                    return $this->wipeVaults($sender,$args);
                else{
                    $sender->sendMessage($this->plugin->msg("You're not allowed to use this command!"));
                    return true;
                }
            case "lvh":
            case "leetvaulthelp":
                if($sender->hasPermission("leetvault.help"))
                    return $this->getHelpPage($sender);
                else{
                    $sender->sendMessage($this->plugin->msg("You're not allowed to use this command!"));
                    return true;
                }
            case "leetvaultadminsetlimit":
                if($sender->hasPermission("leetvault.admin.setlimit"))
                    return $this->setVaultLimit($sender,$args);
                else{
                    $sender->sendMessage($this->plugin->msg("You're not allowed to use this command!"));
                    return true;
                }
        }
        return false;
    }

}