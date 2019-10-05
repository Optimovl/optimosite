/**
 * Класс рекурсивного оператора над деревом страниц.
 * Класс отвечает за:
 *
 * 1) Включение активности;
 * 2) Выключение активности;
 * 3) Помещение страниц в корзину;
 * 4) Восстановление страниц из корзины;
 * 5) Удаление страниц;
 *
 * @param {Control} Control контрол для управления страницами
 * @constructor
 */
function PageTreeRecursiveOperator(Control) {

	/** @var {Boolean} isProgressWindowShown флаг факта отображения окна с прелоудером */
	var isProgressWindowShown = false;

	/** @var {Object} parentIdQueue очередь обрабатываемых родительских страниц */
	var parentIdQueue = {};

	/** @var {Integer} CHILDREN_PART_LIMIT ограничение на размер части списка страниц */
	var CHILDREN_PART_LIMIT = 50;

	/** @var {Integer} FUNCTION_EXECUTION_TIMEOUT значение таймаута по умолчанию для отложенного выполнения функции */
	var FUNCTION_EXECUTION_TIMEOUT = 50;

	/** Кастомный контруктор */
	this.init = function() {
		bindRepetition(this);
	};

	/**
	 * Переключает активность у списка родительских страниц и их детей
	 * @param {Array|Integer} idOrList идентификатор или список идентификаторов родительских страниц
	 * @param {Boolean} targetStatus целевой статус активности
	 */
	this.switchActivity = function(idOrList, targetStatus) {
		targetStatus ? this.activate(idOrList) : this.deactivate(idOrList);
	};

	/**
	 * Включает активность у списка родительских страниц и их детей
	 * @param {Array|Integer} idOrList идентификатор или список идентификаторов родительских страниц
	 */
	this.activate = function(idOrList) {
		var parentIdList = getParentIdList(idOrList);
		parentIdList.forEach(pushParentId);
		parentIdList.forEach(activateTree);
	};

	/**
	 * Выключает активность у списка родительских страниц и их детей
	 * @param {Array|Integer} idOrList идентификатор или список идентификаторов родительских страниц
	 */
	this.deactivate = function(idOrList) {
		var parentIdList = getParentIdList(idOrList);
		parentIdList.forEach(pushParentId);
		parentIdList.forEach(deactivateTree);
	};

	/**
	 * Помещает список родительских страниц и их детей в корзину
	 * @param {Array|Integer} idOrList идентификатор или список идентификаторов родительских страниц
	 * @param {Boolean} showConfirmation необходимо ли показывать подтверждение
	 */
	this.putToTrash = function(idOrList, showConfirmation) {
		var parentIdList = getParentIdList(idOrList);
		parentIdList.forEach(pushParentId);

		function putToTrash() {
			parentIdList.forEach(putTreeToTrash);
		}

		showConfirmation ? showDeleteConfirmation(putToTrash) : putToTrash();
	};

	/**
	 * Восстанавливает список родительских страниц и их детей из корзины
	 * @param {Array|Integer} idOrList идентификатор или список идентификаторов родительских страниц
	 */
	this.restoreFromTrash = function(idOrList) {
		var parentIdList = getParentIdList(idOrList);
		parentIdList.forEach(pushParentId);
		parentIdList.forEach(restoreTreeFromTrash);
	};

	/**
	 * Удаляет список родительских страниц и их детей из корзины
	 * @param {Array|Integer} idOrList идентификатор или список идентификаторов родительских страниц
	 * @param {Boolean} showConfirmation необходимо ли показывать подтверждение
	 */
	this.remove = function(idOrList, showConfirmation) {
		var parentIdList = getParentIdList(idOrList);
		parentIdList.forEach(pushParentId);

		function deleteList() {
			parentIdList.forEach(deleteTree);
		}

		showConfirmation ? showDeleteConfirmation(deleteList) : deleteList();
	};

	/**
	 * Возвращает список идентификаторов страниц
	 * @param {Array|Integer} idOrList идентификатор или список идентификаторов родительских страниц
	 * @returns {Array}
	 */
	function getParentIdList(idOrList) {
		var idList = Array.isArray(idOrList) ? idOrList : [idOrList];

		idList.forEach(function(value, index) {
			idList[index] = parseInt(value);
		});

		return idList;
	}

	/**
	 * Формирует список идентификаторов страниц, над которым производится операция
	 * @param {Document} response ответ бэкенда со списком дочерних страниц
	 * @param {Integer} parentId идентификатор родительской страницы
	 * @returns {Array}
	 */
	function getPageIdListForRequest(response, parentId) {
		var pageIdList = collectChildIdList(response);
		return (pageIdList.length === 0) ? [parentId] : pageIdList
	}

	/**
	 * Включает активность родительской страницы и всех ее детей
	 * @param {Integer} parentId идентификатор родительской страницы
	 */
	function activateTree(parentId) {
		forEachChildInPart(parentId, 'getInactiveChildrenPart', requestActivate);
	}

	/**
	 * Отключает активность родительской страницы и всех ее детей
	 * @param {Integer} parentId идентификатор родительской страницы
	 */
	function deactivateTree(parentId) {
		forEachChildInPart(parentId, 'getActiveChildrenPart', requestDeactivate);
	}

	/**
	 * Помещает родительскую страницу и ее детей в корзину
	 * @param {Integer} parentId идентификатор родительской страницы
	 */
	function putTreeToTrash(parentId) {
		forEachChildInPart(parentId, 'getChildrenPart', requestPutToTrash);
	}

	/**
	 * Восстанавливает родительскую страницу и ее детей из корзины
	 * @param {Integer} parentId идентификатор родительской страницы
	 */
	function restoreTreeFromTrash(parentId) {
		forEachChildInPart(parentId, 'getDeletedChildrenPart', requestRestoreFromTrash);
	}

	/**
	 * Удаляет родительскую страницу и ее детей из корзины
	 * @param {Integer} parentId идентификатор родительской страницы
	 */
	function deleteTree(parentId) {
		forEachChildInPart(parentId, 'getDeletedChildrenPart', requestDelete);
	}

	/**
	 * Выполняет операцию для каждой дочерней страницы в части списка
	 * @param {Integer} parentId идентификатор родительской страницы
	 * @param {String} method метод бэкенда для получение части списка
	 * @param {Function} callback реализация операции
	 */
	function forEachChildInPart(parentId, method, callback) {
		getChildrenPart(parentId, method, function(response) {
			var pageIdList = getPageIdListForRequest(response, parentId);
			callback(pageIdList, parentId);
		});
	}
	
	/**
	 * Показывает окно с подтверждение удаления
	 * @param {Function} callback функция обратного вызова в случае подтверждения
	 */
	function showDeleteConfirmation(callback) {
		openDialog('', getLabel('js-del-short-title'), {
			html: getLabel('js-del-shured'),
			confirmText: getLabel('js-del-do'),
			cancelButton: true,
			cancelText: getLabel('js-cancel'),
			confirmCallback: function(dialogName) {
				closeDialog(dialogName);
				callback();
			}
		});
	}

	/**
	 * Помещает родительскую страницу в очередь
	 * @param {Integer} id идентификатор родительской страницы
	 */
	function pushParentId(id) {
		parentIdQueue[id] = id;
	}

	/**
	 * Удаляет родительскую страницу из очереди
	 * @param {Integer} id идентификатор родительской страницы
	 */
	function pullParentId(id) {
		delete parentIdQueue[id];
	}

	/**
	 * Определяет находится ли родительская страница в очереди
	 * @param {Integer} id идентификатор родительской страницы
	 * @returns {Boolean}
	 */
	function isParentInQueue(id) {
		return !!parentIdQueue[id];
	}

	/**
	 * Возвращает длину очереди родительских страниц
	 * @returns {Integer}
	 */
	function getParentIdQueueLength() {
		return Object.keys(parentIdQueue).length;
	}

	/**
	 * Подключает повторное выполнение операции
	 * @param {PageTreeRecursiveOperator} operator экземпляр класса оператора
	 */
	function bindRepetition(operator) {
		Control.dataSet.addEventHandler('onAfterExecute', function(request) {
			var parentId = request.params.parent_id;

			if (request.params.is_last_iteration) {
				stopRepetition(parentId);
				return true;
			}

			if (isOperationRequiresSeveralIteration(request.params.element.length)) {
				showProgressWindow(parentId);
			}

			repeatOperation(operator, request);
			return true;
		});
	}

	/**
	 * Останавливает повторение операции над родительской страницей
	 * @param {Integer} parentId идентфикатор родительской страницы
	 */
	function stopRepetition(parentId) {
		pullParentId(parentId);

		if (getParentIdQueueLength() === 0) {
			closeProgressWindow(parentId);
		}
	}

	/**
	 * Запрашивает повторение операции
	 * @param {PageTreeRecursiveOperator} operator экземпляр класса оператора
	 * @param {Object} request запрос предыдущей операции
	 */
	function repeatOperation(operator, request) {
		var parentId = request.params.parent_id;

		if (request.method === 'tree_set_activity') {
			operator.switchActivity(parentId, request.params.active);
		}

		if (request.method === 'restore_element') {
			operator.restoreFromTrash(parentId);
		}

		var showConfirmation = false;

		if (request.method === 'tree_delete_element') {
			operator.putToTrash(parentId, showConfirmation);
		}

		if (request.method === 'tree_kill_element') {
			operator.remove(parentId, showConfirmation);
		}
	}

	/**
	 * Отображает окно с прелоудером
	 * @param {Integer} parentId идентификатор родительской страницы
	 */
	function showProgressWindow(parentId) {
		if (isProgressWindowShown) {
			return;
		}

		isProgressWindowShown = true;
		$.get('/styles/skins/modern/design/js/common/html/ProgressBar.html', function(html) {
			openDialog('', getLabel('js-operation-processing'), {
				name: parentId,
				html: html,
				width: 460,
				cancelButton: false,
				stdButtons: false,
				closeButton: false,
				customClass: 'modalUp'
			});
		});
	}

	/**
	 * Закрывает окно с прелоудером
	 * @param {Integer} parentId идентификатор родительской страницы
	 */
	function closeProgressWindow(parentId) {
		isProgressWindowShown = false;
		var popupName = parentId ? parentId.toString() : '';
		getPopupByName(popupName) ? closeDialog(popupName) : closeDialog();
	}

	/**
	 * Определяет будет ли операция над страницами требовать нескольких итераций
	 * @param {Integer} pageListLength количество страниц, которым требуется поменять статус
	 * @returns {Boolean}
	 */
	function isOperationRequiresSeveralIteration(pageListLength) {
		return !isProgressWindowShown && pageListLength === CHILDREN_PART_LIMIT;
	}

	/**
	 * Запрашивает активацию списка страниц
	 * @param {Array} pageIdList список идентификаторов страниц
	 * @param {Integer} parentId идентификатор родительской страницы
	 */
	function requestActivate(pageIdList, parentId) {
		requestSwitchActivity(pageIdList, parentId, true);
	}

	/**
	 * Запрашивает деактивацию списка страниц
	 * @param {Array} pageIdList список идентификаторов страниц
	 * @param {Integer} parentId идентификатор родительской страницы
	 */
	function requestDeactivate(pageIdList, parentId) {
		requestSwitchActivity(pageIdList, parentId, false);
	}

	/**
	 * Запрашивает изменение активности списка страниц
	 * @param {Array} pageIdList список идентификаторов страниц
	 * @param {Integer} parentId идентификатор родительской страницы
	 * @param {Boolean} status желаемый статус активности
	 */
	function requestSwitchActivity(pageIdList, parentId, status) {
		if (!isParentInQueue(parentId)) {
			return;
		}

		var DataSet = Control.dataSet;

		if (!DataSet.isAvailable()) {
			executeWithTimeout(requestSwitchActivity, [pageIdList, parentId, status], FUNCTION_EXECUTION_TIMEOUT);
			return;
		}

		DataSet.execute('tree_set_activity', {
			'element': pageIdList,
			'selected_items': collectPageList(pageIdList),
			'active': status ? 1 : 0,
			'parent_id': parentId,
			'is_last_iteration': isLastIteration(pageIdList, parentId)
		});
	}

	/**
	 * Запрашивает помещение списка страниц в корзину
	 * @param {Array} pageIdList список идентификаторов страниц
	 * @param {Integer} parentId идентификатор родительской страницы
	 */
	function requestPutToTrash(pageIdList, parentId) {
		if (!isParentInQueue(parentId)) {
			return;
		}

		var DataSet = Control.dataSet;

		if (!DataSet.isAvailable()) {
			executeWithTimeout(requestPutToTrash, [pageIdList, parentId], FUNCTION_EXECUTION_TIMEOUT);
			return;
		}

		DataSet.execute('tree_delete_element', {
			'element': pageIdList,
			'selected_items': collectPageList(pageIdList),
			'allow': true,
			'parent_id': parentId,
			'is_last_iteration': isLastIteration(pageIdList, parentId)
		});
	}

	/**
	 * Запрашивает восстановление списка страниц из корзины
	 * @param {Array} pageIdList список идентификаторов страниц
	 * @param {Integer} parentId идентификатор родительской страницы
	 */
	function requestRestoreFromTrash(pageIdList, parentId) {
		if (!isParentInQueue(parentId)) {
			return;
		}

		var DataSet = Control.dataSet;

		if (!DataSet.isAvailable()) {
			executeWithTimeout(requestRestoreFromTrash, [pageIdList, parentId], FUNCTION_EXECUTION_TIMEOUT);
			return;
		}

		DataSet.execute('restore_element', {
			'element': pageIdList,
			'selected_items': collectPageList(pageIdList),
			'parent_id': parentId,
			'is_last_iteration': isLastIteration(pageIdList, parentId)
		});
	}

	/**
	 * Запрашивает удаление списка страниц из корзины
	 * @param {Array} pageIdList список идентификаторов страниц
	 * @param {Integer} parentId идентификатор родительской страницы
	 */
	function requestDelete(pageIdList, parentId) {
		if (!isParentInQueue(parentId)) {
			return;
		}

		var DataSet = Control.dataSet;

		if (!DataSet.isAvailable()) {
			executeWithTimeout(requestDelete, [pageIdList, parentId], FUNCTION_EXECUTION_TIMEOUT);
			return;
		}

		DataSet.execute('tree_kill_element', {
			'element': pageIdList,
			'selected_items': collectPageList(pageIdList),
			'parent_id': parentId,
			'is_last_iteration': isLastIteration(pageIdList, parentId)
		});
	}

	/**
	 * Выполняет функцию обратного вызова с таймаутом
	 * @param {Function} callback функция обратного вызова
	 * @param {Array} params параметры вызова
	 * @param {Integer} timeout таймаут
	 */
	function executeWithTimeout(callback, params, timeout) {
		setTimeout(function() {
			callback.apply(this, params);
		}, timeout);
	}

	/**
	 * Определяет будет ли следующая итерация последней
	 * @param {Array} pageIdList список идентификаторов изменяемых страниц
	 * @param {Integer} parentId идентификатор родительской страницы
	 * @returns {Boolean}
	 */
	function isLastIteration(pageIdList, parentId) {
		return pageIdList.length === 1 && pageIdList[0] === parentId;
	}

	/**
	 * Возвращает список страниц
	 * @param {Array} pageIdList список идентификаторов страниц
	 * @return {Object}
	 */
	function collectPageList(pageIdList) {
		var pageList = {};

		$(pageIdList).each(function() {
			var entity = Control.getItem(this);

			if (entity) {
				pageList[entity.id] = entity;
			}
		});

		return pageList;
	}

	/**
	 * Запрашивает часть списка дочерних страниц
	 * @param {Integer} parentId идентификатор родительской страницы
	 * @param {String} method метод бэкенда для получение части списка
	 * @param {Function} handleSuccessRequest обработчик успешного получения списка
	 */
	function getChildrenPart(parentId, method, handleSuccessRequest) {
		$.ajax({
			url: window.pre_lang + '/admin/' + Control.dataSet.getModule() + '/' + method + '/.xml',
			dataType: 'xml',
			method: 'get',
			data: { parentId: parentId },
			success: handleSuccessRequest,
			error: handleErrorRequest
		});
	}

	/**
	 * Разбирает ответ бэкенда со списком дочерних страниц и возвращает их идентификаторы
	 * @param {Document} response ответ бэкенда со списком дочерних страниц
	 * @returns {Array}
	 */
	function collectChildIdList(response) {
		var childIdList = [];

		$('child', response).each(function() {
			childIdList.push($(this).attr('id'));
		});

		return childIdList;
	}

	/**
	 * Обрабатывает ответ бэкенда с ошибкой
	 * @param {Document} response ответ бэкенда с ошибкой
	 */
	function handleErrorRequest(response) {
		alert(getLabel('js-server_error'));
	}

	this.init();
}