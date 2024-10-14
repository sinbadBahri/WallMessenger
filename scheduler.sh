#!/bin/bash

while true; do
    php artisan schedule:run >> /dev/null 2>&1
    php artisan queue:work > /dev/null 2>&1 &
    sleep 60
done
