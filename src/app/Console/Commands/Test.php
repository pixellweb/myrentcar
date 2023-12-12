<?php

namespace PixellWeb\Myrentcar\app\Console\Commands;


use Carbon\Carbon;
use Illuminate\Console\Command;
use PixellWeb\Myrentcar\app\Api;
use PixellWeb\Myrentcar\app\Ressources\Categorie;
use PixellWeb\Myrentcar\app\Ressources\Reservation;
use PixellWeb\Myrentcar\app\Ressources\Tarif;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'myrentcar:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'test myrentcar';


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

        $categorie = new Categorie();
        dd($categorie->immatriculationDisponible(Carbon::now()->addDay(), Carbon::now()->addDays(7), 'SIEGE', 'B'));
        dd($categorie->liste());

        $reservation = new Reservation();
        dd($reservation->create(\Ipsum\Reservation\app\Models\Reservation\Reservation::find(11)));
        dd($reservation->annuler(1803 ));
        //dd($reservation->get(1764 ));


        $categorie = new Categorie();
        dd($categorie->immatriculationDisponible(Carbon::now()->addDay(), Carbon::now()->addDays(7), 'SIEGE', 'B'));
        dd($categorie->liste());


        $api = new Api();
        dd($api->logout());

        $tarif = new Tarif();
        dd($tarif->get('BASSE SAISON', 7, ['B', 'C']));







        $api = new Api();
        //dd($api->get('Reservations/GetVehiculesDispoSurAgence', ['debut'=>'2023-12-08T11:00:00', 'fin'=>'2024-12-09T11:00:00']));
        dd($api->get('Values/GetCategories'));



    }



}
