<?php

namespace xenialdan\BedWars;

use muqsit\invmenu\inventories\BaseFakeInventory;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\block\BlockIds;
use pocketmine\entity\Entity;
use pocketmine\entity\object\ItemEntity;
use pocketmine\entity\object\PrimedTNT;
use pocketmine\entity\projectile\Arrow;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\Potion;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use xenialdan\BedWars\commands\BedwarsCommand;
use xenialdan\BedWars\entity\ShopVillager;
use xenialdan\BedWars\task\SpawnItemsTask;
use xenialdan\customui\elements\Button;
use xenialdan\customui\elements\Input;
use xenialdan\customui\elements\Label;
use xenialdan\customui\elements\StepSlider;
use xenialdan\customui\windows\CustomForm;
use xenialdan\customui\windows\ModalForm;
use xenialdan\customui\windows\SimpleForm;
use xenialdan\gameapi\API;
use xenialdan\gameapi\Arena;
use xenialdan\gameapi\Game;
use xenialdan\gameapi\Team;

class Loader extends Game
{
    const BRONZE = "Bronze";
    const SILVER = "Silver";
    const GOLD = "Gold";

    /** @var Loader */
    private static $instance = null;

    /**
     * Returns an instance of the plugin
     * @return Loader
     */
    public static function getInstance()
    {
        return self::$instance;
    }

    public function onLoad()
    {
        self::$instance = $this;
    }

