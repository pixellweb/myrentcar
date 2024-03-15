<?php

namespace PixellWeb\Myrentcar\app\Ressources;

use GuzzleHttp\Psr7\MultipartStream;

class Document extends Ressource
{

    public function create(int $key, string $type, string $libelle, string $file) :array
    {
        // Ouvrir le fichier en lecture
        $fileHandle = fopen($file, 'r');
        // Créer un flux multipart contenant le fichier
        $multipartStream = new MultipartStream([
            [
                'name' => 'CleMaitre',
                'contents' => $key
            ],
            [
                'name' => 'TypeEntity',
                'contents' => $type
            ],
            [
                'name' => 'Libelle',
                'contents' => $libelle
            ],
            [
                'name' => 'File',
                'contents' => $fileHandle
            ]
        ]);

        return $this->api->post_multipart('Documents/InsertDocument', $multipartStream);

    }

}
