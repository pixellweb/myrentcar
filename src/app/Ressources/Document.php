<?php

namespace PixellWeb\Myrentcar\app\Ressources;

use GuzzleHttp\Psr7\MultipartStream;

class Document extends Ressource
{

    public function create(int $key, string $type, string $libelle, string $file) :array
    {
        // Ouvrir le fichier en lecture
        $extension = pathinfo($file, PATHINFO_EXTENSION);
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
                'contents' => $fileHandle,
                'filename' => basename($file) . '.' . $extension,
                'headers' => [
                    'Content-Type' => mime_content_type($file)
                ]
            ]
        ]);

        return $this->api->post_multipart('Documents/InsertDocument', $multipartStream);

    }

}
