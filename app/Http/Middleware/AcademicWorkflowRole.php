<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AcademicWorkflowRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        abort_unless(auth()->check() && in_array((string) auth()->user()->raw_type, $roles, true), 403);

        return $next($request);
    }
}
