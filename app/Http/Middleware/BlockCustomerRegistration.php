<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BlockCustomerRegistration
{
    public function handle(Request $request, Closure $next)
    {
        // Prefijo del Admin (en tu caso "panel")
        $adminPrefix = trim(config('app.admin_url', 'admin'), '/');

        // 1) Si es una ruta del Admin, no bloquear
        if ($request->is($adminPrefix.'/*')) {
            return $next($request);
        }

        // 2) Bloquear por NOMBRE de ruta (más seguro para storefront)
        $routeName = $request->route()?->getName() ?? '';
        $shouldBlockByName =
            str_starts_with($routeName, 'shop.customer.')   // p. ej. shop.customer.session.index/create/destroy
            || str_starts_with($routeName, 'shop.customers.'); // p. ej. shop.customers.register.*, account.*, etc.

        // 3) Bloquear por PATH sólo si EMPIEZA con (opcional locale)/customer/
        //    No coincidirá con "panel/configuration/customer/settings"
        $path = ltrim($request->path(), '/');
        $shouldBlockByPath = (bool) preg_match('#^(?:[a-z]{2}(?:-[A-Z]{2})?/)?customer/.*#', $path);

        if ($shouldBlockByName || $shouldBlockByPath) {
            if (Auth::guard('customer')->check()) {
                Auth::guard('customer')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            abort(404); // o redirect()->route('shop.home.index');
        }

        return $next($request);
    }
}
