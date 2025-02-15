FROM php:8.4-cli

WORKDIR /var/www/html

RUN apt-get update -y \
  && apt-get install -y \
    git \
    unzip \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/* \
  && pecl channel-update pecl.php.net \
  && pecl install xdebug \
  && docker-php-ext-enable xdebug

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ARG UID=1000
ARG GID=1000
ARG USERNAME=user
ARG GROUPNAME=user

RUN groupadd -g $GID $GROUPNAME \
    && useradd -m -s /bin/bash -u $UID -g $GID $USERNAME

USER $USERNAME
