<?php

namespace Thinkrix\Middleware;

use Closure;
use think\Request;
use think\Response;
use Thinkrix\Exceptions\ApiException;

class HandleApiException
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->withServer(array_merge($request->server(), [
            'HTTP_ACCEPT' => 'application/json',
        ]));
        $request->withHeader(array_merge($request->header(), [
            'Accept' => 'application/json',
        ]));

        try {
            return $next($request);
        } catch (ApiException $exception) {
            return $exception->render();
        }
    }
}
