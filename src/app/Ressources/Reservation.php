<?php

namespace PixellWeb\Myrentcar\app\Ressources;

use Carbon\CarbonInterface;
use Ipsum\Reservation\app\Location\Prestation;
use Ipsum\Reservation\app\Models\Tarif\Saison;
use Illuminate\Support\Str;
use PixellWeb\Myrentcar\app\MyrentcarException;

class Reservation extends Ressource
{

    public function get(int $id)
    {
        return $this->api->get('Reservations/GetReservation', ['id' => $id]);
    }


    public function create(\Ipsum\Reservation\app\Models\Reservation\Reservation $reservation, ?array $params  = [], $attribution_vehicule = true) :array
    {

        $immatriculation = null;
        if ($attribution_vehicule) {
            $categorie = new Categorie();
            $immatriculation = $categorie->immatriculationDisponible($reservation->debut_at, $reservation->fin_at, $reservation->lieuDebut->custom_fields->hitech_code, $reservation->categorie->custom_fields->hitech_code);
        }

        try {
            $resa = $this->creerReservation($reservation, $params, $immatriculation);
        } catch (MyrentcarException $e) {
            if ($e->getResponseMessage() == 'WS_ERR_NOT_DISPO_VEHICULE') {
                $resa = $this->creerReservation($reservation, $params);
            } else {
                throw $e;
            }
        }

        if($this->getHitechCodeClient($reservation)){
            $client = new Client();
            $myrentcar_client = $client->getListe(['numero' => $this->getHitechCodeClient($reservation)]);
            if($myrentcar_client && isset($myrentcar_client[0])){
                // TODO UPDATE INFO CLIENT MYRENTCAR
                //$client->updateClientProperties($myrentcar_client[0]['ID'], $parameters);
            }
        }

        $reservation->custom_fields->hitech_code = $resa['CleDocument'];
        $reservation->custom_fields->hitech_numero = $resa['NumeroDocument'];
        $reservation->custom_fields->hitech_code_client = $resa['NumeroClient'];
        $reservation->save();
        if ($reservation->client) {
            $reservation->client->custom_fields->hitech_code = $resa['NumeroClient'];
            $reservation->client->save();
        }

        return $resa;
    }

