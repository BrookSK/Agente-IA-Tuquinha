<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Personality;

class PersonalityController extends Controller
{
    public function index(): void
    {
        $personalities = Personality::allActive();

        $this->view('personalities/index', [
            'pageTitle' => 'Escolha a personalidade do Tuquinha',
            'personalities' => $personalities,
        ]);
    }
}
