<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Dashboard extends Controller
{
    public function index()
    {
        $auth = service('auth');

        if (! $auth->loggedIn()) {
            return redirect()->to('/masuk')->with('error', 'Silakan masuk terlebih dahulu.');
        }

        return view('dashboard/index', [
            'title' => 'Beranda - LifeQuest',
            'user'  => $auth->user(),
        ]);
    }
}