    public function onEnable()
    {
        if (!InvMenuHandler::isRegistered()) InvMenuHandler::register($this);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new JoinGameListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new LeaveGameListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new SetupEventListener(), $this);
        Entity::registerEntity(ShopVillager::class, true, ['BWShop', 'bedwars:shop']);
        $this->getServer()->getCommandMap()->register("XBedWars", new BedwarsCommand($this));
        /** @noinspection PhpUnhandledExceptionInspection */
        API::registerGame($this);
        foreach (glob($this->getDataFolder() . "*.json") as $v) {
            $this->addArena($this->getNewArena($v));
        }
    }

    public function getNewArena(string $settingsPath): Arena
    {
        $settings = new BedwarsSettings($settingsPath);
        var_dump($settings->gold, $settings->get("gold"));
        $levelname = basename($settingsPath, ".json");
        $arena = new Arena($levelname, $this, $settings);
        var_dump($settings->teams, $settings->get("teams"));
        foreach ($settings->get("teams", []) as $teamname => $teaminfo) {
            $team = new BedwarsTeam($teaminfo["color"] ?? TextFormat::RESET, $teamname);
            $team->setMinPlayers(1);
            $team->setMaxPlayers($teaminfo["maxplayers"] ?? 1);
            #if (!is_null($teaminfo["spawn"]))
            $team->setSpawn(new Vector3(
                    $teaminfo["spawn"]["x"] ?? $arena->getLevel()->getSpawnLocation()->getFloorX(),
                    $teaminfo["spawn"]["y"] ?? $arena->getLevel()->getSpawnLocation()->getFloorY(),
                    $teaminfo["spawn"]["z"] ?? $arena->getLevel()->getSpawnLocation()->getFloorZ()
                )
            );
            var_dump($team);
            $arena->addTeam($team);
        }
        return $arena;
    }

    private static function getTeamNamesByAmount(int $amount): array
    {
        $teams = [
            TextFormat::RED => "Red",
            TextFormat::DARK_BLUE => "Blue",
            TextFormat::GREEN => "Green",
            TextFormat::YELLOW => "Yellow",
            TextFormat::DARK_PURPLE => "Purple",
            TextFormat::GOLD => "Orange",
            TextFormat::LIGHT_PURPLE => "Pink",
            TextFormat::DARK_AQUA => "Cyan",
        ];
        return array_slice($teams, 0, $amount, true);
    }

    public function setupArena(Player $player): void
    {
        $form = new SimpleForm("Bedwars arena setup");
        $na = "New arena";
        $form->addButton(new Button($na));
        $ea = "Edit arena";
        $form->addButton(new Button($ea));
        $form->setCallable(function (Player $player, $data) use ($na, $ea) {
            if ($data === $na) {
                $form = new SimpleForm("Bedwars arena setup", "New arena via");
                $nw = "New world";
                $form->addButton(new Button($nw));
                $ew = "Existing world";
                $form->addButton(new Button($ew));
                $form->setCallable(function (Player $player, $data) use ($ew, $nw) {
                    $new = true;
                    if ($data === $ew) {
                        $new = false;
                        $form = new SimpleForm("Bedwars arena setup", "New arena from $data");
                        foreach (API::getAllWorlds() as $worldName) {
                            $form->addButton(new Button($worldName));
                        }
                    } else {
                        $form = new CustomForm("Bedwars arena setup");
                        $form->addElement(new Label("New arena from $data"));
                        $form->addElement(new Input("World name", "Example: bw4x1"));
                    }
                    $form->setCallable(function (Player $player, $data) use ($new) {
                        $setup["name"] = $new ? $data[1] : $data;
                        if ($new) {
                            API::$generator->generateLevel($setup["name"]);
                        }
                        Server::getInstance()->loadLevel($setup["name"]);
                        $form = new CustomForm("Bedwars teams setup");
                        $form->addElement(new StepSlider("Teams", array_keys(array_fill(2, 7, ""))));
                        $form->addElement(new StepSlider("Maximum players per team", array_keys(array_fill(1, 5, ""))));
                        $form->setCallable(function (Player $player, $data) use ($new, $setup) {
                            $setup["teamcount"] = intval($data[0]);
                            $setup["maxplayers"] = intval($data[1]);
                            $teams = self::getTeamNamesByAmount($setup["teamcount"]);
                            //New arena
                            $settings = new BedwarsSettings($this->getDataFolder() . $setup["name"] . ".json");
                            foreach ($teams as $color => $name) {
                                $settings->teams[$name] = ["color" => $color, "maxplayers" => $setup["maxplayers"]];
                            }
                            $settings->save();
                            $this->addArena($this->getNewArena($this->getDataFolder() . $setup["name"] . ".json"));
                            //Messages
                            $player->sendMessage(TextFormat::GOLD . TextFormat::BOLD . "Done! Bedwars arena was set up with following settings:");
                            $player->sendMessage(TextFormat::AQUA . "World name: " . TextFormat::DARK_AQUA . $setup["name"]);
                            $message = TextFormat::AQUA . "Teams: " . TextFormat::LIGHT_PURPLE . $setup["teamcount"];
                            $message .= TextFormat::RESET . "(";
                            $tc = [];
                            foreach ($teams as $color => $name) $tc[] = $color . ucfirst($name);
                            $message .= implode(TextFormat::RESET . ", ", $tc);
                            $message .= TextFormat::RESET . ")";
                            $player->sendMessage($message);
                            $player->sendMessage(TextFormat::AQUA . "Maximum players per team: " . TextFormat::DARK_AQUA . $setup["maxplayers"]);
                            $player->sendMessage(TextFormat::GOLD . "Use \"/bw setup\" to set the team and item spawn points");
                        });
                        $player->sendForm($form);
                    });
                    $player->sendForm($form);
                });
                $player->sendForm($form);
            } elseif ($data === $ea) {
                $form = new SimpleForm("Edit Bedwars arena");
                $build = "Build / Edit item spawners";
                $button = new Button($build);
                $button->addImage(Button::IMAGE_TYPE_PATH, "textures/ui/icon_recipe_construction");
                $form->addButton($button);
                $editspawnpoints = "Edit team spawn points";
                $button = new Button($editspawnpoints);
                $button->addImage(Button::IMAGE_TYPE_PATH, "textures/items/bed_red");
                $form->addButton($button);
                $addvillager = "Add Villager (Shop)";
                $button = new Button($addvillager);
                $button->addImage(Button::IMAGE_TYPE_PATH, "textures/items/emerald");
                $form->addButton($button);
                $delete = "Delete arena";
                $button = new Button($delete);
                $button->addImage(Button::IMAGE_TYPE_PATH, "textures/ui/trash");
                $form->addButton($button);
                $form->setCallable(function (Player $player, $data) use ($addvillager, $editspawnpoints, $delete, $build) {
                    switch ($data) {
                        case $build:
                            {
                                $form = new SimpleForm($build, "Select the arena you'd like to build in");
                                foreach ($this->getArenas() as $arena) $form->addButton(new Button($arena->getLevelName()));
                                $form->setCallable(function (Player $player, $data) {
                                    $worldname = $data;
                                    $arena = API::getArenaByLevelName($this, $worldname);
                                    $this->getServer()->broadcastMessage("Stopping arena, reason: Admin actions", $arena->getPlayers());
                                    $arena->stopArena();
                                    $arena->setState(Arena::SETUP);
                                    if (!$this->getServer()->isLevelLoaded($worldname)) $this->getServer()->loadLevel($worldname);
                                    $player->teleport($arena->getLevel()->getSpawnLocation());
                                    $player->setGamemode(Player::CREATIVE);
                                    $player->setAllowFlight(true);
                                    $player->setFlying(true);
                                    $player->getInventory()->clearAll();
                                    $arena->getLevel()->stopTime();
                                    $arena->getLevel()->setTime(Level::TIME_DAY);
                                    $player->sendMessage(TextFormat::GOLD . "You may now freely edit the arena.");
                                    $player->sendMessage(TextFormat::GOLD . "Tap or right click gold blocks, iron blocks or uncolored terracotta blocks to activate the blocks as item droppers for gold, silver and bronze. Break the blocks to remove them");
                                });
                                $player->sendForm($form);
                                break;
                            }
                        case $editspawnpoints:
                            {
                                $form = new SimpleForm($editspawnpoints, "Select the arena you'd like to edit the spawn points of");
                                foreach ($this->getArenas() as $arena) $form->addButton(new Button($arena->getLevelName()));
                                $form->setCallable(function (Player $player, $data) {
                                    $worldname = $data;
                                    $arena = API::getArenaByLevelName($this, $worldname);
                                    $this->getServer()->broadcastMessage("Stopping arena, reason: Admin actions", $arena->getPlayers());
                                    $arena->stopArena();
                                    $arena->setState(Arena::SETUP);
                                    if (!$this->getServer()->isLevelLoaded($worldname)) $this->getServer()->loadLevel($worldname);
                                    $player->teleport($arena->getLevel()->getSpawnLocation());
                                    $player->setGamemode(Player::SURVIVAL);
                                    $player->setAllowFlight(true);
                                    $player->setFlying(true);
                                    $player->getInventory()->clearAll();
                                    $arena->getLevel()->stopTime();
                                    $arena->getLevel()->setTime(Level::TIME_DAY);
                                    foreach ($arena->getTeams() as $team) {
                                        $item = ItemFactory::get(Item::CONCRETE, API::getMetaByColor($team->getColor()));
                                        $item->setLore(["Spawn point for the " . $team->getColor() . $team->getName() . TextFormat::RESET . " team", "Place to set the spawn point for this team"]);
                                        $item->setCustomName($team->getColor() . $team->getName());
                                        $player->getInventory()->addItem($item);
                                    }
                                    $player->sendMessage(TextFormat::GOLD . "Place the concrete blocks to set the team spawn points");
                                });
                                $player->sendForm($form);
                                break;
                            }
                        case $addvillager:
                            {
                                $form = new SimpleForm($editspawnpoints, "Select the arena you'd like to add a villager shop in");
                                foreach ($this->getArenas() as $arena) $form->addButton(new Button($arena->getLevelName()));
                                $form->setCallable(function (Player $player, $data) {
                                    $worldname = $data;
                                    $arena = API::getArenaByLevelName($this, $worldname);
                                    $this->getServer()->broadcastMessage("Stopping arena, reason: Admin actions", $arena->getPlayers());
                                    $arena->stopArena();
                                    $arena->setState(Arena::SETUP);
                                    if (!$this->getServer()->isLevelLoaded($worldname)) $this->getServer()->loadLevel($worldname);
                                    $player->teleport($arena->getLevel()->getSpawnLocation());
                                    $player->setGamemode(Player::SURVIVAL);
                                    $player->setAllowFlight(true);
                                    $player->setFlying(true);
                                    $player->getInventory()->clearAll();
                                    $arena->getLevel()->stopTime();
                                    $arena->getLevel()->setTime(Level::TIME_DAY);
                                    $item = ItemFactory::get(Item::SPAWN_EGG, Entity::VILLAGER, 64);
                                    $item->setLore(["Use to spawn a villager shop", "Sneak and hit a villager to remove it", "Hit a villager to rotate him 45 degrees"]);
                                    $item->setCustomName(TextFormat::GOLD . TextFormat::BOLD . "Shop");
                                    $player->getInventory()->addItem($item);
                                    $player->sendMessage(TextFormat::GOLD . "Use the spawn egg to add a villager. Sneak and hit a villager to remove it. Hit a villager to rotate him 45 degrees");
                                });
                                $player->sendForm($form);
                                break;
                            }
                        case $delete:
                            {
                                $form = new SimpleForm("Delete Bedwars arena", "Select an arena to remove. The world will NOT be deleted");
                                foreach ($this->getArenas() as $arena) $form->addButton(new Button($arena->getLevelName()));
                                $form->setCallable(function (Player $player, $data) {
                                    $worldname = $data;
                                    $form = new ModalForm("Confirm delete", "Please confirm that you want to delete the arena \"$worldname\"", "Delete $worldname", "Abort");
                                    $form->setCallable(function (Player $player, $data) use ($worldname) {
                                        if ($data) {
                                            $arena = API::getArenaByLevelName($this, $worldname);
                                            $this->deleteArena($arena) ? $player->sendMessage(TextFormat::GREEN . "Successfully deleted the arena") : $player->sendMessage(TextFormat::RED . "Removed the arena, but config file could not be deleted!");
                                        }
                                    });
                                    $player->sendForm($form);
                                });
                                $player->sendForm($form);
                                break;
                            }
                    }
                });
                $player->sendForm($form);
            }
        });
        $player->sendForm($form);
    }

    /**
     * @param Arena $arena
     * @param Player $player
     */
    public function removePlayer(Arena $arena, Player $player)
    {
        $arena->bossbar->setTitle(count(array_filter($arena->getTeams(), function (Team $team): bool {
                return count($team->getPlayers()) > 0;
            })) . ' teams alive');
    }

    /**
     * @param Arena $arena
     */
    public function startArena(Arena $arena): void
    {
        /** @var BedwarsTeam $team */
        foreach ($arena->getTeams() as $team) {
            $team->setBedDestroyed(false);
            foreach ($team->getPlayers() as $player) {
                $player->setSpawn(Position::fromObject($team->getSpawn(), $arena->getLevel()));
                $player->teleport($player->getSpawn());
            }
        }

        $arena->bossbar->setSubTitle()->setTitle(count(array_filter($arena->getTeams(), function (BedwarsTeam $team): bool {
                return count($team->getPlayers()) > 0;
            })) . ' teams alive')->setPercentage(1);

        $this->getScheduler()->scheduleDelayedRepeatingTask(new SpawnItemsTask($arena), 100, 1);
    }

    /**
     * @param Arena $arena
     */
    public function stopArena(Arena $arena): void
    {
    }

    public function spawnBronze(Arena $arena)
    {
        /** @var BedwarsSettings $settings */
        $settings = $arena->getSettings();
        foreach ($settings->bronze ?? [] as $i => $spawn) {
            if ($arena->getLevel()->getBlockIdAt($spawn["x"], $spawn["y"], $spawn["z"]) !== BlockIds::HARDENED_CLAY) {
                $s = $settings->bronze;
                unset($s[$i]);
                $settings->bronze = $s;
                $settings->save();
                $this->getLogger()->debug("Removed bronze item spawner at [" . (join(", ", $spawn) . "] due to no bronze block existing at this position"));
                continue;
            }
            $v = new Vector3($spawn["x"] + 0.5, $spawn["y"] + 1, $spawn["z"] + 0.5);
            if (!$arena->getLevel()->isChunkLoaded($v->x >> 4, $v->z >> 4)) $arena->getLevel()->loadChunk($v->x >> 4, $v->z >> 4);
            //Stack items if too many
            if (count($arena->getLevel()->getChunkEntities($v->x >> 4, $v->z >> 4)) >= 50) {
                /** @var ItemEntity|null $last */
                $last = null;
                foreach ($arena->getLevel()->getChunkEntities($v->x >> 4, $v->z >> 4) as $chunkEntity) {
                    if (!$chunkEntity instanceof ItemEntity) continue;
                    if ($chunkEntity->getItem()->getId() === ItemIds::BRICK) {
                        if ($last === null || $last->getItem()->getCount() >= 64) {
                            $last = $chunkEntity;
                            continue;
                        }
                        $last->getItem()->setCount($last->getItem()->getCount() + $chunkEntity->getItem()->getCount());
                        $chunkEntity->close();
                        $last->respawnToAll();
                    }
                }
                if ($last instanceof ItemEntity) {
                    $last->getLevel()->broadcastLevelEvent($last, LevelEventPacket::EVENT_PARTICLE_EYE_DESPAWN);
                    $last->getLevel()->broadcastLevelEvent($last, LevelEventPacket::EVENT_PARTICLE_EYE_DESPAWN);
                    $last->getLevel()->broadcastLevelEvent($last, LevelEventPacket::EVENT_PARTICLE_EYE_DESPAWN);
                }
            }

            $arena->getLevel()->dropItem($v, (new Item(ItemIds::BRICK))->setCount(2)->setCustomName(TextFormat::GOLD . "Bronze"));
            $arena->getLevel()->broadcastLevelSoundEvent($v, LevelSoundEventPacket::SOUND_DROP_SLOT);
        }
    }

    public function spawnSilver(Arena $arena)
    {
        /** @var BedwarsSettings $settings */
        $settings = $arena->getSettings();
        foreach ($settings->silver ?? [] as $i => $spawn) {
            if ($arena->getLevel()->getBlockIdAt($spawn["x"], $spawn["y"], $spawn["z"]) !== BlockIds::IRON_BLOCK) {
                $s = $settings->silver;
                unset($s[$i]);
                $settings->set("silver", $s);
                $settings->save();
                $settings->reload();
                $this->getLogger()->debug("Removed silver item spawner at [" . (join(", ", $spawn) . "] due to no iron block existing at this position"));
                continue;
            }
            $v = new Vector3($spawn["x"] + 0.5, $spawn["y"] + 1, $spawn["z"] + 0.5);
            if (!$arena->getLevel()->isChunkLoaded($v->x >> 4, $v->z >> 4)) $arena->getLevel()->loadChunk($v->x >> 4, $v->z >> 4);
            $arena->getLevel()->dropItem($v, (new Item(ItemIds::IRON_INGOT))->setCustomName(TextFormat::GRAY . "Silver"));
            $arena->getLevel()->broadcastLevelSoundEvent($v, LevelSoundEventPacket::SOUND_DROP_SLOT);
        }
    }

    public function spawnGold(Arena $arena)
    {
        /** @var BedwarsSettings $settings */
        $settings = $arena->getSettings();
        foreach ($settings->gold ?? [] as $i => $spawn) {
            if ($arena->getLevel()->getBlockIdAt($spawn["x"], $spawn["y"], $spawn["z"]) !== BlockIds::GOLD_BLOCK) {
                $s = $settings->gold;
                unset($s[$i]);
                $settings->gold = $s;
                $settings->save();
                $this->getLogger()->debug("Removed gold item spawner at [" . (join(", ", $spawn) . "] due to no gold block existing at this position"));
                continue;
            }
            $v = new Vector3($spawn["x"] + 0.5, $spawn["y"] + 1, $spawn["z"] + 0.5);
            if (!$arena->getLevel()->isChunkLoaded($v->x >> 4, $v->z >> 4)) $arena->getLevel()->loadChunk($v->x >> 4, $v->z >> 4);
            $arena->getLevel()->dropItem($v, (new Item(ItemIds::GOLD_INGOT))->setCustomName(TextFormat::YELLOW . "Gold"));
            $arena->getLevel()->broadcastLevelSoundEvent($v, LevelSoundEventPacket::SOUND_DROP_SLOT);
        }
    }

    /**
     * Called right when a player joins a game in an arena. Used to set up players
     * @param Player $player
     */
    public function onPlayerJoinTeam(Player $player): void
    {
        $player->setSpawn(Position::fromObject(API::getTeamOfPlayer($player)->getSpawn(), API::getArenaOfPlayer($player)->getLevel()));
        //Team color switching
        $player->getInventory()->addItem(Item::get(ItemIds::BED, API::getMetaByColor(API::getTeamOfPlayer($player)->getColor()))->setCustomName("Switch Team"));
    }

    /**
     * Callback function for @see array_filter
     * If return value is true, this entity will be deleted.
     * @param Entity $entity
     * @return bool
     */
    public function removeEntityOnArenaReset(Entity $entity): bool
    {
        return $entity instanceof ItemEntity || $entity instanceof PrimedTNT || $entity instanceof Arrow;
    }

    public function openShop(Player $player)
    {
        $menu = InvMenu::create(InvMenu::TYPE_CHEST)->setName(TextFormat::RED . "Bed" . TextFormat::WHITE . "Wars " . TextFormat::RESET . "Shop")->readonly();
        $menu->getInventory()->setContents([
            Item::get(ItemIds::CHAIN_CHESTPLATE)->setCustomName("Armor"),
            Item::get(ItemIds::SANDSTONE)->setCustomName("Blocks"),
            Item::get(ItemIds::STONE_PICKAXE)->setCustomName("Pickaxes"),
            Item::get(ItemIds::STONE_SWORD)->setCustomName("Weapons"),
            Item::get(ItemIds::BOW)->setCustomName("Bows"),
            Item::get(ItemIds::POTION)->setCustomName("Other")
        ]);
        $menu->setListener(function (Player $player, Item $clicked, Item $clickedWith, SlotChangeAction $action): bool {
            switch ($clicked->getId()) {
                case ItemIds::CHAIN_CHESTPLATE:
                    $this->openShopArmor($player);
                    break;
                case ItemIds::SANDSTONE:
                    $this->openShopBlock($player);
                    break;
                case ItemIds::STONE_PICKAXE:
                    $this->openShopPickaxe($player);
                    break;
                case ItemIds::STONE_SWORD:
                    $this->openShopWeapons($player);
                    break;
                case ItemIds::BOW:
                    $this->openShopBow($player);
                    break;
                case ItemIds::POTION:
                    $this->openShopSpecial($player);
                    break;
            }
            return true;
        });
        $menu->send($player);
    }

    private function openShopArmor(Player $player)
    {
        $menu = InvMenu::create(InvMenu::TYPE_CHEST)->setName("Armor shop")->readonly();
        $menu->setInventoryCloseListener($this->subToMainShop());
        //enchanted and colored items
        $lc = $this->generateShopItem(Item::get(ItemIds::LEATHER_CAP), 1, 2 * 1, self::BRONZE);
        $lc = API::setCustomColor($lc, API::colorFromTextFormat(API::getTeamOfPlayer($player)->getColor()));
        $lp = $this->generateShopItem(Item::get(ItemIds::LEATHER_PANTS), 1, 2 * 1, self::BRONZE);
        $lp = API::setCustomColor($lp, API::colorFromTextFormat(API::getTeamOfPlayer($player)->getColor()));
        $lb = $this->generateShopItem(Item::get(ItemIds::LEATHER_BOOTS), 1, 2 * 1, self::BRONZE);
        $lb = API::setCustomColor($lb, API::colorFromTextFormat(API::getTeamOfPlayer($player)->getColor()));
        $c1 = $this->generateShopItem(Item::get(ItemIds::CHAIN_CHESTPLATE), 1, 2 * 1, self::SILVER);
        $c1->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION)));
        $c1->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING)));
        $c2 = $this->generateShopItem(Item::get(ItemIds::CHAIN_CHESTPLATE), 1, 4 * 1, self::SILVER);
        $c2->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION), 2));
        $c2->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING)));

        $menu->getInventory()->setContents([
            $lc,
            $this->generateShopItem(Item::get(ItemIds::CHAIN_CHESTPLATE), 1, 1 * 1, self::SILVER),
            $lp,
            $lb,
            $c1,
            $c2
        ]);
        $menu->setListener(function (Player $player, Item $clicked, Item $clickedWith, SlotChangeAction $action): bool {
            $this->buyItem($clicked, $player);
            return true;
        });
        $menu->send($player);
    }

    private function openShopBlock(Player $player)
    {
        $menu = InvMenu::create(InvMenu::TYPE_CHEST)->setName("Block shop")->readonly();
        $menu->setInventoryCloseListener($this->subToMainShop());
        $menu->getInventory()->setContents([
            $this->generateShopItem(Item::get(ItemIds::SANDSTONE), 4, 0.5 * 4, self::BRONZE),
            $this->generateShopItem(Item::get(ItemIds::SANDSTONE), 16, 0.5 * 16, self::BRONZE),
            $this->generateShopItem(Item::get(ItemIds::SANDSTONE), 32, 0.5 * 32, self::BRONZE),
            $this->generateShopItem(Item::get(ItemIds::SANDSTONE), 64, 0.5 * 64, self::BRONZE),
            $this->generateShopItem(Item::get(ItemIds::END_STONE), 1, 8 * 1, self::BRONZE),
            $this->generateShopItem(Item::get(ItemIds::END_STONE), 4, 8 * 4, self::BRONZE),
            $this->generateShopItem(Item::get(ItemIds::END_STONE), 16, 8 * 16, self::BRONZE),
            $this->generateShopItem(Item::get(ItemIds::END_STONE), 32, 8 * 32, self::BRONZE)
        ]);
        $menu->setListener(function (Player $player, Item $clicked, Item $clickedWith, SlotChangeAction $action): bool {
            $this->buyItem($clicked, $player);
            return true;
        });
        $menu->send($player);
    }

    private function openShopPickaxe(Player $player)
    {
        $menu = InvMenu::create(InvMenu::TYPE_CHEST)->setName("Pickaxe shop")->readonly();
        $menu->setInventoryCloseListener($this->subToMainShop());
        //enchanted items
        $ipe1 = $this->generateShopItem(Item::get(ItemIds::IRON_PICKAXE), 1, 8, self::SILVER);
        $ipe1->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY)));
        $gpe2 = $this->generateShopItem(Item::get(ItemIds::GOLD_PICKAXE), 1, 4, self::GOLD);
        $gpe2->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 2));

        $menu->getInventory()->setContents([
            $this->generateShopItem(Item::get(ItemIds::STONE_PICKAXE), 1, 16, self::BRONZE),
            $this->generateShopItem(Item::get(ItemIds::IRON_PICKAXE), 1, 4, self::SILVER),
            $ipe1,
            $gpe2
        ]);
        $menu->setListener(function (Player $player, Item $clicked, Item $clickedWith, SlotChangeAction $action): bool {
            $this->buyItem($clicked, $player);
            return true;
        });
        $menu->send($player);
    }

    private function openShopWeapons(Player $player)
    {
        $menu = InvMenu::create(InvMenu::TYPE_CHEST)->setName("Weapon shop")->readonly();
        $menu->setInventoryCloseListener($this->subToMainShop());
        //enchanted items
        $kbs = $this->generateShopItem(Item::get(ItemIds::STICK), 1, 8, self::BRONZE);
        $kbs->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::KNOCKBACK)));

        $gs1 = $this->generateShopItem(Item::get(ItemIds::GOLD_SWORD), 1, 2, self::SILVER);
        $gs1->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING)));

        $gs2 = $this->generateShopItem(Item::get(ItemIds::GOLD_SWORD), 1, 4, self::SILVER);
        $gs2->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING)));
        $gs2->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::SHARPNESS)));;

        $gs3 = $this->generateShopItem(Item::get(ItemIds::GOLD_SWORD), 1, 8, self::SILVER);
        $gs3->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING)));
        $gs3->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::SHARPNESS), 2));;

        $is1 = $this->generateShopItem(Item::get(ItemIds::IRON_SWORD), 1, 4, self::GOLD);
        $is1->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING)));
        $is1->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::SHARPNESS)));

        $menu->getInventory()->setContents([
            $kbs,
            $gs1,
            $gs2,
            $gs3,
            $is1
        ]);
        $menu->setListener(function (Player $player, Item $clicked, Item $clickedWith, SlotChangeAction $action): bool {
            $this->buyItem($clicked, $player);
            return true;
        });
        $menu->send($player);
    }

    private function openShopBow(Player $player)
    {
        $menu = InvMenu::create(InvMenu::TYPE_CHEST)->setName("Bow shop")->readonly();
        $menu->setInventoryCloseListener($this->subToMainShop());
        //enchanted items
        $b1 = $this->generateShopItem(Item::get(ItemIds::BOW), 1, 4, self::GOLD);
        $b1->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING)));

        $b2 = $this->generateShopItem(Item::get(ItemIds::BOW), 1, 8, self::GOLD);
        $b2->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING)));
        $b2->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::POWER)));

        $b3 = $this->generateShopItem(Item::get(ItemIds::BOW), 1, 16, self::GOLD);
        $b3->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING)));
        $b3->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::INFINITY)));

        $menu->getInventory()->setContents([
            $b1,
            $b2,
            $b3,
            $this->generateShopItem(Item::get(ItemIds::ARROW), 4, 0.25 * 4, self::SILVER),
            $this->generateShopItem(Item::get(ItemIds::ARROW), 8, 0.25 * 8, self::SILVER),
            $this->generateShopItem(Item::get(ItemIds::ARROW), 16, 0.25 * 16, self::SILVER)
        ]);
        $menu->setListener(function (Player $player, Item $clicked, Item $clickedWith, SlotChangeAction $action): bool {
            $this->buyItem($clicked, $player);
            return true;
        });
        $menu->send($player);
    }

    private function openShopSpecial(Player $player)
    {
        $menu = InvMenu::create(InvMenu::TYPE_CHEST)->setName("Special shop")->readonly();
        $menu->setInventoryCloseListener($this->subToMainShop());
        $menu->getInventory()->setContents([
            $this->generateShopItem(Item::get(ItemIds::ENDER_PEARL), 1, 4 * 1, self::GOLD),
            $this->generateShopItem(Item::get(ItemIds::TNT), 1, 16 * 1, self::SILVER),
            $this->generateShopItem(Item::get(ItemIds::POTION, Potion::STRONG_SWIFTNESS), 1, 4 * 1, self::SILVER),
            $this->generateShopItem(Item::get(ItemIds::POTION, Potion::STRONG_STRENGTH), 1, 2 * 1, self::GOLD),
            $this->generateShopItem(Item::get(ItemIds::SPLASH_POTION, Potion::SLOWNESS), 1, 4 * 1, self::SILVER),
            $this->generateShopItem(Item::get(ItemIds::SPLASH_POTION, Potion::WEAKNESS), 1, 2 * 1, self::GOLD),
            $this->generateShopItem(Item::get(ItemIds::SPLASH_POTION, Potion::POISON), 1, 2 * 1, self::GOLD),
            $this->generateShopItem(Item::get(ItemIds::BUCKET, 1), 1, 2 * 1, self::SILVER),
        ]);
        $menu->setListener(function (Player $player, Item $clicked, Item $clickedWith, SlotChangeAction $action): bool {
            $this->buyItem($clicked, $player);
            return true;
        });
        $menu->send($player);
    }

    /**
     * @return \Closure
     */
    private function subToMainShop(): \Closure
    {
        return function (Player $player, BaseFakeInventory $inventory) {
            $player->removeWindow($inventory);
            $this->openShop($player);
        };
    }

    private function generateShopItem(Item $item, int $size, int $value, string $valueType = self::GOLD): Item
    {
        $item->setCount($size);
        $item->setLore([$value . "x " . $valueType]);
        return $item;
    }

    private function buyItem(Item $item, Player $player): bool
    {
        [$value, $valueType] = explode("x ", $item->getLore()[0] ?? "0x " . self::GOLD);
        $value = intval($value);
        if ($value < 1) return false;
        $item = $item->setLore([]);
        switch ($valueType) {
            case self::BRONZE:
                $id = ItemIds::BRICK;
                break;
            case self::SILVER:
                $id = ItemIds::IRON_INGOT;
                break;
            case self::GOLD:
                $id = ItemIds::GOLD_INGOT;
                break;
            default:
                throw new \InvalidArgumentException("ValueType is wrong");
        }
        $payment = Item::get($id, 0, $value);
        if ($player->getInventory()->contains($payment)) {
            $player->getInventory()->removeItem($payment);
            $player->getInventory()->addItem($item);
            return true;
        }
        return false;
    }
}