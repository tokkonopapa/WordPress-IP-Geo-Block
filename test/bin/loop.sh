#! /bin/sh
# https://httpd.apache.org/docs/current/programs/ab.html
# Note: Set an appropriate WPHOME and IP address as an attacker.
# Note: Add "HTTP_X_FORWARDED_FOR" into "$_SERVER keys for extra IPs"
#       in order to simulate the requests from outside your country.

WPHOME="http://localhost/"
HEADER="X-Forwarded-For: 129.223.152.47"

COOKIE="wordpress_test_cookie=WP+Cookie+check"
ABOPTS="-t 1 -c 1 -n 1"

while [ $# -ge 1 ]; do
    case $1 in
        -i)  # IP address of an attacker
            shift; HEADER="X-Forwarded-For: $1"
            ;;
        -i*) # IP address of an attacker
            HEADER="X-Forwarded-For: `echo $1 | cut -c3-`"
            ;;
        -w)  # WordPress home ULR
            shift; WPHOME=$1
            ;;
        -w*) # WordPress home ULR
            WPHOME=`echo $1 | cut -c3-`
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
        for i in `seq 1 60`
        do
            ab ${ABOPTS} -H "${HEADER}" -C "${COOKIE}" -T "application/x-www-form-urlencoded" -p "wp-comments-post.txt" ${WPHOME}wp-comments-post.php
            sleep 1
        done
        ;;

    2) # xmlrpc.php
        echo "=== attack on xmlrpc.php ===\n"
        for i in `seq 1 60`
        do
            ab ${ABOPTS} -H "${HEADER}" -C "${COOKIE}" -T "text/html" -p "xmlrpc.txt" ${WPHOME}xmlrpc.php
            sleep 1
        done
        ;;

    3) # sys.multicall xmlrpc.php
        echo "=== attack on xmlrpc.php by sys.multicall ===\n"
        for i in `seq 1 60`
        do
            ab ${ABOPTS} -H "${HEADER}" -C "${COOKIE}" -T "text/html" -p "multicall.txt" ${WPHOME}xmlrpc.php
            sleep 1
        done
        ;;

    4) # wp-login.php
        echo "=== attack on wp-login.php ===\n"
        for i in `seq 1 60`
        do
            ab ${ABOPTS} -H "${HEADER}" -C "${COOKIE}" -T "application/x-www-form-urlencoded" -p "wp-login.txt" ${WPHOME}wp-login.php
            sleep 1
        done
        ;;

    5) # wp-admin/admin-ajax.php
        echo "=== attack on wp-admin/admin-ajax.php ===\n"
        for i in `seq 1 60`
        do
            ab ${ABOPTS} -H "${HEADER}" -C "${COOKIE}" -T "text/plain" "${WPHOME}wp-admin/admin-ajax.php?action=donwload&file=../wp-config.php"
            sleep 1
        done
        ;;

    6) # index.php
        echo "=== attack on index.php ===\n"
        for i in `seq 1 60`
        do
            ab ${ABOPTS} -H "${HEADER}" -C "${COOKIE}" ${WPHOME}index.php
            sleep 1
        done
        ;;

    *) # help
        echo "usage: $0 [-i \"IP address of attacker\"] [-w \"WordPress home URL\"] [1-5]"
        echo "where: 1 ... wp-comments-post.php"
        echo "     : 2 ... xmlrpc.php"
        echo "     : 3 ... xmlrpc.php by sys.multicall"
        echo "     : 4 ... wp-login.php"
        echo "     : 5 ... wp-admin/admin-ajax.php"
        echo "     : 6 ... index.php"
        ;;
esac
