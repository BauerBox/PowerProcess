# Power Process

PowerProcess is a PCNTL and POSIX wrapper for *nix systems running PHP
	
It is for managing and running forked process for parrallel computing with PHP

## Features
	* Easy Process Threading for Parallel Computing
	* Named Thread Support
	* Linux/Unix Signal Handling
	* Daemonizing PHP Scripts
	* Automatic Ticks (No longer have to add calls to Tick() in loops)
	* Dynamic callback support for *nix signals and custom events
	* Process Space Takeover Ability

## Important Notices

### PowerProcess 3.x

PowerProcess 3 represents a signifcant change in the structure of the code. So much in fact that there is no way to make it compatible with PowerProcess 2.x.  PowerProcess 2.x will continue to be hosted on GitHub at `https://github.com/lordgnu/PowerProcess.git` while PowerProcess 3.x and later will be hosted on GitHub at `https://github.com/BauerBox/PowerProcess.git`

### Database Connections

Open database connections made in the control process do not carry over to the threads.  This means if you are doing work with a MySQL (or other) database, you will need to open a new database connection in the thread code.

## Quick Start

### Threading

_Coming Soon!_

### Named-Threads

_Coming Soon!_

### Shared Memory

_Coming Soon!_

### Daemons

_Coming Soon!_	

## Want More?	

Make sure to check the examples included with the source and the full documentation at `Link Coming Soon!`