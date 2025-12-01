<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Plan;

class PlanController extends Controller
{
    public function index(): void
    {
        $plans = Plan::allActive();

        $this->view('plans/index', [
            'pageTitle' => 'Planos - Tuquinha',
            'plans' => $plans,
        ]);
    }
}
