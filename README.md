![BedWars wide banner!](https://github.com/thebigsmileXD/BedWars/blob/master/resources/xbedwars_icon.png)
---
# BedWars
[![](https://poggit.pmmp.io/shield.state/BedWars)](https://poggit.pmmp.io/p/BedWars)
[![](https://poggit.pmmp.io/shield.api/BedWars)](https://poggit.pmmp.io/p/BedWars)
[![](https://poggit.pmmp.io/shield.dl.total/BedWars)](https://poggit.pmmp.io/p/BedWars)

A new BedWars plugin by XenialDan
## Setup
Set up an arena:
Use the command `/bw setup` to open an ui, where you can add, create and modify arenas.

It allows you to create item spawners, set team spawn points, build in the world and create villager shops.

Remember to use `/bw endsetup` when you are done - it automatically saves and backs up the world.

There are `{arenaname}.json` files, where you can modify some settings like team damaging and breakable blocks.

## Joining / Sign setup
Joining is done by using signs, but you can add any event for joining that you'd like - in JoinEventListener.php

Sign setup:
```
L1: [BedWars]
L2: mapname
L3: 
L4: 
```

## Rewards and win messages
If you want to give rewards to the winning player/team, you can either listen for the `WinEvent` in any plugin, or use [gamereward](https://github.com/thebigsmileXD/gamereward)

## TODOs
- [ ] Settings for villager shop (entity used, items)
- [ ] Spectator mode
- [ ] Scoreboard