<?php

namespace PixellWeb\Myrentcar\app\Ressources;

use Carbon\CarbonInterface;

class Categorie extends Ressource
{


    public function liste()
    {
        return $this->api->get('Values/GetCategories');
    }

    public function vehiculesDisponible(CarbonInterface $debut, CarbonInterface $fin, string $agence, string $categorie = null) :array
    {
        $vehicules = $this->api->get('Reservations/GetVehiculesDispoSurAgence',
            [
                'debut' => $debut->format('Y-m-d\TH:i:s'),
                'fin' => $fin->format('Y-m-d\TH:i:s'),
                'codeCategorie' => $categorie,
                'codeAgence' => $agence,
            ]
        );

        return is_array($vehicules) ? $vehicules : [];
    }


    public function disponible(CarbonInterface $debut, CarbonInterface $fin, string $agence, string $categorie = null) :array
    {
        $vehicules = $this->vehiculesDisponible($debut, $fin, $agence, $categorie);

        $categories = [];
        foreach ($vehicules as $vehicule) {
            if (!in_array($vehicule["CodeCategorie"], $categories)) {
                $categories[] = $vehicule["CodeCategorie"];
            }
        }

        return $categories;
    }


    public function immatriculationDisponible(CarbonInterface $debut, CarbonInterface $fin, string $agence, string $categorie) : ?string
    {
        $vehicules = $this->vehiculesDisponible($debut, $fin, $agence, $categorie);

        return $vehicules[0]['Immatriculation'] ?? null;
    }

}
