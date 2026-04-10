tvm-backend codebase contains the central api that communicates with the mobile and web app for (CRUD, auth, advanced patterns and more) that adheres to the system specs and API.

#Getting started
#Installation
Please check the official laravel installation guide for server requirements before you start <a href="https://laravel.com/docs/11.x#installing-php">[Official Documentation]</a>

Clone the repository
    https://github.com/Travimobile-Engineering/tvm-backend.git

Switch to the repo folder

    cd tvm-backend

Install all the dependencies using composer

    composer install

Copy the example env file and make the required configuration changes in the .env file

    cp .env.example .env

Generate a new application key

    php artisan key:generate

Generate a new JWT authentication secret key

    php artisan jwt:generate

Run the database migrations (**Set the database connection in .env before migrating**)

    php artisan migrate

***Note*** : It's recommended to have a clean database before seeding. You can refresh the migrations at any point to clean the database by running the following command

    php artisan migrate:refresh


Run the database seeder and you're done

    php artisan db:seed


Start the local development server

    php artisan serve

You can now access the server

#####################################################################################
Alternative installation is possible without local dependencies relying on Docker

cd tvm-backend
cp .env.example.docker .env
docker run -v $(pwd):/app composer install
cd ./docker
docker-compose up -d
docker-compose exec php php artisan key:generate
docker-compose exec php php artisan jwt:generate
docker-compose exec php php artisan migrate
docker-compose exec php php artisan db:seed
docker-compose exec php php artisan serve --host=0.0.0.0


###Other useful info

** INSTALL PHP DEPENDENCIES **
sudo apt install -y php8.2-fpm php8.2-cli php8.2-mcrypt php8.2-gd php8.2-imagick php8.2-mysql php8.2-pgsql php8.2-imap php8.2-memcached php8.2-mbstring php8.2-xml php8.2-curl php8.2-bcmath php8.2-sqlite3 php8.2-xdebug php8.2-ctype php8.2-common php8.2-xmlrpc   php8.2-dev   php8.2-opcache php8.2-soap php8.2-zip php8.2-redis php8.2-intl

# Code overview

## Dependencies

- [jwt-auth](https://github.com/tymondesigns/jwt-auth) - For authentication using JSON Web Tokens
- [laravel-cors](https://github.com/barryvdh/laravel-cors) - For handling Cross-Origin Resource Sharing (CORS)
- [cloudinary-labs/cloudinary-laravel](https://github.com/cloudinary-community/cloudinary-laravel) - For handling Cloudinary SDK

## Folders

- `app` - Contains all the Eloquent models
- `app/Http/Controllers` - Contains all the api controllers
- `app/Http/Middleware` - Contains the CORS and JWT auth middleware
- `app/Http/Requests` - Contains all the api form requests
- `config` - Contains all the application configuration files
- `database/factories` - Contains the model factory for all the models
- `database/migrations` - Contains all the database migrations
- `database/seeds` - Contains the database seeder
- `routes` - Contains all the api routes defined in api.php file
- `tests` - Contains all the application tests

## Environment variables

- `.env` - Environment variables can be set in this file

***Note*** : You can quickly set the database information and other variables in this file and have the application fully working.

To create your .env file from the example template, run:

cp .env.example .env
----------

# Testing API

Run the laravel development server

    php artisan serve

The api can now be accessed at

    http://localhost:8000/api

Request headers

| **Required** 	| **Key**              	| **Value**            	|
|----------	|------------------	|------------------	|
| Yes      	| Content-Type     	| application/json 	|
| Yes      	| X-Requested-With 	| XMLHttpRequest   	|
| Optional 	| Authorization    	| Token {JWT}      	|

Refer the [api specification](#api-specification) for more info.

----------
 
# Authentication
 
This applications uses JSON Web Token (JWT) to handle authentication. The token is passed with each request using the `Authorization` header with `Token` scheme. The JWT authentication middleware handles the validation and authentication of the token. Please check the following sources to learn more about JWT.
 
- https://jwt.io/introduction/
- https://self-issued.info/docs/draft-ietf-oauth-json-web-token.html

----------

# Cross-Origin Resource Sharing (CORS)
 
This applications has CORS enabled by default on all API endpoints. The default configuration allows requests from `http://localhost:5173` and `https://v2.travimobile.com` to help speed up our frontend testing. The CORS allowed origins can be changed by setting them in the config file. Please check the following sources to learn more about CORS.
 
- https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS
- https://en.wikipedia.org/wiki/Cross-origin_resource_sharing
- https://www.w3.org/TR/cors

