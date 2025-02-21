<?php

namespace App\Services;

use App\Services\UrlGenerator;
use App\Services\TextHandler;
use App\Services\FormatToFrenchDate;
use App\Services\FlashMessage;


class ViewRenderer
{

    private UrlGenerator $url;
    private TextHandler $textHandler;
    private FormatToFrenchDate $formatDate;
    private FlashMessage $flashMessage;
    public function __construct()
    {
        $this->url = new UrlGenerator();
        $this->textHandler = new TextHandler();
        $this->formatDate = new FormatToFrenchDate();
        $this->flashMessage = new FlashMessage();
    }

    // methode appelée automatiquement dès lors qu'on appelle une méthode inexistante dans l'objet ou  dans l'instance de la classe
    // Parcours toutes les propriétés de l'objet
    // Teste si la méthode que l'on tente d'appeler existe
    // si elle existe, on appelle la methode
    // Sinon par defaut, on appelle la  méthode invoque
    public function __call($name, $arguments)
    {
        // retourne la liste des propriétés accessibles de l'objet en argument
        // ici $url et $textHandler sous forme de tableau
        // voir doc __invoque, ..., get_object_vars, is_callable
        $properties = get_object_vars($this);
        foreach ($properties as $propertyValue) {
            if (is_callable([$propertyValue, $name])) {
                return $propertyValue->$name(...$arguments);
            }
        }
        return ($this->$name)(...$arguments);
    }
    public function render(string $view, array $data = [], int $statusCode = 200): void
    {
        // Défini le code HTTP
        http_response_code($statusCode);

        // $data['flashMessage'] = $this->flashMessage;
        // Vérifie si le fichier de vue existe
        $viewPath = __DIR__ . "/../../views/{$view}";
        if (!file_exists($viewPath)) {
            throw new \RuntimeException("Le fichier '{$view}' est introuvable.");
        }
        // Chemin du fichier de mise en page (layout)
        $layoutPath = __DIR__ . "/../../views/layout.phtml";
        if (!file_exists($layoutPath)) {
            throw new \RuntimeException("Le fichier de mise en page est introuvable.");
        }
        // Extrait les données à inclure dans la vue
        extract($data);

        // Définir $template pour inclure la vue dans le layout
        $template = $viewPath;

        // Inclure le fichier layout
        require $layoutPath;
    }
}
