<?php

namespace PixellWeb\Myrentcar\app\Console\Commands;


use App\Hitechservices\Exception;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Ipsum\Reservation\app\Models\Categorie\Categorie;
use Ipsum\Reservation\app\Models\Tarif\Duree;
use Ipsum\Reservation\app\Models\Tarif\Saison;
use PixellWeb\Myrentcar\app\Ressources\Tarif;

class ImportTarif extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'myrentcar:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importation des tarifs Myrentcar';


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $saisons = Saison::where('fin_at', '>=', Carbon::now()->startOfDay())->get();
        $this->info('Importation Hitech de '.$saisons->count().' saison(s)');

        $durees = Duree::whereNull('type')->get();
        $categories = Categorie::all();

        $cats = $categories->map(function ($item, $key) {
            return $item->custom_fields->hitech_code;
        });

        $hitech_tarif = new Tarif();

        foreach ($saisons as $saison) {
            $tarifs = [];
            foreach($saison->tarifs as $tarif) {
                $tarifs[$tarif->categorie_id][$tarif->duree_id] = $tarif;
            }

            foreach ($durees as $duree) {

                try {

                    $nombre_jour = $duree->nom == 'Forfait weekend' ? 7 : $duree->min;

                    $tarifs_hitech = $hitech_tarif->get($saison->custom_fields->hitech_code, $nombre_jour, $cats->toArray());

                    foreach ($categories as $categorie) {

                        if (isset($tarifs[$categorie->id][$duree->id])) {
                            $tarif = $tarifs[$categorie->id][$duree->id];
                        } else {
                            $tarif = new \Ipsum\Reservation\app\Models\Tarif\Tarif();
                            $tarif->categorie_id = $categorie->id;
                            $tarif->duree_id = $duree->id;
                        }
                        $tarif->montant = isset($tarifs_hitech[$categorie->custom_fields->hitech_code]) ? $tarifs_hitech[$categorie->custom_fields->hitech_code] / $nombre_jour : null;
                        if ($duree->id == 4 and $tarif->montant !== null) {
                            // forfait
                            $tarif->montant = $tarif->montant * 2;
                        }
                        $tarif->saison_id = $saison->id;
                        $tarif->condition_paiement_id = null;
                        $tarif->save();

                    }
                } catch (Exception $exception) {
                    $this->error($exception->getMessage());
                }

            }
        }

        return Command::SUCCESS;
    }



}
