<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Setting;

class HomeController extends Controller
{
    public function index(): void
    {
        $tuquinhaAboutVideoUrl = Setting::get('tuquinha_about_video_url', '') ?? '';

        $this->view('home/index', [
            'pageTitle' => 'Resenha 2.0 - Tuquinha',
            'tuquinhaAboutVideoUrl' => $tuquinhaAboutVideoUrl,
        ]);
    }
}
