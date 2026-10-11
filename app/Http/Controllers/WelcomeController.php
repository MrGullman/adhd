<?php

namespace App\Http\Controllers;

use App\Enums\DeviceType;
use App\Support\CurrentFamily;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    /**
     * On a trusted family or child device the start page is the children's login.
     */
    public function __invoke(Request $request, CurrentFamily $current): Response|RedirectResponse
    {
        $device = $current->device();

        if ($request->user() === null && $device !== null && $device->type !== DeviceType::Display) {
            return to_route('child-login');
        }

        return Inertia::render('Welcome');
    }
}
