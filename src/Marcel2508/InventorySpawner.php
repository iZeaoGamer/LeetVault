<?php

namespace Marcel2508;
use pocketmine\inventory\ChestInventory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\tile\Tile;
use pocketmine\tile\Chest;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\nbt\NBT;
use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\math\Vector3;
use pocketmine\level\Position;

class InventorySpawner {

    private $fakeChestTile=null;

    private function getFakeChestTile(Player $player,array $chestConfig,String $title) : Chest{
        $level = $player->getLevel();
        $pos = new Position(intval($player->x),intval($player->y)-2,intval($player->z),$level);
        $block = BlockFactory::get(54,0,$pos);
        //Fake Block for Player
        $level->sendBlocks([$player],[$block]);

        //CREATE Tile
        $pos = [$player->x,$player->y,$player->z];
        $nbt = new CompoundTag("FakeChest",[
            new ListTag("Items",[]),
            new StringTag("id",Tile::CHEST),
            new StringTag("CustomName",$title),
            //new StringTag("PlayerVault","FakeChest"),
            new CompoundTag("PlayerVault",[
                new IntTag("PlayerId",$chestConfig[0]),
                new IntTag("PlayerVaultNumber",$chestConfig[1]),
                new IntTag("AdminView",$chestConfig[2])
            ]),
            new IntTag("x",intval($pos[0])),
            new IntTag("y",intval($pos[1])-2),
            new IntTag("z",intval($pos[2])),
        ]);
        $nbt->Items->setTagType(NBT::TAG_Compound);
        $tile = Tile::createTile(Tile::CHEST,$level,$nbt);
        $tile->setName($title);//Don't work either... hmm..

        return $tile;
    }

    public function getInventory(Player $player,array $chestConfig = [0,0,-1],string $title="PlayerVault") : ChestInventory{
        $chest = $this->getFakeChestTile($player,$chestConfig,$title);
        //var_dump($chest->getInventory());
        if ($chest instanceof \pocketmine\tile\Chest) {}else{echo "ERROR Can't create Chest!\r\n";}
        return $chest->getInventory();
    }
}