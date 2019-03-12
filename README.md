# Biblioteca Api Forebase Cloud Messaging
Biblioteca PHP v7.2 para manipulação da API do FCM

# Como utilizar

```php
<?php

include "vendor/autoload.php";

use FcmLibrary\FcmLibrary;

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('America/Sao_Paulo');

$apiFcm = new FcmLibrary();
```
