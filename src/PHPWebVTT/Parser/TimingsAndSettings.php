<?php

namespace PHPWebVTT\Parser\TimingsAndSettings;

class TimingsAndSettings {

	public static function parse(string $line, callable &$errorHandler) {

	var SPACE = /[\u0020\t\f]/,
			NOSPACE = /[^\u0020\t\f]/,
			line = line,
			pos = 0,
			err = function(message) {
				$errorHandler(message, pos+1)
			},
			spaceBeforeSetting = true
	function skip(pattern) {
		while(
			line[pos] != undefined &&
			pattern.test(line[pos])
		) {
			pos++
		}
	}
	function collect(pattern) {
		var str = ""
		while(
			line[pos] != undefined &&
			pattern.test(line[pos])
		) {
			str += line[pos]
			pos++
		}
		return str
	}
	/* http://dev.w3.org/html5/webvtt/#collect-a-webvtt-timestamp */
	function timestamp() {
		var units = "minutes",
				val1,
				val2,
				val3,
				val4
		// 3
		if(line[pos] == undefined) {
			$errorHandler("No timestamp found.")
			return
		}
		// 4
		if(!/\d/.test(line[pos])) {
			$errorHandler("Timestamp must start with a character in the range 0-9.")
			return
		}
		// 5-7
		val1 = collect(/\d/)
		if(val1.length > 2 || parseInt(val1, 10) > 59) {
			units = "hours"
		}
		// 8
		if(line[pos] != ":") {
			$errorHandler("No time unit separator found.")
			return
		}
		pos++
		// 9-11
		val2 = collect(/\d/)
		if(val2.length != 2) {
			$errorHandler("Must be exactly two digits.")
			return
		}
		// 12
		if(units == "hours" || line[pos] == ":") {
			if(line[pos] != ":") {
				$errorHandler("No seconds found or minutes is greater than 59.")
				return
			}
			pos++
			val3 = collect(/\d/)
			if(val3.length != 2) {
				$errorHandler("Must be exactly two digits.")
				return
			}
		} else {
			val3 = val2
			val2 = val1
			val1 = "0"
		}
		// 13
		if(line[pos] != ".") {
			$errorHandler("No decimal separator (\".\") found.")
			return
		}
		pos++
		// 14-16
		val4 = collect(/\d/)
		if(val4.length != 3) {
			$errorHandler("Milliseconds must be given in three digits.")
			return
		}
		// 17
		if(parseInt(val2, 10) > 59) {
			$errorHandler("You cannot have more than 59 minutes.")
			return
		}
		if(parseInt(val3, 10) > 59) {
			$errorHandler("You cannot have more than 59 seconds.")
			return
		}
		return parseInt(val1, 10) * 60 * 60 + parseInt(val2, 10) * 60 + parseInt(val3, 10) + parseInt(val4, 10) / 1000
	}

	/* http://dev.w3.org/html5/webvtt/#parse-the-webvtt-settings */
	function parseSettings(input, cue) {
		var settings = input.split(SPACE),
				seen = []
		for(var i=0; i < settings.length; i++) {
			if(settings[i] == "")
				continue

			var index = settings[i].indexOf(':'),
					setting = settings[i].slice(0, index),
					value = settings[i].slice(index + 1)

			if(seen.indexOf(setting) != -1) {
				$errorHandler("Duplicate setting.")
			}
			seen.push(setting)

			if(value == "") {
				$errorHandler("No value for setting defined.")
				return
			}

			if(setting == "vertical") { // writing direction
				if(value != "rl" && value != "lr") {
					$errorHandler("Writing direction can only be set to 'rl' or 'rl'.")
					continue
				}
				cue.direction = value
			} else if(setting == "line") { // line position
				if(!/\d/.test(value)) {
					$errorHandler("Line position takes a number or percentage.")
					continue
				}
				if(value.indexOf("-", 1) != -1) {
					$errorHandler("Line position can only have '-' at the start.")
					continue
				}
				if(value.indexOf("%") != -1 && value.indexOf("%") != value.length-1) {
					$errorHandler("Line position can only have '%' at the end.")
					continue
				}
				if(value[0] == "-" && value[value.length-1] == "%") {
					$errorHandler("Line position cannot be a negative percentage.")
					continue
				}
				if(value[value.length-1] == "%") {
					if(parseInt(value, 10) > 100) {
						$errorHandler("Line position cannot be >100%.")
						continue
					}
					cue.snapToLines = false
				}
				cue.linePosition = parseInt(value, 10)
			} else if(setting == "position") { // text position
				if(value[value.length-1] != "%") {
					$errorHandler("Text position must be a percentage.")
					continue
				}
				if(parseInt(value, 10) > 100) {
					$errorHandler("Size cannot be >100%.")
					continue
				}
				cue.textPosition = parseInt(value, 10)
			} else if(setting == "size") { // size
				if(value[value.length-1] != "%") {
					$errorHandler("Size must be a percentage.")
					continue
				}
				if(parseInt(value, 10) > 100) {
					$errorHandler("Size cannot be >100%.")
					continue
				}
				cue.size = parseInt(value, 10)
			} else if(setting == "align") { // alignment
				var alignValues = ["start", "middle", "end", "left", "right"]
				if(alignValues.indexOf(value) == -1) {
					$errorHandler("Alignment can only be set to one of " + alignValues.join(", ") + ".")
					continue
				}
				cue.alignment = value
			} else {
				$errorHandler("Invalid setting.")
			}
		}
	}

	this.parse = function(cue, previousCueStart) {
		skip(SPACE)
		cue.startTime = timestamp()
		if(cue.startTime == undefined) {
			return
		}
		if(cue.startTime < previousCueStart) {
			$errorHandler("Start timestamp is not greater than or equal to start timestamp of previous cue.")
		}
		if(NOSPACE.test(line[pos])) {
			$errorHandler("Timestamp not separated from '-->' by whitespace.")
		}
		skip(SPACE)
		// 6-8
		if(line[pos] != "-") {
			$errorHandler("No valid timestamp separator found.")
			return
		}
		pos++
		if(line[pos] != "-") {
			$errorHandler("No valid timestamp separator found.")
			return
		}
		pos++
		if(line[pos] != ">") {
			$errorHandler("No valid timestamp separator found.")
			return
		}
		pos++
		if(NOSPACE.test(line[pos])) {
			$errorHandler("'-->' not separated from timestamp by whitespace.")
		}
		skip(SPACE)
		cue.endTime = timestamp()
		if(cue.endTime == undefined) {
			return
		}
		if(cue.endTime <= cue.startTime) {
			$errorHandler("End timestamp is not greater than start timestamp.")
		}

		if(NOSPACE.test(line[pos])) {
			spaceBeforeSetting = false
		}
		skip(SPACE)
		parseSettings(line.substring(pos), cue)
		return true
	}
	this.parseTimestamp = function() {
		var ts = timestamp()
		if(line[pos] != undefined) {
			$errorHandler("Timestamp must not have trailing characters.")
			return
		}
		return ts
	}
}
