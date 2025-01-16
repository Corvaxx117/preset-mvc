<?php

// Rôle : Analyse les requêtes (URI et méthode HTTP) et les fait correspondre aux routes définies.
// Fonctionnement :
// Charge les routes depuis routes.yaml.
// Utilise des expressions régulières pour gérer les routes dynamiques (ex. : /news/:id).
// Retourne le contrôleur et les paramètres associés à une route.

namespace App\Core;

use Symfony\Component\Yaml\Yaml;
use App\Exceptions\NotFoundException;

class Router
{
    private array $routes;

    public function __construct(string $routesFile)
    {
        $this->routes = Yaml::parseFile($routesFile)['routes'];
    }

    public function match(string $uri, string $method)
    {
        foreach ($this->routes as $route => $config) {
            // '/:\w+/' : Une regex qui trouve les paramètres dynamiques (comme :id).
            // '(\w+)' : La chaîne de remplacement. Ici, on utilise une parenthèse capturante
            // $route : La chaîne sur laquelle effectuer le remplacement.
            // '/' est un délimiteur en regexp, on échappe donc '/' par '\/' grace a str_replace
            $pattern = preg_replace('/:\w+/', '(\w+)', str_replace('/', '\/', $route));

            // $matches contient tous les paramètres extrait de l'URI
            // arguments de pregmatch optionnel passé par référence (alimenté directement par cette fonction )
            // le preg_match permet de tester si l'url de la requête correspond bien à une route
            // il alimente $matches avec toutes les valeurs variable de la route par rapport à l'url
            if (preg_match('/^' . $pattern . '$/', $uri, $matches) && $method === $config['method']) {
                // $matches[0] contiendra toujours ce que l'expression reguliere valide dans sa totalité, 
                // la ou les index suivant contiendront seulement ce qui est capturé 
                // (par capture j'entends les parenthèse de la regexp)
                array_shift($matches); // Retire le 1er élément
                // dd($route, $uri, $config, $pattern, $matches);
                // si une route est matchée on retourne donc un array structuré qui va nous être utile pour appeler le bon controller avec les bons arguments
                return ['callable' => $config['callable'], 'params' => $matches];
            }
        }
        throw new NotFoundException("Aucune route correspondante pour l'URI: $uri");
    }
}
