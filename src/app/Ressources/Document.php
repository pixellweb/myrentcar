<?php

namespace PixellWeb\Myrentcar\app\Ressources;



class Document extends Ressource
{

    public function create(int $key, string $type, string $libelle, string $file) :array
    {
        return $this->api->post('Documents/InsertDocument',
            [
                'CleMaitre' => $key,
                'TypeEntity' => $type,
                'Libelle' => $libelle,
                'File' => base64_encode($file),
            ]
        );

    }

}
