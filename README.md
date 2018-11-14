
### Build backend
    composer install

### Run with Compose
    docker-compose -f build/docker-compose.yml up -d

### Init database
    docker run -i --rm --network gitphp mysql mysql -v -hmysql < build/schema.sql

### Build frontend for development
    cd client
    php build.php development
    node_modules/.bin/webpack -w

### Cleaning
    docker-compose -f build/docker-compose.yml down
    cd client
    php build.php clean
