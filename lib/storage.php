<?php

class Storage {
    public $directory = null;
    public $attribution = null;
    public $request_data = null;

    protected function __construct($root, $prefix = null, $require_exists = false, $make_prefixed = true, $attribution = null) {
        $root = rtrim($root, '/') . '/';
        $this->directory = $root;
        if (!file_exists($this->directory)) {
            if (!get_config('MAKE_STORAGE_DIR', false)) {
                throw new StorageException("Won't create the storage directory " . $this->directory);
            }
            if (!mkdir($this->directory, 0770, true)) {
                throw new StorageException("Can't create the storage directory " . $this->directory);
            }
        }

        $this->directory .= trim($prefix ?: '', '/');
        $this->directory = rtrim($this->directory, '/') . '/';
        if (!file_exists($this->directory)) {
            if ($this->directory == $root) {
                throw new StorageException("Storage dir is root but doesn't exist, this is a problem");
            }
            if ($require_exists) {
                throw new StorageException("Can't find the prefixed storage directory " . $this->directory);
            }
            if ($make_prefixed) {
                if (!mkdir($this->directory, 0770, true)) {
                    throw new StorageException("Can't create the prefixed storage directory " . $this->directory);
                }
            }
        }

        $attribution_file = $this->get_attribution_filename();
        if (file_exists($attribution_file)) {
            $sep = get_config('ATTRIBUTION_SEPARATOR') . "\n";
            list($this->attribution, $this->request_data) = explode($sep, file_get_contents($attribution_file));
            $this->attribution = trim($this->attribution);
            $this->request_data = json_decode(trim($this->request_data));
        }

        if ($require_exists && !($this->attribution && $this->request_data)) {
            throw new StorageException("Can't load the attribution from " . $attribution_file);
        }

        if ($attribution) {
            $this->set_attribution($attribution);
        }
    }

    public static function get($prefix = null, $require_exists = false, $make_prefixed = true, $attribution = null) {
        return new self(get_config('STORAGE_DIR'), $prefix, $require_exists, $make_prefixed, $attribution);
    }

    // public static function get_all($pre_prefix = null, $prefix_mask = '*') {
    //     foreach (glob(trim(get_config('STORAGE_DIR') . '/' . trim($pre_prefix ?: '', '/'), '/') . '/' . $prefix_mask) as $dir) {
    //         if (file_exists($dir) && is_dir($dir)) {
    //             yield self::get(trim(trim($pre_prefix ?: '', '/') . '/' . basename(rtrim($dir, '/')), '/'), false, false);
    //         }
    //     }
    // }

    protected function get_attribution_filename() {
        return $this->directory . get_config('ATTRIBUTION_FILENAME');
    }

    public function set_attribution($attribution) {
        $this->attribution = $attribution;
        file_put_contents(
            $this->get_attribution_filename(),
            $this->attribution . "\n" . get_config('ATTRIBUTION_SEPARATOR') . "\n" . json_encode($_SERVER, JSON_PRETTY_PRINT)
        );
    }

    protected function get_extension_by_mime($filename, $real_name = null) {
        $mimes = array_merge(
            get_config('MIME_BASE', array()),
            get_config('MIME_CUSTOM', array())
        );
        $mime_overrides = array_merge(
            get_config('MIME_OVERRIDE_BASE', array()),
            get_config('MIME_OVERRIDE_CUSTOM', array())
        );

        $mime = mime_content_type($filename);
        if (!empty($mime_overrides[$mime])) {
            $ext = explode('.', $real_name ?: $filename);
            $ext = '.' . strtolower(array_pop($ext));
            if (!empty($mime_overrides[$mime][$ext])) {
                $mime = $mime_overrides[$mime][$ext];
            }
        }

        if (empty($mimes[$mime])) {
            throw new BadRequestException("Invalid file type");
        }

        return $mimes[$mime];
    }

    public function put_uploaded_file($file_key) {
        if (!array_key_exists($file_key, $_FILES)) {
            throw new BadRequestException("Missing file upload");
        }

        $file = $_FILES[$file_key];

        if ($file['size'] > (get_config('DROPZONE_MAX_FILESIZE') * 1048576)) {
            throw new BadRequestException("The file is too large");
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new BadRequestException("The file is too large");
                break;
            case UPLOAD_ERR_PARTIAL:
                throw new BadRequestException("The file did not upload successfully");
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new BadRequestException("No file was uploaded");
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
            case UPLOAD_ERR_CANT_WRITE:
            case UPLOAD_ERR_EXTENSION:
            default:
                throw new HTTPError("The file could not be uploaded");
                break;
        }

        $ext = $this->get_extension_by_mime($file['tmp_name'], $file['name']);
        $fname = sha1_file($file['tmp_name']) . $ext;
        $dest = $this->directory . $fname;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new HTTPError("The file could not be uploaded");
        }
    }
}