<?php

namespace PixellWeb\Myrentcar\app\Ressources;

use Carbon\CarbonInterface;
use Ipsum\Reservation\app\Location\Prestation;
use Ipsum\Reservation\app\Models\Tarif\Saison;

class Reservation extends Ressource
{

    public function get(int $id)
    {
        return $this->api->get('Reservations/GetReservation', ['id' => $id]);
    }


    public function create(\Ipsum\Reservation\app\Models\Reservation\Reservation $reservation) :array
    {

        $is_mobile = in_array(substr($reservation->telephone, 0, 2), ['06', '07']);

        $saison = Saison::betweenDates($reservation->debut_at, $reservation->fin_at)->first();

        $categorie = new Categorie();
        $immatriculation = $categorie->immatriculationDisponible($reservation->debut_at, $reservation->fin_at, $reservation->lieuDebut->custom_fields->hitech_code, $reservation->categorie->custom_fields->hitech_code);

        $parameters = [
            "Depart" => $reservation->debut_at->format('Y-m-d\TH:i:s'),
            "CodeAgenceDepart" => $reservation->lieuDebut->custom_fields->hitech_code,
            "LieuDepart" => $reservation->observation.($reservation->custom_fields->vol ? ' vol : '.$reservation->custom_fields->vol : ''),
            "Retour" => $reservation->fin_at->format('Y-m-d\TH:i:s'),
            "CodeAgenceRetour" => $reservation->lieuFin->custom_fields->hitech_code,
            "LieuRetour" => null,
            "CodeAgenceTravail" => $reservation->lieuDebut->custom_fields->hitech_code, // Agence origine ?
            "KmEstime" => 0,
            "CodeCategorie" => $reservation->categorie->custom_fields->hitech_code,
            "Note" => 'Réservation '. parse_url(config('app.url'), PHP_URL_HOST).' : '.$reservation->reference,
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
                "DateNaissance" => $reservation->naissance_at?->format('d/m/Y'),
                "ReponsesConducteur1" => null,
                "ReponsesConducteur2" => null,
                "ReponsesClient" => null,
                "Civilite" => null,
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
                    "Montant" => $this->formatMontant(($reservation->montant_base - $reservation->promotions->totalReductions())  / $reservation->nb_jours),
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
                    "LibellePrestation" => $prestation->tarification != "agence" ? $prestations->find($prestation->id)->custom_fields->hitech_code : $prestation->nom.' (en agence)',
                    "Montant" => $this->formatMontant($prestation->tarif),
                    "Souscription" => true,
                    "TypePrix" => '2',  // 1-Jour 2-Forfait f
                    "Quantite" => $prestation->quantite,
                    "Plafond" => 0
                ];

            }
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
                "Libelle" => 'Réglement #'.$paiement->id,
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

        $resa = $this->api->post('Reservations/CreerReservation', $parameters);

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

    protected function formatMontant(string|float $montant): float
    {
        $montant_ttc = str_replace(',', '.', $montant ?? 0);
        // Retourne un montant HT
        return $montant_ttc / (1 + (config('myrentcar.tva') / 100));

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
