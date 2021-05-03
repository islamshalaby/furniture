<?php

if (! function_exists('cloudinary_app_name')) {
    function cloudinary_app_name() {
        $cloudName = env('CLOUDINARY_CLOUD_NAME');
        return $cloudName;
    }
}