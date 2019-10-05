<?php

	/**
	 * Класс сервис контейнера
	 * @see iServiceContainerFactory::create()
	 */
	class ServiceContainer implements iServiceContainer {

		/** @var array $rules правила инстанциирования сервисов */
		private $rules;

		/** @var array $parameters параметры для инстанцирования сервисов */
		private $parameters;

		/** @var object[] $services список инстанцированных сервисов */
		private $services = [];

		/** @inheritdoc */
		public function __construct(array $rules = [], array $parameters = []) {
			$this->rules = $rules;
			$this->parameters = $parameters;
		}

		/** @inheritdoc */
		public function get($name) {
			$this->validateServiceName($name);

			if (isset($this->services[$name])) {
				return $this->services[$name];
			}

			$service = $this->createService($name);
			$this->set($name, $service);

			return $this->services[$name];
		}

		/** @inheritdoc */
		public function getNew($name) {
			$this->validateServiceName($name);

			$service = $this->createService($name);

			if (!isset($this->services[$name])) {
				$this->set($name, $service);
			}

			return $service;
		}

		/** @inheritdoc */
		public function set($name, $service) {
			if (!is_object($service)) {
				throw new Exception('Wrong service given');
			}

			$this->services[$name] = $service;
			return $this;
		}

		/** @inheritdoc */
		public function hasRules($name) {
			return isset($this->rules[$name]);
		}

		/** @inheritdoc */
		public function addRules(array $rules) {
			foreach ($rules as $serviceName => $serviceRules) {
				if (!$this->hasRules($serviceName)) {
					$this->rules[$serviceName] = $serviceRules;
				}
			}
		}

		/** @inheritdoc */
		public function addParameters(array $params) {
			foreach ($params as $name => $value) {
				if (!$this->hasParameter($name)) {
					$this->parameters[$name] = $value;
				}
			}
		}

		/** @inheritdoc */
		public function hasParameter($name) {
			try {
				$this->getParameter($name);
			} catch (Exception $exception) {
				return false;
			}

			return true;
		}

		/** @inheritdoc */
		public function initService($name, $service) {
			if (!$this->hasRules($name)) {
				throw new Exception('Service rules not found: ' . $name);
			}

			$entry = &$this->rules[$name];

			if (!is_array($entry) || !isset($entry['calls'])) {
				throw new Exception($name . ' service entry must be an array containing a \'calls\' key');
			}

			if (isset($entry['lock']) && $entry['lock']) {
				throw new Exception($name . ' contains circular reference');
			}

			$entry['lock'] = true;
			$this->initializeService($service, $name, $entry['calls']);
			$entry['lock'] = false;
			return $service;
		}

		/**
		 * Валидирует имя сервиса
		 * @param string $name имя сервиса
		 * @throws Exception
		 */
		private function validateServiceName($name) {
			if (!is_string($name) || $name === '') {
				throw new Exception('Wrong service name given');
			}
		}

		/**
		 * Возвращает параметры инстанциирования сервиса
		 * @param string $name имя сервиса
		 * @return array
		 * @throws Exception
		 */
		private function getParameter($name) {
			$tokens = explode('.', $name);
			$context = $this->parameters;

			while (($token = array_shift($tokens)) !== null) {
				if (!isset($context[$token])) {
					throw new Exception('Parameter not found: ' . $name);
				}
				$context = $context[$token];
			}

			return $context;
		}

		/**
		 * Инстанциирует сервис и возвращает его экземпляр
		 * @param string $name имя сервиса
		 * @return object
		 * @throws Exception
		 */
		private function createService($name) {
			if (!$this->hasRules($name)) {
				throw new Exception('Service rules not found: ' . $name);
			}

			$entry = &$this->rules[$name];

			if (!is_array($entry) || !isset($entry['class'])) {
				throw new Exception($name . ' service entry must be an array containing a \'class\' key');
			}

			if (!class_exists($entry['class'])) {
				throw new Exception($name . ' service class does not exist: ' . $entry['class']);
			}

			if (isset($entry['lock']) && $entry['lock']) {
				throw new Exception($name . ' contains circular reference');
			}

			$entry['lock'] = true;
			$arguments = isset($entry['arguments']) ? $this->resolveArguments($entry['arguments']) : [];

			$reflector = new ReflectionClass($entry['class']);
			$interfaces = $reflector->getInterfaces();

			if (isset($interfaces['iSingleton'])) {
				$service = $entry['class']::getInstance();
			} else {
				$service = $reflector->newInstanceArgs($arguments);
			}

			if (isset($entry['calls'])) {
				$this->initializeService($service, $name, $entry['calls']);
			}

			$entry['lock'] = false;
			return $service;
		}

		/**
		 * Формирует список значений аргументов по списку их идентификаторов
		 * @param array $argumentDefinitions список идентификаторов аргументов
		 * @return array
		 * @throws Exception
		 */
		private function resolveArguments(array $argumentDefinitions) {
			$arguments = [];

			foreach ($argumentDefinitions as $definition) {
				if ($definition instanceof ServiceReference) {
					$name = $definition->getName();
					$arguments[] = $this->get($name);
				} elseif ($definition instanceof ParameterReference) {
					$name = $definition->getName();
					$arguments[] = $this->getParameter($name);
				} elseif ($definition instanceof InstantiableReference) {
					$name = $definition->getName();
					$arguments[] = new $name();
				} elseif ($definition instanceof ServiceContainerReference) {
					$arguments[] = $this;
				} else {
					$arguments[] = $definition;
				}
			}

			return $arguments;
		}

		/**
		 * Применяет правила инстанциирования к сервису,
		 * то есть вызывает все необходимые методы с передачей им параметров
		 * @param object $service сервис
		 * @param string $name имя сервиса
		 * @param array $callDefinitions правила инстанцирования
		 * @throws Exception
		 */
		private function initializeService($service, $name, array $callDefinitions) {
			foreach ($callDefinitions as $callDefinition) {
				if (!is_array($callDefinition) || !isset($callDefinition['method'])) {
					throw new Exception($name . ' service calls must be arrays containing a \'method\' key');
				}

				if (!is_callable([$service, $callDefinition['method']])) {
					throw new Exception(
						$name . ' service asks for call to uncallable method: ' . $callDefinition['method']
					);
				}

				$arguments = isset($callDefinition['arguments'])
					? $this->resolveArguments($callDefinition['arguments'])
					: [];
				call_user_func_array([$service, $callDefinition['method']], $arguments);
			}
		}
	}
