<?php

require_once __DIR__ . '/../repositories/MenuRepository.php';

class MenuService
{
    public function __construct(
        private MenuRepository $menuRepository
    ) {
    }

    public function saveMenu(array $post, array $files): array
    {
        $nom   = trim($post['nom']);
        $desc  = trim($post['description']);
        $prix  = (float) str_replace(',', '.', $post['prix']);
        $cat   = trim($post['categorie']);
        $dispo = isset($post['disponible']) ? 1 : 0;

        $image_url = trim($post['image_url'] ?? '');
        $msg = '';
        $msg_err = '';

        if (!empty($files['image_file']['name'])) {

            $file = $files['image_file'];

            $allowed = [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp'
            ];

            $maxsize = 3 * 1024 * 1024;

            if (!in_array($file['type'], $allowed)) {
                $msg_err = '❌ JPG/PNG/GIF/WEBP seulement';

            } elseif ($file['size'] > $maxsize) {
                $msg_err = '❌ Max 3 Mo';

            } else {

                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid('menu_') . '.' . strtolower($ext);
                $dest = dirname(__DIR__) . '/assets/uploads/' . $filename;

                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $image_url = 'assets/uploads/' . $filename;
                } else {
                    $msg_err = '❌ Permissions assets/uploads/ ?';
                }
            }
        }

        if (!$msg_err) {

            if (!empty($post['id'])) {

                if ($image_url === '') {
                    $image_url = $this->menuRepository->getImageUrl((int)$post['id']);
                }

                $this->menuRepository->update(
                    (int)$post['id'],
                    $nom,
                    $desc,
                    $prix,
                    $cat,
                    $dispo,
                    $image_url
                );

                $msg = '✅ Menu modifié !';

            } else {

                $this->menuRepository->create(
                    $nom,
                    $desc,
                    $prix,
                    $cat,
                    $dispo,
                    $image_url
                );

                $msg = '✅ Menu ajouté !';
            }
        }

        return [$msg, $msg_err];
    }
}