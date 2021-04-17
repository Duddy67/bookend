<?php namespace Codalia\Bookend\Classes;

use Closure;
use Response;
use Codalia\Bookend\Models\Settings;


class RestfulApiMiddleware
{
    public function handle($request, Closure $next)
    {
        if (substr($request->path(), 0, 6) === 'api/v1' && !Settings::get('restful_api', 0)) {
	    return Response::json(['error' => 'Service unavailable'], 503);
	}

        return $next($request);
    }
}
