<?php

namespace App\Http\Controllers;

use App\Concerns\UploadedFile;
use App\Models\Setting;
use Inertia\Inertia;
use Inertia\Response;

class FrontendController extends Controller
{
    use UploadedFile;
    /**
     * @return Response
     */
    public function index(): Response
    {
        return Inertia::render('Frontend', [
            'primaryColor' => Setting::get('primary_color', '#1f2937'),
        ]);
    }

    /**
     * @return Response
     */
    public function privacy(): Response
    {
        return Inertia::render('Privacy', [

        ]);
    }

    /**
     * @return Response
     */
    public function terms(): Response
    {
        return Inertia::render('Terms', [

        ]);
    }

    /**
     * @return Response
     */
    public function cookies(): Response
    {
        return Inertia::render('Cookies', [

        ]);
    }
}
