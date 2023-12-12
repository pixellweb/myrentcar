<?php

namespace PixellWeb\Myrentcar\app\Ressources;

use Carbon\CarbonInterface;

class Categorie extends Ressource
{


    public function liste()
    {
        return $this->api->get('Values/GetCategories');
    }


    public function disponible(CarbonInterface $debut, CarbonInterface $fin, string $agence, string $categorie = null) :array
    {
        $vehicules = $this->api->get('Reservations/GetVehiculesDispoSurAgence',
            [
                'debut' => $debut->format('Y-m-d\TH:i:s'),
                'fin' => $fin->format('Y-m-d\TH:i:s'),
                'codeCategorie' => $categorie,
                'codeAgence' => $agence,
            ]
        );

        if (!is_array($vehicules)) {
            return [];
        }

        $categories = [];
        foreach ($vehicules as $vehicule) {
            if (!in_array($vehicule["CodeCategorie"], $categories)) {
                $categories[] = $vehicule["CodeCategorie"];
            }
        }

        return $categories;
    }

}
