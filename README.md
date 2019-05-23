# BedWars
A new BedWars plugin 
## Setup
Joining is done by using signs, but you can add any event for joining that you'd like - in JoinEventListener.php

Sign setup:
```
L1: [BedWars]
L2: mapname
L3: 
L4: 
```
Then, click on it, and you are set.

**You need to set up DEVirion and install the [gameapi](https://github.com/thebigsmileXD/gameapi) virion properly if you are running from source!**
(you could also turn this repository into a poggit project instead and use a compiled phar)
**Please search up how this is done yourself!**

If you want to give rewards to the winning player/team, you can either listen for the `WinEvent` in any plugin, or use [gamereward](https://github.com/thebigsmileXD/gamereward)