<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

Breadcrumbs::for('home', function (BreadcrumbTrail $trail) {
    $trail->push('Home', route('home'));
});

Breadcrumbs::for('gear', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('Gear', route('gear'));
});
Breadcrumbs::for('gear.create', function (BreadcrumbTrail $trail) {
    $trail->parent('gear');
    $trail->push('Create Gear', route('gear.create'));
});

