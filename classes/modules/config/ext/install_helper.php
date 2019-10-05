<?php

/**
 * addFields
 *
 * Создает и добавляет свойства (поля) в указанную группу
 *
 * @param umiFieldGroup $group объект группы, к которой необхдимо добавить свойства
 * @param array $fields массив свойств, которые необходимо добавить:
 * 
 * 'name' => Имя свойства (строковой идентификатор)
 * 
 * 'title' => Название свойства,
 * 
 * 'is_visible' => Видимое (0 или 1)
 * 
 * 'tip' => Подсказка к названию свойства,
 * 
 * 'is_in_filter' => Фильтруемое (0 или 1),
 * 
 * 'is_in_search' => Участвует в поиске (0 или 1),
 * 
 * 'is_required' => Обязательное (0 или 1),
 * 
 * 'field_type' => Тип поля (boolean, counter, date, file, float, img_file, int, optioned, password, price, relation, multiple_relation, string, swf_file, symlink, tags, text, video_file, wysiwyg),
 * 
 * 'guide_id' => ID справочника, если field_type = relation, или multiple_relation
 *
 */
function addFields($group, $fields) {
    $fc = umiFieldsCollection::getInstance();

    $field_types = array();
    $_field_types = umiFieldTypesCollection::getInstance()->getFieldTypesList();
    foreach ($_field_types as $field_type) {
        $data_type = $field_type->getDataType();
        $data_type = ($data_type == 'relation' && $field_type->getIsMultiple()) ? 'multiple_relation' : $data_type;
        $field_types[$data_type] = $field_type->getId();
    }

    foreach ($fields as $field) {
        if (isset($field_types[$field['field_type']])) {
            $field_type_id = $field_types[$field['field_type']];
        } else {
            throw new publicAdminException('Ошибка в функции addFields: передан некорректный field_type_id - ' . $field['field_type']);
        }

        $field_id = $fc->addField($field['name'], $field['title'], $field_type_id, $field['is_visible']);
        $field_obj = $fc->getField($field_id);
        if (is_object($field_obj)) {
            $field_obj->setIsInFilter($field['is_in_filter']);
            $field_obj->setIsInSearch($field['is_in_search']);
            $field_obj->setIsRequired($field['is_required']);
            if ($field['field_type'] == 'relation' || $field['field_type'] == 'multiple_relation') {
                if (isset($field['guide_id'])) {
                    $field_obj->setGuideId($field['guide_id']);
                } else {
                    throw new publicAdminException('Ошибка в функции addFields: не передан guide_id для поля ' . $field['name']);
                }
            }
            if (isset($field['tip']) && strlen($field['tip'])) {
                $field_obj->setTip($field['tip']);
            }
            $group->attachField($field_id);
        }
    }
    return 1;
}

/**
 * addGroups
 *
 * Создает и добавляет группы в указанный тип данных
 *
 * @param umiFieldType $type объект типа, к которому необхдимо добавить группы
 * @param array $groups массив групп, которые необходимо добавить:
 * 
 * 'name' => Имя группы (строковой идентификатор)
 * 
 * 'title' => Название группы,
 * 
 * 'is_visible' => Видимая (0 или 1)
 * 
 * 'fields' => Массив полей (описан в параметре fields функции addField)
 *
 */
function addGroups($type, $groups) {
    foreach ($groups as $group_info) {
        $type->addFieldsGroup($group_info['name'], $group_info['title'], 1, $group_info['is_visible']);
        $group = $type->getFieldsGroupByName($group_info['name']);
        if (is_object($group)) {
            addFields($group, $group_info['fields']);
        }
    }
    return 1;
}

/**
 * createType
 *
 * Создает тип данных, группы и свойства (поля)
 *
 * @param array $typeData массив, содержащий информацию о добавляемом типе даннвх:
 * 
 * 'regedit_section_name' => Имя модуля (строковой идентификатор) данных для секции в реестре (//modules/%name%/)
 * 
 * 'hierarchy_type_guid' => Имя иерархического типа (строковой идентификатор) для секции в реестре (//modules/somemodule/%name%), передается если параметр $need_hierarchy_type = 1
 * 
 * 'hierarchy_type_title' => Название иерархического типа, передается если параметр $need_hierarchy_type = 1
 * 
 * 'hierarchy_type_method' => Название функции по умолчанию для иерархического типа, передается если параметр $need_hierarchy_type = 1
 * 
 * 'object_type_guid' => Имя объектного типа (строковой идентификатор) для секции в реестре (//modules/somemodule/%name%)
 * 
 * 'object_type_title' => Название объектного типа,
 * 
 * 'groups' => Массив групп (описан в параметре groups функции addGroup)
 *
 * @param boolean $delete_if_exists удалять тип, если он существует (0 или 1, по умолчанию 1)
 */
