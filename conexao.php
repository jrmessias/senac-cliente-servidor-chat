<?php
define("HOST","127.0.0.1");
define("USER","user");
define("PASSWORD","password");
define("DATABASE","database");
$link = mysqli_connect(HOST,USER,PASSWORD,DATABASE);//connecta a base de dados
mysqli_set_charset($link, 'utf8');//define o charset como UTF-8