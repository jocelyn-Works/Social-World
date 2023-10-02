<?php 

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;

class UploaderPicture {
    public function __construct(
        private Filesystem $fs,
        private $profileFolderPublic,
        private $profileFolder
    )
    {
        
    }
    public function uploadProfilImage($picture, $oldPicture = null) {
        // dossier ou sont stocker les images ( service.yaml )
        $folder = $this->profileFolder;  
        // extension de l'image
        $ext = $picture->guessExtension() ?? 'bin'; 
        // nom de l'image
        $filename = bin2hex(random_bytes(10)) . '.' . $ext;
        // place l'image dans profileFolder avec le nom du fichier généré
        $picture->move($this->profileFolder, $filename);
        // si ancienne image
        if($oldPicture) {
            // on suprime l'ancienne image 
            $this->fs->remove($folder. '/' .pathinfo($oldPicture, PATHINFO_BASENAME));
            // BASE_NAME, pour récupérer juste le fichier et son extension
        }
        // le chemin du nouveau fichier
        return $this->profileFolderPublic . '/' . $filename;
    }
}

?>