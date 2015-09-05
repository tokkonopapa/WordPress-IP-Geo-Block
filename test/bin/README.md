The load caused by burst access
===============================

The `attack.sh` can mesure the load of the server caused by malicios burst 
access using [apache bench][atache-banch].

### Usage: ###

```./attack.sh [-a "IP address of attacker"] [-h "Home URL of WordPress"] [1-4]```

or edit the following lines in `attack.sh`.

```WPHOME="http://localhost:8888/wordpress/"
HEADER="X-Forwarded-For: 129.223.152.47"```

[atache-banch]: http://httpd.apache.org/docs/current/programs/ab.html "ab - Apache HTTP server benchmarking tool"
