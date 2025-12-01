<?php

namespace App\Controllers;

use App\Core\Controller;

class SupportController extends Controller
{
    public function index(): void
    {
        $this->view('support/index', [
            'pageTitle' => 'Suporte - Tuquinha',
        ]);
    }
}
