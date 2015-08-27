# Sass Compile 1337

## Requirements
 - PHP >= 5.5.9
 - composer

## How to install
 - git clone
 - cd into root directory
 - composer install
 - php artisan serve --host=127.0.0.1 --port=5000
 - visit http://127.0.0.1:5000/form

## Things that need to be done
 - Create directory: /public/css-compiled
 - Create directory: /public/sass-compile
 - cd /public/sass-compile
 - git clone https://github.com/RaisingIT/codeclips.git ./

## files of interest
 - resources/views/form.blade.php - the frontend for the form
 - app/Http/Controllers/SassController.php - where the sass compilation magic happens
 - app/Http/routes.php - the routing - only edit if you need to add extra pages
 - public/sass-compile/main.scss - this is where the scss is stored