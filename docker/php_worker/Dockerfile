FROM thelocal/coopcycle-web

ENTRYPOINT ["sh", "-c", "sleep 60 && /var/www/html/bin/console messenger:consume async --limit=100 --time-limit=900 -n"]
