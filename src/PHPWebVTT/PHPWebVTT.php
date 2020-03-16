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

		//XXX need global search and replace for \0
		$startTime = time(); // new Date.now();
		$this->linePos = 0;
		$this->lines = preg_split( self::NEWLINE_REGEX, $input );

		$alreadyCollected  = false;
		$cues = [];

		$line = $this->lines[$linePos];
		$lineLength = line.length,
		$signature = "WEBVTT";
		$bom = 0;
		$signatureLength = strlen($signature);

		/* Byte order mark */
		if ($line[0] === "\ufeff") {
			$bom = 1
			$signatureLength += 1
		}

		/* SIGNATURE */
		if (
			$lineLength < $signatureLength ||
			line.indexOf($signature) !== 0+bom ||
			lineLength > $signatureLength &&
			line[$signatureLength] !== " " &&
			line[$signatureLength] !== "\t"
		) {
			$this->addError("No valid signature. (File needs to start with \"WEBVTT\".)")
		}

		linePos++

		/* HEADER */
		while($this->lines[$linePos] != "" && $this->lines[$linePos] != undefined) {
			$this->addError("No blank line after the signature.")
			if($this->lines[$linePos].indexOf("-->") != -1) {
				$alreadyCollected = true
				break
			}
			linePos++
		}

		/* CUE LOOP */
		while($this->lines[$linePos] != undefined) {
			var cue
			while(!$alreadyCollected && $this->lines[$linePos] == "") {
				linePos++
			}
			if(!$alreadyCollected && $this->lines[$linePos] == undefined)
				break

			/* CUE CREATION */
			cue = {
				id:"",
				startTime:0,
				endTime:0,
				pauseOnExit:false,
				direction:"horizontal",
				snapToLines:true,
				linePosition:"auto",
				textPosition:50,
				size:100,
				alignment:"middle",
				text:"",
				tree:null
			}

			var parseTimings = true;

			if($this->lines[$linePos].indexOf("-->") == -1) {
				cue.id = $this->lines[$linePos]

				/* COMMENTS
					 Not part of the specification's parser as these would just be ignored. However,
					 we want them to be conforming and not get "Cue identifier cannot be standalone".
				 */
				if(/^NOTE($|[ \t])/.test(cue.id)) { // .startsWith fails in Chrome
					linePos++
					while($this->lines[$linePos] != "" && $this->lines[$linePos] != undefined) {
						if($this->lines[$linePos].indexOf("-->") != -1)
							$this->addError("Cannot have timestamp in a comment.")
						linePos++
					}
					continue
				}

				linePos++

				if($this->lines[$linePos] == "" || $this->lines[$linePos] == undefined) {
					$this->addError("Cue identifier cannot be standalone.")
					continue
				}

				if($this->lines[$linePos].indexOf("-->") == -1) {
					parseTimings = false
					$this->addError("Cue identifier needs to be followed by timestamp.")
				}

			}

			/* TIMINGS */
			$alreadyCollected = false
			var timings = new \PHPWebVTT\Parser\TimingsAndSettings::parse($this->lines[$linePos], &$this->addError);
			var previousCueStart = 0
			if(cues.length > 0) {
				previousCueStart = cues[cues.length-1].startTime
			}
			if(parseTimings && !timings.parse(cue, previousCueStart)) {
				/* BAD CUE */

				cue = null
				linePos++

				/* BAD CUE LOOP */
				while($this->lines[$linePos] != "" && $this->lines[$linePos] != undefined) {
					if($this->lines[$linePos].indexOf("-->") != -1) {
						$alreadyCollected = true
						break
					}
					linePos++
				}
				continue
			}
			linePos++

			/* CUE TEXT LOOP */
			while($this->lines[$linePos] != "" && $this->lines[$linePos] != undefined) {
				if($this->lines[$linePos].indexOf("-->") != -1) {
					$this->addError("Blank line missing before cue.")
					$alreadyCollected = true
					break
				}
				if(cue.text != "")
					cue.text += "\n"
				cue.text += $this->lines[$linePos]
				linePos++
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



//
// var WebVTTSerializer = function() {
// 	function serializeTree(tree) {
// 		var result = ""
// 		for (var i = 0; i < tree.length; i++) {
// 			var node = tree[i]
// 			if(node.type == "text") {
// 				result += node.value
// 			} else if(node.type == "object") {
// 				result += "<" + node.name
// 				if(node.classes) {
// 					for(var y = 0; y < node.classes.length; y++) {
// 						result += "." + node.classes[y]
// 					}
// 				}
// 				if(node.value) {
// 					result += " " + node.value
// 				}
// 				result += ">"
// 				if(node.children)
// 					result += serializeTree(node.children)
// 				result += "</" + node.name + ">"
// 			} else {
// 				result += "<" + node.value + ">"
// 			}
// 		}
// 		return result
// 	}
// 	function serializeCue(cue) {
// 		return cue.startTime + " " + cue.endTime + "\n" + serializeTree(cue.tree.children) + "\n\n"
// 	}
// 	this.serialize = function(cues) {
// 		var result = ""
// 		for(var i=0;i<cues.length;i++) {
// 			result += serializeCue(cues[i])
// 		}
// 		return result
// 	}
// }
