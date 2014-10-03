/**
 * WordFilter.js
 *
 * Copyright, Moxiecode Systems AB
 * Released under LGPL License.
 *
 * License: http://www.tinymce.com/license
 * Contributing: http://www.tinymce.com/contributing
 */

/**
 * This class parses word HTML into proper TinyMCE markup.
 *
 * @class tinymce.pasteplugin.Quirks
 * @private
 */
define("tinymce/pasteplugin/WordFilter", [
	"tinymce/util/Tools",
	"tinymce/html/DomParser",
	"tinymce/html/Schema",
	"tinymce/html/Serializer",
	"tinymce/html/Node",
	"tinymce/pasteplugin/Utils"
], function(Tools, DomParser, Schema, Serializer, Node, Utils) {
	/**
	 * Checks if the specified content is from any of the following sources: MS Word/Office 365/Google docs.
	 */
	function isWordContent(content) {
		return (
			(/<font face="Times New Roman"|class="?Mso|style="[^"]*\bmso-|style='[^'']*\bmso-|w:WordDocument/i).test(content) ||
			(/class="OutlineElement/).test(content) ||
			(/id="?docs\-internal\-guid\-/.test(content))
		);
	}

	/**
	 * Checks if the specified text starts with "1. " or "a. " etc.
	 */
	function isNumericList(text) {
		var found, patterns;

		patterns = [
			/^[IVXLMCD]{1,2}\.[ \u00a0]/,  // Roman upper case
			/^[ivxlmcd]{1,2}\.[ \u00a0]/,  // Roman lower case
			/^[a-z]{1,2}[\.\)][ \u00a0]/,  // Alphabetical a-z
			/^[A-Z]{1,2}[\.\)][ \u00a0]/,  // Alphabetical A-Z
			/^[0-9]+\.[ \u00a0]/,          // Numeric lists
			/^[\u3007\u4e00\u4e8c\u4e09\u56db\u4e94\u516d\u4e03\u516b\u4e5d]+\.[ \u00a0]/, // Japanese
			/^[\u58f1\u5f10\u53c2\u56db\u4f0d\u516d\u4e03\u516b\u4e5d\u62fe]+\.[ \u00a0]/  // Chinese
		];

		text = text.replace(/^[\u00a0 ]+/, '');

		Tools.each(patterns, function(pattern) {
			if (pattern.test(text)) {
				found = true;
				return false;
			}
		});

		return found;
	}

	function isBulletList(text) {
		return /^[\s\u00a0]*[\u2022\u00b7\u00a7\u00d8\u25CF]\s*/.test(text);
	}

	function WordFilter(editor) {
		var settings = editor.settings;

		editor.on('BeforePastePreProcess', function(e) {
			var content = e.content, retainStyleProperties, validStyles;

			retainStyleProperties = settings.paste_retain_style_properties;
			if (retainStyleProperties) {
				validStyles = Tools.makeMap(retainStyleProperties.split(/[, ]/));
			}

			/**
			 * Converts fake bullet and numbered lists to real semantic OL/UL.
			 *
			 * @param {tinymce.html.Node} node Root node to convert children of.
			 */
			function convertFakeListsToProperLists(node) {
				var currentListNode, prevListNode, lastLevel = 1;

				function getText(node) {
					var txt = '';

					if (node.type === 3) {
						return node.value;
					}

					if ((node = node.firstChild)) {
						do {
							txt += getText(node);
						} while ((node = node.next));
					}

					return txt;
				}

				function trimListStart(node, regExp) {
					if (node.type === 3) {
						if (regExp.test(node.value)) {
							node.value = node.value.replace(regExp, '');
							return false;
						}
					}

					if ((node = node.firstChild)) {
						do {
							if (!trimListStart(node, regExp)) {
								return false;
							}
						} while ((node = node.next));
					}

					return true;
				}

				function removeIgnoredNodes(node) {
					if (node._listIgnore) {
						node.remove();
						return;
					}

					if ((node = node.firstChild)) {
						do {
							removeIgnoredNodes(node);
						} while ((node = node.next));
					}
				}

				function convertParagraphToLi(paragraphNode, listName, start) {
					var level = paragraphNode._listLevel || lastLevel;

					// Handle list nesting
					if (level != lastLevel) {
						if (level < lastLevel) {
							// Move to parent list
							if (currentListNode) {
								currentListNode = currentListNode.parent.parent;
							}
						} else {
							// Create new list
							prevListNode = currentListNode;
							currentListNode = null;
						}
					}

					if (!currentListNode || currentListNode.name != listName) {
						prevListNode = prevListNode || currentListNode;
						currentListNode = new Node(listName, 1);

						if (start > 1) {
							currentListNode.attr('start', '' + start);
						}

						paragraphNode.wrap(currentListNode);
					} else {
						currentListNode.append(paragraphNode);
					}

					paragraphNode.name = 'li';

					// Append list to previous list if it exists
					if (level > lastLevel && prevListNode) {
						prevListNode.lastChild.append(currentListNode);
					}

					lastLevel = level;

					// Remove start of list item "1. " or "&middot; " etc
					removeIgnoredNodes(paragraphNode);
					trimListStart(paragraphNode, /^\u00a0+/);
					trimListStart(paragraphNode, /^\s*([\u2022\u00b7\u00a7\u00d8\u25CF]|\w+\.)/);
					trimListStart(paragraphNode, /^\u00a0+/);
				}

				var paragraphs = node.getAll('p');

				for (var i = 0; i < paragraphs.length; i++) {
					node = paragraphs[i];

					if (node.name == 'p' && node.firstChild) {
						// Find first text node in paragraph
						var nodeText = getText(node);

						// Detect unordered lists look for bullets
						if (isBulletList(nodeText)) {
							convertParagraphToLi(node, 'ul');
							continue;
						}

						// Detect ordered lists 1., a. or ixv.
						if (isNumericList(nodeText)) {
							// Parse OL start number
							var matches = /([0-9]+)\./.exec(nodeText);
							var start = 1;
							if (matches) {
								start = parseInt(matches[1], 10);
							}

							convertParagraphToLi(node, 'ol', start);
							continue;
						}

						// Convert paragraphs marked as lists but doesn't look like anything
						if (node._listLevel) {
							convertParagraphToLi(node, 'ul', 1);
							continue;
						}

						currentListNode = null;
					}
				}
			}

			function filterStyles(node, styleValue) {
				var outputStyles = {}, matches, styles = editor.dom.parseStyle(styleValue);

				Tools.each(styles, function(value, name) {
					// Convert various MS styles to W3C styles
					switch (name) {
						case 'mso-list':
							// Parse out list indent level for lists
							matches = /\w+ \w+([0-9]+)/i.exec(styleValue);
							if (matches) {
								node._listLevel = parseInt(matches[1], 10);
							}

							// Remove these nodes <span style="mso-list:Ignore">o</span>
							// Since the span gets removed we mark the text node and the span
							if (/Ignore/i.test(value) && node.firstChild) {
								node._listIgnore = true;
								node.firstChild._listIgnore = true;
							}

							break;

						case "horiz-align":
							name = "text-align";
							break;

						case "vert-align":
							name = "vertical-align";
							break;

						case "font-color":
						case "mso-foreground":
							name = "color";
							break;

						case "mso-background":
						case "mso-highlight":
							name = "background";
							break;

						case "font-weight":
						case "font-style":
							if (value != "normal") {
								outputStyles[name] = value;
							}
							return;

						case "mso-element":
							// Remove track changes code
							if (/^(comment|comment-list)$/i.test(value)) {
								node.remove();
								return;
							}

							break;
					}

					if (name.indexOf('mso-comment') === 0) {
						node.remove();
						return;
					}

					// Never allow mso- prefixed names
					if (name.indexOf('mso-') === 0) {
						return;
					}

					// Output only valid styles
					if (retainStyleProperties == "all" || (validStyles && validStyles[name])) {
						outputStyles[name] = value;
					}
				});

				// Convert bold style to "b" element
				if (/(bold)/i.test(outputStyles["font-weight"])) {
					delete outputStyles["font-weight"];
					node.wrap(new Node("b", 1));
				}

				// Convert italic style to "i" element
				if (/(italic)/i.test(outputStyles["font-style"])) {
					delete outputStyles["font-style"];
					node.wrap(new Node("i", 1));
				}

				// Serialize the styles and see if there is something left to keep
				outputStyles = editor.dom.serializeStyle(outputStyles, node.name);
				if (outputStyles) {
					return outputStyles;
				}

				return null;
			}

			if (settings.paste_enable_default_filters === false) {
				return;
			}

			// Detect is the contents is Word junk HTML
			if (isWordContent(e.content)) {
				e.wordContent = true; // Mark it for other processors

				// Remove basic Word junk
				content = Utils.filter(content, [
					// Word comments like conditional comments etc
					/<!--[\s\S]+?-->/gi,

					// Remove comments, scripts (e.g., msoShowComment), XML tag, VML content,
					// MS Office namespaced tags, and a few other tags
					/<(!|script[^>]*>.*?<\/script(?=[>\s])|\/?(\?xml(:\w+)?|img|meta|link|style|\w:\w+)(?=[\s\/>]))[^>]*>/gi,

					// Convert <s> into <strike> for line-though
					[/<(\/?)s>/gi, "<$1strike>"],

					// Replace nsbp entites to char since it's easier to handle
					[/&nbsp;/gi, "\u00a0"],

					// Convert <span style="mso-spacerun:yes">___</span> to string of alternating
					// breaking/non-breaking spaces of same length
					[/<span\s+style\s*=\s*"\s*mso-spacerun\s*:\s*yes\s*;?\s*"\s*>([\s\u00a0]*)<\/span>/gi,
						function(str, spaces) {
							return (spaces.length > 0) ?
								spaces.replace(/./, " ").slice(Math.floor(spaces.length / 2)).split("").join("\u00a0") : "";
						}
					]
				]);

				var validElements = settings.paste_word_valid_elements;
				if (!validElements) {
					validElements = '-strong/b,-em/i,-span,-p,-ol,-ul,-li,-h1,-h2,-h3,-h4,-h5,-h6,-p/div,' +
						'-table[width],-tr,-td[colspan|rowspan|width],-th,-thead,-tfoot,-tbody,-a[href|name],sub,sup,strike,br,del';
				}

				// Setup strict schema
				var schema = new Schema({
					valid_elements: validElements,
					valid_children: '-li[p]'
				});

				// Add style/class attribute to all element rules since the user might have removed them from
				// paste_word_valid_elements config option and we need to check them for properties
				Tools.each(schema.elements, function(rule) {
					if (!rule.attributes["class"]) {
						rule.attributes["class"] = {};
						rule.attributesOrder.push("class");
					}

					if (!rule.attributes.style) {
						rule.attributes.style = {};
						rule.attributesOrder.push("style");
					}
				});

				// Parse HTML into DOM structure
				var domParser = new DomParser({}, schema);

				// Filter styles to remove "mso" specific styles and convert some of them
				domParser.addAttributeFilter('style', function(nodes) {
					var i = nodes.length, node;

					while (i--) {
						node = nodes[i];
						node.attr('style', filterStyles(node, node.attr('style')));

						// Remove pointess spans
						if (node.name == 'span' && node.parent && !node.attributes.length) {
							node.unwrap();
						}
					}
				});

				// Check the class attribute for comments or del items and remove those
				domParser.addAttributeFilter('class', function(nodes) {
					var i = nodes.length, node, className;

					while (i--) {
						node = nodes[i];

						className = node.attr('class');
						if (/^(MsoCommentReference|MsoCommentText|msoDel|MsoCaption)$/i.test(className)) {
							node.remove();
						}

						node.attr('class', null);
					}
				});

				// Remove all del elements since we don't want the track changes code in the editor
				domParser.addNodeFilter('del', function(nodes) {
					var i = nodes.length;

					while (i--) {
						nodes[i].remove();
					}
				});

				// Keep some of the links and anchors
				domParser.addNodeFilter('a', function(nodes) {
					var i = nodes.length, node, href, name;

					while (i--) {
						node = nodes[i];
						href = node.attr('href');
						name = node.attr('name');

						if (href && href.indexOf('#_msocom_') != -1) {
							node.remove();
							continue;
						}

						if (href && href.indexOf('file://') === 0) {
							href = href.split('#')[1];
							if (href) {
								href = '#' + href;
							}
						}

						if (!href && !name) {
							node.unwrap();
						} else {
							// Remove all named anchors that aren't specific to TOC, Footnotes or Endnotes
							if (name && !/^_?(?:toc|edn|ftn)/i.test(name)) {
								node.unwrap();
								continue;
							}

							node.attr({
								href: href,
								name: name
							});
						}
					}
				});

				// Parse into DOM structure
				var rootNode = domParser.parse(content);

				// Process DOM
				convertFakeListsToProperLists(rootNode);

				// Serialize DOM back to HTML
				e.content = new Serializer({}, schema).serialize(rootNode);
			}
		});
	}

	WordFilter.isWordContent = isWordContent;

	return WordFilter;
});