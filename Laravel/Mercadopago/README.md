## Package MercadoPago with Laravel.

ACLARATION 1: 
This package was developed working with "mercadopago/dx-php": "1.1.8", but the current version is 2.0.0.
The version in composer.json was already updated, but you have to check if the fields name MP return are the same. 

ACLARATION 2:
I'm using orderRepository and Cart that are Bagisto packages, perhaps to keep a consistent package 
without depends of Bagisto you can remove that code and distpach events instead (so from outside the package you can create listeners for these events).
