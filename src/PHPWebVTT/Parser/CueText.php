<?php

namespace PHPWebVTT\Parser;

class CueText {

	// const MODE_CHAPTER .// etc

	public static function parse(string $line, callable $errorHandler, string $mode) {
		var line = line,
				pos = 0,
				err = function(message) {
					if(mode == "metadata")
						return
					errorHandler(message, pos+1)
				}

		this.parse = function(cueStart, cueEnd) {
			var result = {children:[]},
					current = result,
					timestamps = []

			function attach(token) {
				current.children.push({type:"object", name:token[1], classes:token[2], children:[], parent:current})
				current = current.children[current.children.length-1]
			}
			function inScope(name) {
				var node = current
				while(node) {
					if(node.name == name)
						return true
					node = node.parent
				}
				return
			}

			while(line[pos] != undefined) {
				var token = nextToken()
				if(token[0] == "text") {
					current.children.push({type:"text", value:token[1], parent:current})
				} else if(token[0] == "start tag") {
					if(mode == "chapters")
						$this->addError("Start tags not allowed in chapter title text.")
					var name = token[1]
					if(name != "v" && name != "lang" && token[3] != "") {
						$this->addError("Only <v> and <lang> can have an annotation.")
					}
					if(
						name == "c" ||
						name == "i" ||
						name == "b" ||
						name == "u" ||
						name == "ruby"
					) {
						attach(token)
					} else if(name == "rt" && current.name == "ruby") {
						attach(token)
					} else if(name == "v") {
						if(inScope("v")) {
							$this->addError("<v> cannot be nested inside itself.")
						}
						attach(token)
						current.value = token[3] // annotation
						if(!token[3]) {
							$this->addError("<v> requires an annotation.")
						}
					} else if(name == "lang") {
						attach(token)
						current.value = token[3] // language
					} else {
						$this->addError("Incorrect start tag.")
					}
				} else if(token[0] == "end tag") {
					if(mode == "chapters")
						$this->addError("End tags not allowed in chapter title text.")
					// XXX check <ruby> content
					if(token[1] == current.name) {
						current = current.parent
					} else if(token[1] == "ruby" && current.name == "rt") {
						current = current.parent.parent
					} else {
						$this->addError("Incorrect end tag.")
					}
				} else if(token[0] == "timestamp") {
					if(mode == "chapters")
						$this->addError("Timestamp not allowed in chapter title text.")
					var timings = new WebVTTCueTimingsAndSettingsParser(token[1], err),
							timestamp = timings.parseTimestamp()
					if(timestamp != undefined) {
						if(timestamp <= cueStart || timestamp >= cueEnd) {
							$this->addError("Timestamp must be between start timestamp and end timestamp.")
						}
						if(timestamps.length > 0 && timestamps[timestamps.length-1] >= timestamp) {
							$this->addError("Timestamp must be greater than any previous timestamp.")
						}
						current.children.push({type:"timestamp", value:timestamp, parent:current})
						timestamps.push(timestamp)
					}
				}
			}
			while(current.parent) {
				if(current.name != "v") {
					$this->addError("Required end tag missing.")
				}
				current = current.parent
			}
			return result
		}

		function nextToken() {
			var state = "data",
					result = "",
					buffer = "",
					classes = []
			while(line[pos-1] != undefined || pos == 0) {
				var c = line[pos]
				if(state == "data") {
					if(c == "&") {
						buffer = c
						state = "escape"
					} else if(c == "<" && result == "") {
						state = "tag"
					} else if(c == "<" || c == undefined) {
						return ["text", result]
					} else {
						result += c
					}
				} else if(state == "escape") {
					if(c == "&") {
						$this->addError("Incorrect escape.")
						result += buffer
						buffer = c
					} else if(/[abglmnsprt]/.test(c)) {
						buffer += c
					} else if(c == ";") {
						if(buffer == "&amp") {
							result += "&"
						} else if(buffer == "&lt") {
							result += "<"
						} else if(buffer == "&gt") {
							result += ">"
						} else if(buffer == "&lrm") {
							result += "\u200e"
						} else if(buffer == "&rlm") {
							result += "\u200f"
						} else if(buffer == "&nbsp") {
							result += "\u00A0"
						} else {
							$this->addError("Incorrect escape.")
							result += buffer + ";"
						}
						state = "data"
					} else if(c == "<" || c == undefined) {
						$this->addError("Incorrect escape.")
						result += buffer
						return ["text", result]
					} else {
						$this->addError("Incorrect escape.")
						result += buffer + c
						state = "data"
					}
				} else if(state == "tag") {
					if(c == "\t" || c == "\n" || c == "\f" || c == " ") {
						state = "start tag annotation"
					} else if(c == ".") {
						state = "start tag class"
					} else if(c == "/") {
						state = "end tag"
					} else if(/\d/.test(c)) {
						result = c
						state = "timestamp tag"
					} else if(c == ">" || c == undefined) {
						if(c == ">") {
							pos++
						}
						return ["start tag", "", [], ""]
					} else {
						result = c
						state = "start tag"
					}
				} else if(state == "start tag") {
					if(c == "\t" || c == "\f" || c == " ") {
						state = "start tag annotation"
					} else if(c == "\n") {
						buffer = c
						state = "start tag annotation"
					} else if(c == ".") {
						state = "start tag class"
					} else if(c == ">" || c == undefined) {
						if(c == ">") {
							pos++
						}
						return ["start tag", result, [], ""]
					} else {
						result += c
					}
				} else if(state == "start tag class") {
					if(c == "\t" || c == "\f" || c == " ") {
						classes.push(buffer)
						buffer = ""
						state = "start tag annotation"
					} else if(c == "\n") {
						classes.push(buffer)
						buffer = c
						state = "start tag annotation"
					} else if(c == ".") {
						classes.push(buffer)
						buffer = ""
					} else if(c == ">" || c == undefined) {
						if(c == ">") {
							pos++
						}
						classes.push(buffer)
						return ["start tag", result, classes, ""]
					} else {
						buffer += c
					}
				} else if(state == "start tag annotation") {
					if(c == ">" || c == undefined) {
						if(c == ">") {
							pos++
						}
						buffer = buffer.split(/[\u0020\t\f\r\n]+/).filter(function(item) { if(item) return true }).join(" ")
						return ["start tag", result, classes, buffer]
					} else {
						buffer +=c
					}
				} else if(state == "end tag") {
					if(c == ">" || c == undefined) {
						if(c == ">") {
							pos++
						}
						return ["end tag", result]
					} else {
						result += c
					}
				} else if(state == "timestamp tag") {
					if(c == ">" || c == undefined) {
						if(c == ">") {
							pos++
						}
						return ["timestamp", result]
					} else {
						result += c
					}
				} else {
					$this->addError("Never happens.") // The joke is it might.
				}
				// 8
				pos++
			}

		}

	}

}
