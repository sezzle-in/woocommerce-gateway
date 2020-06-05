FROM trafex/alpine-nginx-php7

ARG WORDPRESS_VERSION

USER root

RUN apk update \
    && apk add less mysql-client sudo

RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
RUN php wp-cli.phar --info
RUN chmod +x wp-cli.phar
RUN mv wp-cli.phar /usr/local/bin/wp
RUN mkdir -p /.wp-cli/cache && chown nobody:nobody /.wp-cli/cache

RUN rm -f *

ADD --chown=nobody:nobody scripts/woocommerce-* ./

COPY ./scripts/install.sh /usr/local/bin/install

USER nobody

RUN wp core download --version=${WORDPRESS_VERSION} --locale=en_US
