#!/bin/sh

# wp core download --version=$WORDPRESS_VERSION --locale=en_US
wp config create --dbhost=$MYSQL_HOST --dbname=$MYSQL_DATABASE --dbuser=$MYSQL_USER --dbpass="$MYSQL_PASSWORD" --allow-root 
wp core install --url=http://localhost --title="LUMA" --admin_user=$WORDPRESS_ADMIN_USERNAME --admin_password=$WORDPRESS_ADMIN_PASSWORD --admin_email=$WORDPRESS_ADMIN_EMAIL --skip-email --allow-root
wp plugin install wordpress-importer --activate
wp plugin install woocommerce --version=$WOOCOMMERCE_VERSION --allow-root --activate
wp theme install storefront --allow-root --activate
php -f woocommerce-setup-wizard.php
wp import ./woocommerce-products.xml --authors=create --allow-root

rm -rf wp-config-sample.php wp-admin/install*.php