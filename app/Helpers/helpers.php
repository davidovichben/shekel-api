<?php

if (!function_exists('current_business_id')) {
    /**
     * Get the current business ID from the authenticated user.
     */
    function current_business_id(): int
    {
        if (auth()->check()) {
            return auth()->user()->business_id;
        }

        // Fallback for unauthenticated contexts (e.g., seeders, commands)
        return 1;
    }
}

if (!function_exists('current_business')) {
    /**
     * Get the current business from the authenticated user.
     */
    function current_business(): ?\App\Models\Business
    {
        if (auth()->check()) {
            return auth()->user()->business;
        }

        return null;
    }
}
