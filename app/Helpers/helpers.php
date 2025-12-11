<?php

if (!function_exists('current_business_id')) {
    /**
     * Get the current business ID.
     * Currently returns hardcoded value, will be updated to use logged in user's business.
     */
    function current_business_id(): int
    {
        // TODO: Get from logged in user when authentication is properly set up
        // return auth()->user()->business_id;
        return 1;
    }
}
