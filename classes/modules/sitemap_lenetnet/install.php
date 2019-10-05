<?php

    $INFO = Array();

    $INFO['name'] = "sitemap_lenetnet";
    $INFO['filename'] = "modules/sitemap_lenetnet/class.php";
    $INFO['config'] = "0";
    $INFO['ico'] = "sitemap_lenetnet";
    $INFO['default_method'] = "page";
    $INFO['default_method_admin'] = "sitemap";

    $INFO['func_perms'] = "";
    $INFO['func_perms/admin'] = "Администрирование модуля";

    $COMPONENTS = array();

    $COMPONENTS[0] = "./classes/modules/sitemap_lenetnet/class.php";
    $COMPONENTS[1] = "./classes/modules/sitemap_lenetnet/__admin.php";
    $COMPONENTS[3] = "./classes/modules/sitemap_lenetnet/i18n.php";
    $COMPONENTS[6] = "./classes/modules/sitemap_lenetnet/permissions.php";

?>