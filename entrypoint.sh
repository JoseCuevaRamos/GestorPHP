#!/bin/bash

# Run database migrations before starting Apache
if [ -f /var/www/html/vendor/bin/phinx ]; then
    echo "Running database migrations..."
    php /var/www/html/vendor/bin/phinx migrate -c /var/www/html/phinx.php
else
    echo "Phinx not found. Skipping migrations."
fi

# Start Apache in the foreground
exec apache2-foreground