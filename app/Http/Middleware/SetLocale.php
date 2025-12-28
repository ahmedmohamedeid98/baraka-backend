<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Get locale from 'lang' header or default to 'ar' (Arabic)
        $locale = $request->header('lang') ?? 'ar';
        
        // Clean up locale (in case it's like "ar-SA" or "en-US")
        $locale = strtolower(substr($locale, 0, 2));
        
        // Ensure locale is supported
        $supportedLocales = ['en', 'ar'];
        if (!in_array($locale, $supportedLocales)) {
            $locale = 'ar';
        }
        
        App::setLocale($locale);
        
        return $next($request);
    }
}
