<?php

define('PUBLIC_DIR', realpath(dirname(__FILE__)));
define('ROOT_DIR', dirname(PUBLIC_DIR));
define('LIB_DIR', ROOT_DIR . '/lib');
define('CONFIG_DIR', ROOT_DIR . '/config');

require_once LIB_DIR . '/exceptions.php';
require_once LIB_DIR . '/config.php';
require_once LIB_DIR . '/storage.php';

try {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $hash = null;
        $storage = null;

        if (!empty($_REQUEST['hash'])) {
            $hash = $_REQUEST['hash'];
            try {
                $storage = Storage::get($hash, true);
            } catch (StorageException $e) {
                $hash = null;
            }
        }

        if (!$hash) {
            $attribution = empty($_POST['attribution']) ? '' : $_POST['attribution'];
            $attribution = substr(trim($attribution), 0, get_config('ATTRIBUTION_MAX_LEN'));
            if (!$attribution) {
                throw new BadRequestException("An attribution is required");
            }
            $hash = sha1($attribution);
            $storage = Storage::get($hash, false, true, $attribution);
        }

        $storage->put_uploaded_file('file');

        echo $hash;
    } else {
        $attribution = '';
        $hash = empty($_GET['hash']) ? null : $_GET['hash'];
        if ($hash) {
            $storage = Storage::get($hash, false, false);
            $attribution = trim($storage->attribution) ?: '';
        }
        ?><!DOCTYPE html>

        <html>
            <head>
                <title><?= get_config('SITE_TITLE'); ?></title>
                <link rel="stylesheet" type="text/css" href="deaddrop.css" />
                <link rel="stylesheet" type="text/css" href="dropzone.css" />
            </head>
            <body>
                <div class="wrapper">
                    <form>
                        <div class="title">
                            <h1><?= get_config('SITE_TITLE'); ?></h1>
                        </div>
                        <div class="attribution">
                            <label for="attribution">Attribution Information</label>
                            <p><?= get_config('ATTRIBUTION_HELP'); ?></p>
                            <textarea id="attribution" name="attribution"><?= $attribution; ?></textarea>
                        </div>
                        <div id="alert-container"></div>
                        <div id="dropzone" class="dropzone"></div>
                    </form>
                </div>

                <script>
                    window.deaddrop = {
                        dropzone_config: {
                            parallelUploads: <?= get_config('DROPZONE_PARALLEL_UPLOADS'); ?>,
                            maxFilesize: <?= get_config('DROPZONE_MAX_FILESIZE'); ?>,
                        },
                        hash: <?= $hash ? "'" . $hash . "'" : 'null'; ?>,
                    };
                </script>

                <script src="jquery.js"></script>
                <script src="dropzone.js"></script>
                <script src="deaddrop.js"></script>
            </body>
        </html>
        <?php
    }
} catch (HTTPException $e) {
    http_response_code($e->getCode() ?: 500);
    echo $e->getMessage() ?: 'Internal Server Error';
}