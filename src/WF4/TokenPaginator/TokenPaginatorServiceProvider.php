<?php

namespace WF4\TokenPaginator;

use Illuminate\Support\ServiceProvider;

class TokenPaginatorServiceProvider extends ServiceProvider
{
    public function register()
    {
        TokenPaginator::setCurrentTokenResolver(function ($tokenName = 'token') {
            return $this->app['request']->input($tokenName);
        });
    }
}