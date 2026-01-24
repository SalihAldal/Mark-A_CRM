<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $lang = $request->query('lang');
        if (in_array($lang, ['tr', 'en'], true)) {
            $request->session()->put('ui_lang', $lang);
        }

        $active = $request->session()->get('ui_lang', 'tr');
        app()->setLocale($active);

        return $next($request);
    }
}

