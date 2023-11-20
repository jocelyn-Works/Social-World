<?php 

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;

class UploaderPostPicture {
    public function __construct(
        private Filesystem $fs,
        private $postFolderPublic,
        private $postFolder
    )
    {
        
    }
    public function uploadPostImage($picture) {
        // dossier ou sont stocker les images ( service.yaml )
        $folder = $this->postFolder;  
        // extension de l'image
        $ext = $picture->guessExtension() ?? 'bin'; 
        // nom de l'image
        $filename = bin2hex(random_bytes(10)) . '.' . $ext;
        // place l'image dans profileFolder avec le nom du fichier généré
        $picture->move($this->postFolder, $filename);
        // si ancienne image
        
        // le chemin du nouveau fichier
        return $this->postFolderPublic . '/' . $filename;
    }
}

?>