function createType($typeData, $delete_if_exists = 1) {
    $regedit = regedit::getInstance();
    $otc = umiObjectTypesCollection::getInstance();
    $htc = umiHierarchyTypesCollection::getInstance();
    $fc = umiFieldsCollection::getInstance();
    $oc = umiObjectsCollection::getInstance();
    $fc = umiFieldsCollection::getInstance();

    if (isset($typeData['hierarchy_type_guid']) && isset($typeData['hierarchy_type_title']) && isset($typeData['hierarchy_type_method'])) {
        $hTypeId = $regedit->getVal("//modules/" . $typeData['regedit_section_name'] . "/" . $typeData['hierarchy_type_guid'] . "_htype_id");

        if ($hTypeId > 0 && $delete_if_exists) {
            $htc->delType($hTypeId);
            $hTypeId = 0;
        }

        if (!$hTypeId || !$htc->getType($hTypeId)) {
            $hTypeId = $htc->addType($typeData['regedit_section_name'], $typeData['hierarchy_type_title'], $typeData['hierarchy_type_method']);
            if ($hTypeId) {
                $regedit->setVal("//modules/" . $typeData['regedit_section_name'] . "/" . $typeData['hierarchy_type_guid'] . "_htype_id", $hTypeId);
            }
        }
    } else {
        $hTypeId = -1;
    }


    $oTypeId = $regedit->getVal("//modules/" . $typeData['regedit_section_name'] . "/" . $typeData['object_type_guid'] . "_otype_id");
    if ($oTypeId > 0 && $delete_if_exists) {
        $oType = $otc->getType($oTypeId);
        if (is_object($oType)) {
            $fields = $oType->getAllFields();
            if (is_array($fields) && count($fields)) {
                foreach ($fields as $field) {
                    $fc->delField($field->getId());
                }
            }
            $otc->delType($oTypeId);
            $oTypeId = 0;
            $oType = null;
        }
    }

    if (!$oTypeId || !$otc->getType($oTypeId)) {
        $oTypeId = $otc->addType(0, $typeData['object_type_title'], 0);
        if ($oTypeId) {
            $regedit->setVal("//modules/" . $typeData['regedit_section_name'] . "/" . $typeData['object_type_guid'] . "_otype_id", $oTypeId);
            $oType = $otc->getType($oTypeId);
            if (is_object($oType)) {
                if ($hTypeId > 0) {
                    $oType->setHierarchyTypeId($hTypeId);
                }

                return addGroups($oType, $typeData['groups']);
            }
        }
    }
}

function registrateModule($name, $ext = '', $dont_cache_methods = array()) {
    $regedit = regedit::getInstance();

    $ext_regedit = '';
    if ($ext != '') {
        $ext_regedit = $ext . '_';
    }

    if (!$regedit->getVal("//modules/{$name}/{$ext_regedit}is_reg/")) {

        $ctx = stream_context_create(array('http' =>
            array(
                'timeout' => 10,
            )
        ));

        $ip = '';
        if (function_exists('gethostbyname') && function_exists('gethostname')) {
            $ip = gethostbyname(gethostname());
        }

        $host = $_SERVER['HTTP_HOST'];

        $_ext = '';
        if ($ext != '') {
            $_ext = '_' . $ext;
        }

        $url = "http://api.clean-code.ru/umimarket.php?module={$name}{$_ext}&host={$host}&ip={$ip}";

        if (function_exists('curl_version')) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
            curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.16) Gecko/20110319 Firefox/3.6.16");
            $response = intval(trim(curl_exec($curl)));
        } else {
            $response = intval(trim(@file_get_contents($url, false, $ctx)));
        }

        $regedit->setVal("//modules/{$name}/{$ext_regedit}is_reg/", 1);

        $_dont_cache_methods = array();
        if (is_array($dont_cache_methods) && count($dont_cache_methods)) {
            $_dont_cache_methods = $dont_cache_methods;
        } else {
            if (file_exists(dirname(__FILE__) . "/permissions.{$ext}.php") || file_exists(dirname(__FILE__) . "/permissions.php")) {
                if ($ext) {
                    include_once dirname(__FILE__) . "/permissions.{$ext}.php";
                } else {
                    include_once dirname(__FILE__) . "/permissions.php";
                }

                if (isset($permissions) && count($permissions)) {
                    foreach ($permissions as $method_category) {
                        foreach ($method_category as $method) {
                            $_dont_cache_methods[] = "{$name}/{$method}";
                        }
                    }
                }
            }
        }

        if (count($_dont_cache_methods)) {
            $config = mainConfiguration::getInstance();
            $not_allowed_methods = $config->get('cache', 'not-allowed-methods');
            $not_allowed_methods = array_unique(array_merge($not_allowed_methods, $_dont_cache_methods));
            $config->set('cache', 'not-allowed-methods', $not_allowed_methods);
        }

        return 1;
    }
    return 0;
}