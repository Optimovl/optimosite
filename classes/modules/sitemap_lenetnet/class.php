<?php
    class sitemap_lenetnet extends def_module {

        public function __construct() {
            parent::__construct();  // Вызываем конструктор родительского класса def_module

            // В зависимости от режима работы системы, подключаем различные методы
            if(cmsController::getInstance()->getCurrentMode() == "admin") {
                // подгружаем файл с абстрактным классом __mymodule_adm для админки
                $this->__loadLib("__admin.php");
                // подключаем ("импортируем") методы класса __mymodule_adm
                // для расширения функционала в режиме администрирования
                $this->__implement("__sitemap_adm");
            } else {
                // подгружаем файл с абстрактным классом __custom_mymodule для клиентской части
                $this->__loadLib("__custom.php");
                // подключаем ("импортируем") методы класса __custom_mymodule
                // для расширения функционала в клиентском режиме
                $this->__implement("__custom_sitemap");
            }
        }

    };
?>