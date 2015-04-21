#! /bin/bash
ab -t 60 -c 5 -p 'attack-data.txt' -T 'application/x-www-form-urlencoded' -C 'wordpress_test_cookie=WP+Cookie+check' http://localhost:8888/wordpress/wp-login.php