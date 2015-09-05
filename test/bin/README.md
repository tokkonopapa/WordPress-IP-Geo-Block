Measuring impact of broute-force attack
=======================================

The `attack.sh` is a shell program which can mesure the load on the server 
caused by malicious burst access using [apache bench][ApacheBench].

### Usage: ###

    ./attack.sh [-a "attacker IP address"] [-h "Home URL of WordPress"] [1-4]

where `[1-4]` should be specified as a target PHP file as follows:

1. `wp-comments-post.php`
2. `xmlrpc.php`
3. `wp-login.php`
4. `wp-admin/admin-ajax.php`

or edit the following lines in `attack.sh`.

    WPHOME="http://localhost:8888/wordpress/"
    HEADER="X-Forwarded-For: 129.223.152.47"

Make sure to put `HTTP_X_FOWARDED_FOR` to `$_SERVER keys for extra IPs` at 
`Validation rule settings` on `Settings` tab like following so that the 
malicious burst accesses can be blocked by IP Geo Block.

![HTTP_X_FOWARDED_FOR][X-Forwarded]

### Parameter files: ###

The following files are used to request by POST method.

* `wp-comments-post.txt`
* `xmlrpc.txt`
* `wp-login.txt`

### Settings for ab: ###

* concurrency  
  Number of multiple requests to perform at a time. [5]
* timelimit  
  Maximum number of seconds to spend for benchmarking. [60]

### CAUTION: ###

Do not abuse.

[ApacheBench]:  http://httpd.apache.org/docs/current/programs/ab.html "ab - Apache HTTP server benchmarking tool"
[X-Forwarded]:  http://tokkonopapa.github.io/WordPress-IP-Geo-Block/img/2015-09/X-Forwarded-For.png "$_SERVER keys for extra IPs"
[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