    protected function creerReservation(\Ipsum\Reservation\app\Models\Reservation\Reservation $reservation, ?array $params, ?string $immatriculation = null): array
    {
        $is_mobile = in_array(substr($reservation->telephone, 0, 2), ['06', '07']);

        $saison = Saison::betweenDates($reservation->debut_at, $reservation->fin_at)->first();

        $parameters = [
            "Depart" => $reservation->debut_at->format('Y-m-d\TH:i:s'),
            "CodeAgenceDepart" => $reservation->lieuDebut->custom_fields->hitech_code,
            "LieuDepart" =>  Str::limit(($reservation->observation.($reservation->custom_fields->vol ? ' vol : '.$reservation->custom_fields->vol : '')), 30, ''),
            "Retour" => $reservation->fin_at->format('Y-m-d\TH:i:s'),
            "CodeAgenceRetour" => $reservation->lieuFin->custom_fields->hitech_code,
            "LieuRetour" => null,
            "CodeAgenceTravail" => $reservation->lieuDebut->custom_fields->hitech_code, // Agence origine ?
            "KmEstime" => 0,
            "CodeCategorie" => $reservation->categorie->custom_fields->hitech_code,
            "Note" => 'Réservation '. parse_url(config('app.url'), PHP_URL_HOST).' : '.$reservation->reference."\n\r".$reservation->observation,
            "ImmatriculationVehicule" => $immatriculation, // N'attribue pas automatiquement de véhicule
            "InfosClient" => [
                "Numero" => $this->getHitechCodeClient($reservation),
                "Nom" => $reservation->nom,
                "Prenom" => $reservation->prenom,
                "Adresse1" => $reservation->adresse,
                "Adresse2" => null,
                "ComplementAdresse" => null,
                "CodePostal" => $reservation->cp,
                "Ville" => $reservation->ville,
                "Telephone" => !$is_mobile ? $reservation->telephone : null,
                "Mobile" => $is_mobile ? $reservation->telephone : null,
                "Mail" => $reservation->email,
                "DateNaissance" => $reservation->naissance_at?->format('Y-m-d'),
                "ReponsesConducteur1" => null,
                "ReponsesConducteur2" => null,
                "ReponsesClient" => null,
                "Civilite" => config('myrentcar.civilite_monsieur'),
            ],
            "TarifClient" => [
                "CodeTarif" => $saison->custom_fields->hitech_code,
                "Remise" => 0,
                "ModeRechercheDuree" => "BEST",
                "NombreJoursBase" => $reservation->nb_jours,
                "NombreJoursSuppl" => 0,
                "NombreHeureSuppl" => 0,
                "NombreKmInclus" => 0
            ],
            "TarifPEC" => null,
            "LignesPrix" => [
                [
                    "IsPec" => false,
                    "CodeReservation" => null,
                    "NumeroReservation" => null,
                    "CodePrestation" => config('myrentcar.code_prestation'),
                    "LibellePrestation" => config('myrentcar.libelle_prestation'),
                    "Montant" => $this->formatMontant($reservation->montant_base / $reservation->nb_jours),
                    "Souscription" => true,
                    "TypePrix" => '1',
                    "Quantite" => 1,
                    "Plafond" => 0
                ]
            ]
        ];


        if ($reservation->prestations) {
            $prestations = Prestation::all();
            foreach ($reservation->prestations as $prestation) {
                $parameters["LignesPrix"][] = [
                    "IsPec" => false,
                    "CodeReservation" => null,
                    "NumeroReservation" => null,
                    "CodePrestation" => $prestations->find($prestation->id)->custom_fields->hitech_code, // LOCATION, TAXE AEROPORT, JOURS SUPP, KMS SUPP
                    "LibellePrestation" => $prestation->tarification != "agence" ? $prestations->find($prestation->id)->custom_fields->hitech_code : Str::limit($prestation->nom.' (en agence)', 30, ''),
                    "Montant" => $this->formatMontant($prestation->tarif),
                    "Souscription" => true,
                    "TypePrix" => '2',  // 1-Jour 2-Forfait f
                    "Quantite" => $prestation->quantite,
                    "Plafond" => 0
                ];

            }
        }

        // FAIRE REMONTER PRESTATION KM SUPP EN METTANT SOUSCRIPTIONS FALSE
        if($reservation->categorie->custom_fields->km_supplementaire){
            $parameters["LignesPrix"][] = [
                "IsPec" => false,
                "CodeReservation" => null,
                "NumeroReservation" => null,
                "CodePrestation" => "KMS SUPP",
                "LibellePrestation" => 'Kilométage supplémentaire',
                "Montant" => $this->formatMontant($reservation->categorie->custom_fields->km_supplementaire),
                "Souscription" => true,
                "TypePrix" => '2',  // 1-Jour 2-Forfait f
                "Quantite" => '1',
                "Plafond" => 0
            ];
        }

        // FAIRE REMONTER D'AUTRE INFORMATION
        if($reservation->custom_fields->myrentcar_prestations){
            foreach ($reservation->custom_fields->myrentcar_prestations as $prestation) {
                $parameters["LignesPrix"][] = $prestation;
            }
        }


        if ($reservation->promotions and $reservation->promotions->totalReductions()) {
            $parameters["LignesPrix"][] = [
                "IsPec" => false,
                "CodeReservation" => null,
                "NumeroReservation" => null,
                "CodePrestation" => config('myrentcar.code_prestation_promotion'),
                "LibellePrestation" => Str::limit($reservation->promotions->implode('nom', ', '), 30, ''),
                "Montant" => -$this->formatMontant($reservation->promotions->totalReductions(), false),
                "Souscription" => true,
                "TypePrix" => '2',  // 1-Jour 2-Forfait f
                "Quantite" => '1',
                "Plafond" => 0
            ];
        }

        $paiement = $reservation->paiements()->ok()->first();
        if ($paiement and config('app.env') == 'production') {
            // Il n'est pas possible de supprimer un réglement dans Hitech, donc il faut mieux éviter de les envoyer.
            $parameters["Reglements"][] = [
                "IsPec" => false,
                "CodeDocument" => null,
                "TypeDocument" => "CD",
                "NumeroDocument" => null,
                "CleModeReglement" => config('myrentcar.mode_reglement'),
                "Libelle" => config('myrentcar.reglement_prefixe').$reservation->reference.' - régl #'.$paiement->id,
                "TypeReglement" => "R",
                "TypeTiers" => "C",
                "DateReglement" => $paiement->created_at->format('Y-m-d\TH:i:s'),
                "DateEcheance" => $paiement->created_at->format('Y-m-d\TH:i:s'),
                "Montant" => $paiement->montant,
                "RIB" => null,
                "Domiciliation" => null,
                "BIC" => null,
                "IBAN" => null,
                "MandatType" => null,
                "MandatDate" => null,
                "MandatNumero" => null
            ];
        }

        // Merge les params
        foreach ($params as $key => $value) {
            if (isset($parameters[$key])) {
                if (is_array($value)) {
                    foreach ($value as $subKey => $subValue) {
                        $parameters[$key][$subKey] = $subValue;
                    }
                } else {
                    $parameters[$key] = $value;
                }
            }
        }


        $resa = $this->api->post('Reservations/CreerReservation', $parameters);

        return $resa;

    }

    protected function formatMontant(string|float $montant, bool $has_taxe = true): float
    {
        $montant_ttc = str_replace(',', '.', $montant ?? 0);
        // Retourne un montant HT
        return $has_taxe ? $montant_ttc / (1 + (config('myrentcar.tva') / 100)) : $montant_ttc;

    }

    protected function getHitechCodeClient(\Ipsum\Reservation\app\Models\Reservation\Reservation $reservation): string|null
    {
        if (!$reservation->client or $reservation->client->custom_fields->hitech_code === null) {
            // Recherche Code client avec le même email
            // Attention problème dans le cas ou le client est suprimé dans Hitech
            $reservation_with_same_mail = \Ipsum\Reservation\app\Models\Reservation\Reservation::select('custom_fields')->whereRaw("LOWER(email) = LOWER('".$reservation->email."')")->whereNotNull('custom_fields->hitech_code_client')->orderBy('created_at', 'DESC')->first();
            $hitech_code_client = $reservation_with_same_mail?->custom_fields->hitech_code_client;
        } else {
            $hitech_code_client = $reservation->client->custom_fields->hitech_code;
        }
        return $hitech_code_client;
    }


    public function annuler($ID, string $motif = "Test site Internet")
    {
        return $this->api->put('Reservations/AnnulerReservation', [
            'ID' => $ID,
            'Motif' => $motif,
        ]);
    }

}
