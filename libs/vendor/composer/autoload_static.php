<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit11039c49cc2b1e31cf4b1346e6cb8170
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Symfony\\Component\\EventDispatcher\\' => 34,
        ),
        'P' => 
        array (
            'PHPMailer\\PHPMailer\\' => 20,
        ),
        'E' => 
        array (
            'Emojione\\' => 9,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Symfony\\Component\\EventDispatcher\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/event-dispatcher',
        ),
        'PHPMailer\\PHPMailer\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpmailer/phpmailer/src',
        ),
        'Emojione\\' => 
        array (
            0 => __DIR__ . '/..' . '/emojione/emojione/lib/php/src',
        ),
    );

    public static $prefixesPsr0 = array (
        'Y' => 
        array (
            'Yandex\\Tests' => 
            array (
                0 => __DIR__ . '/..' . '/nixsolutions/yandex-php-library/tests',
            ),
            'Yandex' => 
            array (
                0 => __DIR__ . '/..' . '/nixsolutions/yandex-php-library/src',
            ),
        ),
        'G' => 
        array (
            'Guzzle\\Stream' => 
            array (
                0 => __DIR__ . '/..' . '/guzzle/stream',
            ),
            'Guzzle\\Service' => 
            array (
                0 => __DIR__ . '/..' . '/guzzle/service',
            ),
            'Guzzle\\Parser' => 
            array (
                0 => __DIR__ . '/..' . '/guzzle/parser',
            ),
            'Guzzle\\Inflection' => 
            array (
                0 => __DIR__ . '/..' . '/guzzle/inflection',
            ),
            'Guzzle\\Http' => 
            array (
                0 => __DIR__ . '/..' . '/guzzle/http',
            ),
            'Guzzle\\Common' => 
            array (
                0 => __DIR__ . '/..' . '/guzzle/common',
            ),
            'Guzzle\\Cache' => 
            array (
                0 => __DIR__ . '/..' . '/guzzle/cache',
            ),
        ),
    );

    public static $classMap = array (
        'RecursiveCallbackFilterIterator' => __DIR__ . '/..' . '/studio-42/elfinder/php/elFinderVolumeLocalFileSystem.class.php',
        'elFinder' => __DIR__ . '/..' . '/studio-42/elfinder/php/elFinder.class.php',
        'elFinderAbortException' => __DIR__ . '/..' . '/studio-42/elfinder/php/elFinder.class.php',
        'elFinderConnector' => __DIR__ . '/..' . '/studio-42/elfinder/php/elFinderConnector.class.php',
        'elFinderEditor' => __DIR__ . '/..' . '/studio-42/elfinder/php/editors/editor.php',
        'elFinderEditorOnlineConvert' => __DIR__ . '/..' . '/studio-42/elfinder/php/editors/OnlineConvert/editor.php',
        'elFinderEditorZipArchive' => __DIR__ . '/..' . '/studio-42/elfinder/php/editors/ZipArchive/editor.php',
        'elFinderEditorZohoOffice' => __DIR__ . '/..' . '/studio-42/elfinder/php/editors/ZohoOffice/editor.php',
        'elFinderLibGdBmp' => __DIR__ . '/..' . '/studio-42/elfinder/php/libs/GdBmp.php',
        'elFinderPlugin' => __DIR__ . '/..' . '/studio-42/elfinder/php/elFinderPlugin.php',
        'elFinderPluginAutoResize' => __DIR__ . '/..' . '/studio-42/elfinder/php/plugins/AutoResize/plugin.php',
        'elFinderPluginAutoRotate' => __DIR__ . '/..' . '/studio-42/elfinder/php/plugins/AutoRotate/plugin.php',
        'elFinderPluginNormalizer' => __DIR__ . '/..' . '/studio-42/elfinder/php/plugins/Normalizer/plugin.php',
        'elFinderPluginSanitizer' => __DIR__ . '/..' . '/studio-42/elfinder/php/plugins/Sanitizer/plugin.php',
        'elFinderPluginWatermark' => __DIR__ . '/..' . '/studio-42/elfinder/php/plugins/Watermark/plugin.php',
        'elFinderSession' => __DIR__ . '/..' . '/studio-42/elfinder/php/elFinderSession.php',
        'elFinderSessionInterface' => __DIR__ . '/..' . '/studio-42/elfinder/php/elFinderSessionInterface.php',
        'elFinderVolumeBox' => __DIR__ . '/..' . '/studio-42/elfinder/php/elFinderVolumeBox.class.php',
        'elFinderVolumeDriver' => __DIR__ . '/..' . '/studio-42/elfinder/php/elFinderVolumeDriver.class.php',
        'elFinderVolumeDropbox' => __DIR__ . '/..' . '/studio-42/elfinder/php/elFinderVolumeDropbox.class.php',
        'elFinderVolumeDropbox2' => __DIR__ . '/..' . '/studio-42/elfinder/php/elFinderVolumeDropbox2.class.php',
        'elFinderVolumeFTP' => __DIR__ . '/..' . '/studio-42/elfinder/php/elFinderVolumeFTP.class.php',
        'elFinderVolumeFlysystemGoogleDriveCache' => __DIR__ . '/..' . '/studio-42/elfinder/php/elFinderFlysystemGoogleDriveNetmount.php',
        'elFinderVolumeFlysystemGoogleDriveNetmount' => __DIR__ . '/..' . '/studio-42/elfinder/php/elFinderFlysystemGoogleDriveNetmount.php',
        'elFinderVolumeGoogleDrive' => __DIR__ . '/..' . '/studio-42/elfinder/php/elFinderVolumeGoogleDrive.class.php',
        'elFinderVolumeGroup' => __DIR__ . '/..' . '/studio-42/elfinder/php/elFinderVolumeGroup.class.php',
        'elFinderVolumeLocalFileSystem' => __DIR__ . '/..' . '/studio-42/elfinder/php/elFinderVolumeLocalFileSystem.class.php',
        'elFinderVolumeMySQL' => __DIR__ . '/..' . '/studio-42/elfinder/php/elFinderVolumeMySQL.class.php',
        'elFinderVolumeOneDrive' => __DIR__ . '/..' . '/studio-42/elfinder/php/elFinderVolumeOneDrive.class.php',
        'elFinderVolumeTrash' => __DIR__ . '/..' . '/studio-42/elfinder/php/elFinderVolumeTrash.class.php',
        'elFinderVolumeTrashMySQL' => __DIR__ . '/..' . '/studio-42/elfinder/php/elFinderVolumeTrashMySQL.class.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit11039c49cc2b1e31cf4b1346e6cb8170::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit11039c49cc2b1e31cf4b1346e6cb8170::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit11039c49cc2b1e31cf4b1346e6cb8170::$prefixesPsr0;
            $loader->classMap = ComposerStaticInit11039c49cc2b1e31cf4b1346e6cb8170::$classMap;

        }, null, ClassLoader::class);
    }
}
