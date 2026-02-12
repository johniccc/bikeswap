#!/bin/bash
set -e

# Fix storage permissions (volume mount overrides Dockerfile chown)
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage

# Run the original Apache entrypoint
exec apache2-foreground