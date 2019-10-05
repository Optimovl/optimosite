<?php

	use UmiCms\Service;

	class umiOpenSSL implements iUmiOpenSSL {

		protected $privateKeyFilePath = false,
			$publicKeyFilePath = false,
			$certificateFilePath = false,

			$privateKey,
			$publicKey;

		public $dn = [
			'countryName' => 'RU',
			'stateOrProvinceName' => 'none',
			'localityName' => 'Saint-Petersburg',
			'organizationName' => 'MySelf',
			'organizationalUnitName' => 'Whatever',
			'commonName' => 'mySelf',
			'emailAddress' => 'user@domain.com'
		];

		public function __construct() {
			if (!function_exists('openssl_pkey_new')) {
				throw new publicAdminException('No openSSL extenstion');
			}
		}

		public function setPrivateKeyFilePath($keyFilePath) {
			if ($this->checkFilePath($keyFilePath)) {
				$this->privateKeyFilePath = $keyFilePath;
				return true;
			}

			throw new coreException("No file {$keyFilePath}");
		}

		public function getPrivateKeyFilePath() {
			return $this->privateKeyFilePath;
		}

		public function setPublicKeyFilePath($keyFilePath) {
			if ($this->checkFilePath($keyFilePath)) {
				$this->publicKeyFilePath = $keyFilePath;
				return true;
			}

			throw new coreException("No file {$keyFilePath}");
		}

		public function getPublicKeyFilePath() {
			return $this->publicKeyFilePath;
		}

		public function setCertificateFilePath($filePath) {
			if ($this->checkFilePath($filePath)) {
				$this->certificateFilePath = $filePath;
				return true;
			}

			throw new coreException("No file {$filePath}");
		}

		public function getCertificateFilePath() {
			return $this->certificateFilePath;
		}

		public function loadSettingsFromRegedit() {
			$regedit = Service::Registry();
			$privateKeyFilePath = $regedit->get('//settings/openssl_private_key');
			$publicKeyFilePath = $regedit->get('//settings/openssl_public_key');

			$bResult = false;

			try {
				$bResult =
					($this->setPrivateKeyFilePath($privateKeyFilePath) && $this->setPublicKeyFilePath($publicKeyFilePath));
			} catch (coreException $e) {
				// ignored
			}

			return $bResult;
		}

		public function createPrivateKeyFile($keyFilePath) {
			$this->privateKey = $private_key_resource = openssl_pkey_new();
			openssl_pkey_export($private_key_resource, $private_key);

			$dirname = dirname($keyFilePath);

			if ($this->checkFolderPath($dirname, true)) {
				file_put_contents($keyFilePath, $private_key);
				return $this->setPrivateKeyFilePath($keyFilePath);
			}

			throw new coreException("Can't save private key file. Folder \"{$dirname}\" is not writable.");
		}

		public function createCertificateFile($filePath) {
			if ($this->getPrivateKeyFilePath()) {
				$privkey = $this->getPrivateKey();
				$csr = openssl_csr_new($this->dn, $privkey);
				$ssecrt = openssl_csr_sign($csr, null, $privkey, 100);
				if (openssl_x509_export($ssecrt, $cert)) {
					file_put_contents($filePath, $cert);
					$this->setCertificateFilePath($filePath);
					return true;
				}

				throw new coreException("Can't export x509 certificate");
			}

			throw new coreException('Private key requred');
		}

		public function createPublicKeyFile($filePath) {
			if ($this->privateKeyFilePath && $this->certificateFilePath) {
				$publicKey = $this->getPublicKey();
				$publicKeyArr = openssl_pkey_get_details($publicKey);
				$publicKeyStr = $publicKeyArr['key'];
				file_put_contents($filePath, $publicKeyStr);
				return true;
			}

			throw new coreException('Private key and certificate required');
		}

		public function encrypt($data, $out = true) {
			if ($out) {
				$getKeyFunction = 'getPrivateKey';
				$encryptFunction = 'openssl_private_encrypt';
			} else {
				$getKeyFunction = 'getPublicKey';
				$encryptFunction = 'openssl_public_encrypt';
			}

			$key = $this->$getKeyFunction();
			if ($key) {
				$result = null;
				$encryptFunction($data, $result, $key);
				return $result;
			}

			throw new coreException('Unnable to load key');
		}

		public function decrypt($enc, $out = true) {
			if ($out) {
				$getKeyFunction = 'getPublicKey';
				$decryptFunction = 'openssl_public_decrypt';
			} else {
				$getKeyFunction = 'getPrivateKey';
				$decryptFunction = 'openssl_private_decrypt';
			}

			$key = $this->$getKeyFunction();
			if ($key) {
				$result = null;
				$decryptFunction($enc, $result, $key);
				return $result;
			}

			throw new coreException('Unnable to load key');
		}

		private function getPrivateKey() {
			if ($this->privateKey) {
				return $this->privateKey;
			}

			$keyFilePath = 'file://' . $this->getPrivateKeyFilePath();
			return $this->privateKey = openssl_pkey_get_private($keyFilePath);
		}

		private function getPublicKey() {
			if ($this->publicKey) {
				return $this->publicKey;
			}

			if ($this->publicKeyFilePath) {
				$publicKeyFilePath = $this->getPublicKeyFilePath();
				return $this->publicKey = openssl_pkey_get_public(file_get_contents($publicKeyFilePath));
			}

			if ($this->certificateFilePath) {
				$certFilePath = $this->getCertificateFilePath();
				return $this->publicKey = openssl_pkey_get_public(file_get_contents($certFilePath));
			}

			throw new coreException("Can't get public key.");
		}

		private function checkFileOrFolderPath($filePath, $checkIfWritable = false) {
			if (is_readable($filePath)) {
				if ($checkIfWritable) {
					if (is_writable($filePath)) {
						return true;
					}

					return false;
				}

				return true;
			}

			return false;
		}

		private function checkFilePath($filePath, $checkIfWritable = false) {
			if (file_exists($filePath)) {
				return $this->checkFileOrFolderPath($filePath, $checkIfWritable);
			}

			return false;
		}

		private function checkFolderPath($folderPath, $checkIfWritable = false) {
			if (is_dir($folderPath)) {
				return $this->checkFileOrFolderPath($folderPath, $checkIfWritable);
			}

			return false;
		}

		public function supplyDefaultKeyFiles() {
			$bResult = false;
			//
			$bResult = $this->loadSettingsFromRegedit();
			if (!$bResult) {
				/*
				если что-то не так - создаем новые ключи в директории /ssl/
				*/
				$sSslDir = $_SERVER['DOCUMENT_ROOT'] . '/ssl';
				$bCreateDir = @mkdir($sSslDir);
				$oSslDir = new umiDirectory($sSslDir);
				//
				if ($oSslDir instanceof umiDirectory && !$oSslDir->getIsBroken()) {
					$bCertOk = false;
					$sCertFile = $sSslDir . '/' . uniqid('');
					$bPrivateOk = false;
					$sPrivateFile = $sSslDir . '/' . uniqid('');
					$bPublicOk = false;
					$sPublicFile = $sSslDir . '/' . uniqid('');
					// сначала необходим private
					$bSucc = $this->createPrivateKeyFile($sPrivateFile);
					if ($bSucc) {
						$bPrivateOk = $this->setPrivateKeyFilePath($sPrivateFile);
					}
					// затем создаем сертификат
					if ($bPrivateOk) {
						$bSucc = $this->createCertificateFile($sCertFile);
						if ($bSucc) {
							$bCertOk = $this->setCertificateFilePath($sCertFile);
						}
					}
					// и наконец создаем public
					if ($bCertOk) {
						$bSucc = $this->createPublicKeyFile($sPublicFile);
						if ($bSucc) {
							$bPublicOk = $this->setPublicKeyFilePath($sPublicFile);
						}
					}
					// пишем в реестр
					if ($bPublicOk) {
						$regedit = Service::Registry();
						$bPrivateSetted = $regedit->set('//settings/openssl_private_key', $sPrivateFile);
						$bPublicSetted = $regedit->set('//settings/openssl_public_key', $sPublicFile);
						$bResult = ($bPrivateSetted && $bPublicSetted);
					}
				}
			}

			return $bResult;
		}
	}
