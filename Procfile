web: php artisan migrate --force && php -d variables_order=EGPCS -S 0.0.0.0:$PORT -t public
worker: php artisan queue:work --tries=3 --timeout=120 --sleep=3
scheduler: php artisan schedule:work
