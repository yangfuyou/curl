<?php

function __autoload($class){
    include 'src/'.$class.'.php';
}

spl_autoload_register('__autoload');