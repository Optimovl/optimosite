<?php

	use UmiCms\Service;

	class defaultCaptchaDrawer extends captchaDrawer {

		public function draw($randomCode) {
			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->contentType('image/jpeg');
			$buffer->setHeader('Etag', sha1($randomCode));
			$buffer->push($this->render($randomCode));
			$buffer->end();
		}

		private function render($str) {
			$length = 7;
			$charWidth = 15;
			$imagewidth = $imagelength = $length * $charWidth + 16;
			$imageheight = 35;

			$image = imagecreate($imagelength, $imageheight);
			$bgcolor = imagecolorallocate($image, 255, 255, 255);

			$stringcolor = imagecolorallocate($image, 0, 0, 0);
			$linecolor = imagecolorallocate($image, 0, 0, 0);

			$chars = str_split($str);
			$sz = umiCount($chars);

			$left = 20;
			for ($i = 0; $i < $sz; $i++) {
				$rx = (mt_rand() % 6) - 3;
				$ry = (mt_rand() % 6) - 3;

				imagestring($image, 25, $left + $rx, 8 + $ry, $chars[$i], $stringcolor);
				$left += $charWidth;
			}

			$linecolor = imagecolorallocate($image, 204, 204, 204);

			$c = 10 + (mt_rand() % 10);
			for ($i = 0; $i < $c; $i++) {
				$y1 = 0;
				$y2 = $imageheight;

				$x1 = mt_rand() % ($imagewidth + $imagewidth * 0.2);
				$x2 = $x1 - $imageheight;

				imageline($image, $x1, $y1, $x2, $y2, $linecolor);
			}

			$filePath = SYS_CACHE_RUNTIME . md5($str);
			imagejpeg($image, $filePath, 65);
			$imageContent = file_get_contents($filePath);
			unlink($filePath);
			return $imageContent;
		}
	}
