<?php

use App\Models\Gear;
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
    $trail->parent('home');
    $trail->push('Gear', route('gear'));
    $trail->push('Create Gear', route('gear.create'));
});
Breadcrumbs::for('gear.edit', function (BreadcrumbTrail $trail, Gear $gear) {
    $trail->parent('home');
    $trail->push('Gear', route('gear'));
    $trail->push('Edit Gear', route('gear.edit', $gear));
});

Breadcrumbs::for('strava.auth.successful', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('Successful Strava Authorisation', route('strava.auth.successful'));
});

Breadcrumbs::for('strava.auth.unsuccessful', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('Unsuccessful Strava Authorisation', route('strava.auth.unsuccessful'));
});
