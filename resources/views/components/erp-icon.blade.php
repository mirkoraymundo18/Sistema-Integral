@props(['name'])
<svg {{ $attributes->merge(['class' => 'erp-icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('building')<path d="M4 21h16M6 21V4h12v17M9 8h1m4 0h1m-6 4h1m4 0h1M10 21v-5h4v5"/>@break
        @case('dashboard')<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>@break
        @case('products')<path d="m12 3 9 5-9 5-9-5 9-5Zm-9 5v9l9 5 9-5V8M12 13v9M7.5 5.5l9 5"/>@break
        @case('kardex')<path d="M4 7h16m-4-4 4 4-4 4M20 17H4m4-4-4 4 4 4"/>@break
        @case('requests')<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 3h6v3H9zM9 11h6m-6 4h4"/>@break
        @case('restock')<path d="M4 17V8m5 9V5m5 12v-6m5 6V3M3 21h18"/>@break
        @case('counts')<path d="M9 4h10v17H5V8m0-4h4v4H5zM9 12l2 2 4-4m-6 7h6"/>@break
        @case('users')<circle cx="9" cy="8" r="3"/><path d="M3 21v-2a6 6 0 0 1 12 0v2m1-16a3 3 0 0 1 0 6m3 10v-2a6 6 0 0 0-2-4"/>@break
        @case('settings')<path d="M4 7h16M4 17h16"/><circle cx="8" cy="7" r="2"/><circle cx="16" cy="17" r="2"/>@break
        @case('logout')<path d="M9 4H4v16h5m5-12 4 4-4 4m-5-4h13"/>@break
        @case('menu')<path d="M4 6h16M4 12h16M4 18h16"/>@break
        @case('close')<path d="m6 6 12 12M18 6 6 18"/>@break
    @endswitch
</svg>
