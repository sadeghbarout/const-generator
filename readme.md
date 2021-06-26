# Installation: 

1- Composer require colbeh/consts

2- Make sure you have app/Extras/consts.php and added to composer.json

        ...
        
        "autoload": {
                "files":[
                    ...
                    "app/Extras/consts.php"
                    ...
                ]
            },
            
        ...

3- Go to 

        domain.com/const
        
4- Add consts file
        
        php artisan vendor:publish --provider="Colbeh\Consts\ServiceProvider" --tag=app


# Upgrade:
 
        composer require colbeh/consts:x.x.x


