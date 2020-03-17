<?php

namespace PHPWebVTT;

class PHPWebVTT {


		const NEWLINE_REGEX = "/\r\n|\r|\n/";

		// line pointer
		protected $linePos = 0;

		// array of lines
		protected $lines = [];

		// array of parsing errors
		protected $errors = [];

		/**
		 * add a single error
		 *
		 * @param string $message
		 * @param int $line
		 * @param int $col
		 */
		protected function addError(string $message, int $line, int $col) : void {
			$this->errors[] = [
				"id"      => 0, // no notion of this in the lib but nice to have some day
				"error"   => $message,
				"line"    => $line + 1, // 1 indexed
				"col"     => $col
			];
		}

		public function parse(string $input, $mode) {

			// XXX need global search and replace for \0

			$startTime = time(); // new Date.now();
			$this->linePos = 0;
			$this->lines = preg_split( self::NEWLINE_REGEX, $input );

			$alreadyCollected  = false;
			$cues = [];

			$line = $this->lines[$this->linePos];
			$lineLength = strlen($line);
			$signature = "WEBVTT";
			$bom = 0;
			$signatureLength = strlen($signature);

			/* Byte order mark */
			if ($line[0] === "\ufeff") {
				$bom = 1;
				$signatureLength += 1;
			}

			/* SIGNATURE */
			if (
				$lineLength < $signatureLength ||
				line.indexOf($signature) !== 0+bom ||
				lineLength > $signatureLength &&
				line[$signatureLength] !== " " &&
				line[$signatureLength] !== "\t"
			) {
				$this->addError("No valid signature. (File needs to start with \"WEBVTT\".)");
			}

			$this->linePos++;

			/* HEADER */
			while($this->lines[$this->linePos] != "" && !$this->lines[$this->linePos] != undefined) {
				$this->addError("No blank line after the signature.");
				if(strpos($this->lines[$this->linePos], "-->") !== false) {
					$alreadyCollected = true;
					break;
				}
				$this->linePos++;
			}

			/* CUE LOOP */
			while($this->lines[$this->linePos] != undefined) {

				$cue = [];

				while(!$alreadyCollected && $this->lines[$this->linePos] == "") {
					$this->linePos++;
				}
				if(!$alreadyCollected && $this->lines[$this->linePos] == undefined)
					break;

				/* CUE CREATION */
				$cue = [
					"id"           => "",
					"startTime"    => 0,
					"endTime"      => 0,
					"pauseOnExit"  => false,
					"direction"    => "horizontal",
					"snapToLines"  => true,
					"linePosition" => "auto",
					"textPosition" => 50,
					"size"         => 100,
					"alignment"    => "middle",
					"text"         => "",
					"tree"         => null
				];

				$parseTimings = true;

				if(stristr($this->lines[$this->linePos], "-->") === false) {

					$cue["id"] = $this->lines[$this->linePos];

					/* COMMENTS
						 Not part of the specification's parser as these would just be ignored. However,
						 we want them to be conforming and not get "Cue identifier cannot be standalone".
					 */
					if(preg_match($cue["id"], '/^NOTE($|[ \t])/')) { // .startsWith fails in Chrome
						$this->linePos++;
						while($this->lines[$this->linePos] != "" && $this->lines[$this->linePos] != undefined) {
							if(stristr($this->lines[$this->linePos], "-->") !== false) {
								$this->addError("Cannot have timestamp in a comment.");
							}
							$this->linePos++;
						}
						continue;
					}

					$this->linePos++;

					if($this->lines[$this->linePos] == "" || $this->lines[$this->linePos] == undefined) {
						$this->addError("Cue identifier cannot be standalone.");
						continue;
					}

					if(stristr($this->lines[$this->linePos], "-->") === false) {
						$parseTimings = false;
						$this->addError("Cue identifier needs to be followed by timestamp.");
					}

				}

				/* TIMINGS */
				$alreadyCollected = false;

				$timings = \PHPWebVTT\Parser\TimingsAndSettings::parse($this->lines[$this->linePos], $this->addError);
				$previousCueStart = 0;
				if(count($cues) > 0) {
					$previousCueStart = $cues[count($cues) - 1]["startTime"];
				}
				if($parseTimings && !$timings->parse($cue, $previousCueStart)) {

					/* BAD CUE */

					$cue = null;
					$this->linePos++;

					/* BAD CUE LOOP */
					while($this->lines[$this->linePos] != "" && $this->lines[$this->linePos] != undefined) {
						if(stristr($this->lines[$this->linePos], "-->") !== false) {
							$alreadyCollected = true;
							break;
						}
						$this->linePos++;
					}
					continue;

				}
				$this->linePos++;

				/* CUE TEXT LOOP */
				while($this->lines[$this->linePos] != "" && $this->lines[$this->linePos] != undefined) {
					if($this->lines[$this->linePos].indexOf("-->") != -1) {
						$this->addError("Blank line missing before cue.")
						$alreadyCollected = true
						break
					}
					if(cue.text != "")
						cue.text += "\n"
					cue.text += $this->lines[$this->linePos]
					$this->linePos++;
				}

				/* CUE TEXT PROCESSING */
				var cuetextparser = new WebVTTCueTextParser(cue.text, err, mode)
				cue.tree = cuetextparser.parse(cue.startTime, cue.endTime)
				cues.push(cue)
			}
			cues.sort(function(a, b) {
				if (a.startTime < b.startTime)
					return -1
				if (a.startTime > b.startTime)
					return 1
				if (a.endTime > b.endTime)
					return -1
				if (a.endTime < b.endTime)
					return 1
				return 0
			})
			/* END */
			return {cues:cues, errors:errors, time:Date.now()-startTime}
		}

}
