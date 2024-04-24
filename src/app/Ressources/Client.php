<?php

namespace PixellWeb\Myrentcar\app\Ressources;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class Client extends Ressource
{

    public function getListe(array $parameters)
    {
        return $this->api->get("Clients/GetClientsWS", $parameters);
    }

    public function updateClientProperties(string $idClient, array $parameters)
    {
        $clientUpdateData = [
            "codeAgenceTravail" => $parameters["CodeAgenceDepart"],
            [
                "op" => "replace",
                "path" => "/Nom",
                "value" => $parameters["InfosClient"]["Nom"] ?? '',
            ],
            [
                "op" => "replace",
                "path" => "/Prenom",
                "value" => $parameters["InfosClient"]["Prenom"] ?? '',
            ],
            [
                "op" => "replace",
                "path" => "/Adresse1",
                "value" => $parameters["InfosClient"]["Adresse1"] ?? '',
            ],
            [
                "op" => "replace",
                "path" => "/Adresse2",
                "value" => $parameters["InfosClient"]["Adresse2"] ?? '',
            ],
            [
                "op" => "replace",
                "path" => "/Complement",
                "value" => $parameters["InfosClient"]["ComplementAdresse"] ?? '',
            ],
            [
                "op" => "replace",
                "path" => "/CodePostal",
                "value" => $parameters["InfosClient"]["CodePostal"] ?? '',
            ],
            [
                "op" => "replace",
                "path" => "/Ville",
                "value" => $parameters["InfosClient"]["Ville"] ?? '',
            ],
            [
                "op" => "replace",
                "path" => "/Telephone",
                "value" => $parameters["InfosClient"]["Telephone"] ?? '',
            ],
            [
                "op" => "replace",
                "path" => "/Mobile",
                "value" => $parameters["InfosClient"]["Mobile"] ?? '',
            ],
            [
                "op" => "replace",
                "path" => "/EMail",
                "value" => $parameters["InfosClient"]["Mail"] ?? '',
            ],
            [
                "op" => "replace",
                "path" => "/DateNaissance",
                "value" => $parameters["InfosClient"]["DateNaissance"] ? Carbon::createFromFormat('d/m/Y', $parameters["InfosClient"]["DateNaissance"])->format('Y-m-d\TH:i:s') : Carbon::now()->format('Y-m-d\TH:i:s'),
            ]
        ];

        return $this->api->patch("Clients/{$idClient}", $clientUpdateData);
    }
}
