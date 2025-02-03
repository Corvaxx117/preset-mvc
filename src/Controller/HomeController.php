<?php

namespace App\Controller;

class HomeController extends BaseController
{
    public function __invoke()
    {
        $data = [
            'title' => 'Page d\'accueil',
            'content' => 'Bienvenue sur la page d\'accueil !'
        ];
        $this->viewRenderer->render('home.phtml', $data);
    }
}
