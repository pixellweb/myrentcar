<?php

namespace PixellWeb\Myrentcar\app\Ressources;



class Tarif extends Ressource
{


    public function get(string $code_tarif, int $duree, array $categories, bool $weekend = false) :array
    {
        $tarifs_response = $this->api->get('Reservations/GetTarifParDuree',
            [
                'codeTarif' => $code_tarif,
                //'kmEstime' => null,
                'codesCategories' => $categories,
                'duree' => $duree,
                'weekend' => $weekend ? 'true' : 'false',
                //'inclurePrestations' => null,
            ]
        );


        $tarifs = [];
        foreach ($tarifs_response as $key => $tarif) {
            $tarifs[$categories[$key]] = $tarif['PrixTTC'];
        }

        return $tarifs;

    }

}
