<?php

function route_class()
{
    return str_replace('.', '-', Route::currentRouteName());
}

function group_union()
{
    return substr(mt_rand(),0,8);
}

