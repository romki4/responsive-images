<?php

namespace Romki4\ResponsiveImages;

use Illuminate\Support\Facades\Storage;
use Romki4\ResponsiveImages\Jobs\GenerateResponsiveImages;

class ResponsiveImages
{
    private static function getConfig($key)
    {
        return config('responsive-images.'. $key);
    }

    public static function getFileSystemDriver($driver)
    {
        if($driver){
            return $driver;
        }

        return config('responsive-images.driver');
    }

    public static function generate(
        $picture = null,
        $picture_title = 'Image',
        $size_pc = '380, 380',
        $size_tablet = '354, 354',
        $size_mobile = '290, 290',
        $mode = 'crop',
        $class_name = '',
        $lazyload = false,
        $driver = false
    )
    {
        $picture = self::isAbsoluteUrl($picture) ? self::getRelativeUrl($picture) : $picture;

        if(
            is_null($picture) ||
            is_array($picture) ||
            !Storage::disk(self::getFileSystemDriver($driver))->exists($picture)
        ){
            return false;
        }

        $size_pc = explode(',', preg_replace('/\s/', '', $size_pc));
        $size_tablet = explode(',', preg_replace('/\s/', '', $size_tablet));
        $size_mobile = explode(',', preg_replace('/\s/', '', $size_mobile));

        $arraySizes = self::makeSizesArray([
            'mobile' => $size_mobile,
            'tablet' => $size_tablet,
            'pc' => $size_pc
        ]);

        $result = '';

        $sizes = getimagesize(Storage::disk(self::getFileSystemDriver($driver))->path($picture));
        $width = ($sizes && count($sizes) && $sizes[0]) ? $sizes[0] : $size_pc[0];
        $height = ($sizes && count($sizes) && $sizes[1]) ? $sizes[1] : $size_pc[1];

        $currentMimeType = Storage::disk(self::getFileSystemDriver($driver))->mimeType($picture);

        if (
            $currentMimeType == 'image/svg+xml' ||
            $currentMimeType == 'image/svg' ||
            $currentMimeType == 'text/html'
        ){
            if ($lazyload) {
                $result .= '<img class="' . $class_name . '"
                    data-src="'.url($picture). '"
                        width="'.$width.'"
                        height="'.$height.'"
                        loading="lazy"
                    alt="' . $picture_title . '"
                    fetchpriority="low">';
            } else {
                $result .= '<img class="' . $class_name . '"
                    src="'. url($picture) . '"
                        width="'.$width.'"
                        height="'.$height.'"
                    alt="' . $picture_title . '"
                    fetchpriority="low">';
            }
        } else {

            if ($currentMimeType != 'image/gif') {

                $images = self::getImagePath($picture, $arraySizes, $mode, $driver);

                if(count($images)){
                    foreach ($images as $type => $image){
                        if($type == 'png' || isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'image/'.$type) >= 0){
                            $result .= '<source srcset="
                                    '.str_replace(' ','%20', url($image['mobile_x2'])) . ' 2x,
                                    '.str_replace(' ','%20', url($image['mobile'])) .' 1x"
                                    media="(max-width: 480px)" type="image/'. $type .'">';
                            $result .= '<source srcset="
                                    ' . str_replace(' ', '%20', url($image['tablet_x2'])) . ' 2x,
                                    ' . str_replace(' ', '%20', url($image['tablet'])) . ' 1x"
                                    media="(max-width: 992px)" type="image/'. $type .'">';
                            $result .= '<source srcset="
                                    ' . str_replace(' ', '%20', url($image['pc_x2'])) . ' 2x,
                                    ' . str_replace(' ', '%20', url($image['pc'])) . ' 1x
                                    " media="(min-width: 993px)" type="image/'. $type .'">';
                        }
                    }
                }

            } else {
                $result .= '<source srcset="'.str_replace(' ','%20', url($picture)) . '">';
            }

            if(isset($images['png']['mobile'])){
                $picture = Storage::disk(self::getFileSystemDriver($driver))->path($images['png']['mobile']);
                $sizes = getimagesize($picture);
                $calculatedMinWidth = ($sizes && count($sizes) && $sizes[0]) ? $sizes[0] : $size_pc[0];
                $calculatedMinHeight = ($sizes && count($sizes) && $sizes[1]) ? $sizes[1] : $size_pc[1];
            }else{
                $calculatedMinWidth = $arraySizes['mobile']['width'];
                $calculatedMinHeight = intval(($calculatedMinWidth / $width) * $height);
            }

            $result .= '<img class="' . $class_name . '"
                    src="'.str_replace(' ','%20', url($picture)) . '"
                        width="'.$calculatedMinWidth.'"
                        height="'.$calculatedMinHeight.'"
                    alt="' . $picture_title . '"
                    fetchpriority="low">';
        }

        return '<picture>'. $result. '</picture>';
    }

    private static function isAbsoluteUrl($url)
    {
        $pattern = "/^(?:ftp|https?|feed)?:?\/\/(?:(?:(?:[\w\.\-\+!$&'\(\)*\+,;=]|%[0-9a-f]{2})+:)*
        (?:[\w\.\-\+%!$&'\(\)*\+,;=]|%[0-9a-f]{2})+@)?(?:
        (?:[a-z0-9\-\.]|%[0-9a-f]{2})+|(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\]))(?::[0-9]+)?(?:[\/|\?]
        (?:[\w#!:\.\?\+\|=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})*)?$/xi";
        return (bool) preg_match($pattern, $url);
    }

    private static function getRelativeUrl($url) {
        $url = str_replace(config('app.url'), '', $url);
        if (!empty($url) && $url[0] == '/') {
            $url = ltrim($url, '/');
        }
        return $url;
    }

    private static function getImagePath($path, $sizes, $mode, $driver)
    {
        $path = Storage::disk(self::getFileSystemDriver($driver))->path($path);

        $original = $path;
        $slices = explode('/', $path);
        $filename = array_pop($slices);

        $mask = dirname($path).'/%s-%s/%s/%s';

        $images = [];
        $imagesNotExist = [];

        foreach (self::getConfig('mime_types') as $type){
            foreach ($sizes as $s => $size){

                $imagename = pathinfo($filename, PATHINFO_FILENAME).'.'. $type;

                $filePath = sprintf($mask, $size['width'] ?? 'auto', $size['height'] ?? 'auto', $mode, $imagename);
                $images[$type][$s] = self::generateDestinationPath($filePath, $driver);

                if(!Storage::disk(self::getFileSystemDriver($driver))->exists($images[$type][$s])){
                    $imagesNotExist[$type][$s] = $images[$type][$s];
                }
            }
        }

        if(count($imagesNotExist)){
            dispatch(new GenerateResponsiveImages($original, $imagesNotExist, $sizes, $driver));
        }

        return $images;
    }

    private static function generateDestinationPath($path, $driver)
    {
        return str_replace(
            Storage::disk(self::getFileSystemDriver($driver))->path(''),
            rtrim(self::getConfig('destination'), '/') . '/',
            $path
        );
    }

    private static function makeSizesArray($array)
    {
        $result = [];

        foreach ($array as $key => $item) {
            $width = is_numeric($item[0]) ? $item[0] : null;
            $height = is_numeric($item[1]) ? $item[1] : null;

            $result[$key] = [
                'width'  => $width,
                'height' => $height
            ];
            $result[$key.'_x2'] = [
                'width'  => $width ? $width * 2 : null,
                'height' => $height ? $height * 2 : null
            ];
        }

        return $result;
    }
}
