<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/*
|--------------------------------------------------------------------------
| Tenant Inbox Channel
|--------------------------------------------------------------------------
|
| Only authenticated users belonging to the tenant can listen to
| the private inbox channel for real-time message updates.
|
*/
Broadcast::channel('tenant.{tenantId}.inbox', function ($user, $tenantId) {
    return $user->tenant_id === (int) $tenantId;
});
