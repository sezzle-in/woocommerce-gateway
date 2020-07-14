# Sezzle WooCommerce Plugin

## Start
Use `docker-compose up -d` to start the Wordpress server.

## Install WooCommerce
You can configure the woocommerce version to be installed in docker.env

```bash
docker exec -it woocommerce install
```

## Install Sezzle Plugin
Sezzle plugin directory is mounted to the container.

```bash
docker exec -it woocommerce wp plugin activate sezzle-woocommerce-payment
```

Once you activate, you can make changes to `sezzle-gateway.php` and see the changes from [wp admin page](http://localhost/wp-admin/admin.php?page=wc-settings&tab=checkout&section=sezzlepay). Login using the admin username and password configured in docker.env

## Cleanup
```bash
docker-compose down --rmi local -v --remove-orphans
```

### Update the image
```bash
docker build -t hisankaran/woocommerce:latest --build-arg WORDPRESS_VERSION=5.3.2 ./internal/.
docker push hisankaran/woocommerce:latest
```

### Create a release archive
```bash
cat release.txt | zip -r@ "sezzle-woocommerce-payment.zip"
```