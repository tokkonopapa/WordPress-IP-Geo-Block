The load caused by burst access
===============================

The `attack.sh` can mesure the load of the server caused by malicios burst 
access using [apache bench][ApacheBench].

### Usage: ###

    ./attack.sh [-a "IP address of attacker"] [-h "Home URL of WordPress"] [1-4]

where `[1-4]` should be specified as a target PHP file as follows:

1. `wp-comments-post.php`
2. `xmlrpc.php`
3. `wp-login.php`
4. `wp-admin/admin-ajax.php`

or edit the following lines in `attack.sh`:

    WPHOME="http://localhost:8888/wordpress/"
    HEADER="X-Forwarded-For: 129.223.152.47"

[ApacheBench]: http://httpd.apache.org/docs/current/programs/ab.html "ab - Apache HTTP server benchmarking tool"
