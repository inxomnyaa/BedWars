# BedWars
A new BedWars plugin 
## Setup
Set up an arena:
Use the command `/bw setup` to open an ui, where you can add, create and modify arenas.

It allows you to create item spawners, set team spawn points, build in the world and create villager shops.

Remember to use `/bw endsetup` when you are done - it automatically saves and backs up the world. (Not necessary after "New Arena" action)

There are arenaname.json files, where you can modify some settings like team damaging and breakable blocks.

## Joining / Sign setup
Joining is done by using signs, but you can add any event for joining that you'd like - in JoinEventListener.php

Sign setup:
```
L1: [BedWars]
L2: mapname
L3: 
L4: 
```

## From source

**You need to set up DEVirion and install the [gameapi](https://github.com/thebigsmileXD/gameapi) virion properly if you are running from source!**
(you could also turn this repository into a poggit project instead and use a compiled phar)
**Please search up how this is done yourself!**

## Rewards and win messages
If you want to give rewards to the winning player/team, you can either listen for the `WinEvent` in any plugin, or use [gamereward](https://github.com/thebigsmileXD/gamereward)
