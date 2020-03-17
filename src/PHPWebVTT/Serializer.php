<?php 
class Serializer {

	public function serializeTree($tree) {

			var result = ""
			for (var i = 0; i < tree.length; i++) {
				var node = tree[i]
				if(node.type == "text") {
					result += node.value
				} else if(node.type == "object") {
					result += "<" + node.name
					if(node.classes) {
						for(var y = 0; y < node.classes.length; y++) {
							result += "." + node.classes[y]
						}
					}
					if(node.value) {
						result += " " + node.value
					}
					result += ">"
					if(node.children)
						result += serializeTree(node.children)
					result += "</" + node.name + ">"
				} else {
					result += "<" + node.value + ">"
				}
			}
			return result
		}
		function serializeCue(cue) {
			return cue.startTime + " " + cue.endTime + "\n" + serializeTree(cue.tree.children) + "\n\n"
		}
		this.serialize = function(cues) {
			var result = ""
			for(var i=0;i<cues.length;i++) {
				result += serializeCue(cues[i])
			}
			return result
		}

	}

}
