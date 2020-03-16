FROM php:7.4-cli

# RUN apt-get update && apt-get install -y composer

COPY . /var/test
WORKDIR /var/test
CMD ["composer", "install"]

ENTRYPOINT ["php", "src/deadloop.php"];
