<?php
/*
MIT License

Copyright (c) 2019 Jonathan Barda

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

class libVirtXML {
	// Slightly modified version from: https://stackoverflow.com/a/20506281
	public function dom2xml($domNode) {
		foreach($domNode->childNodes as $node) {
			if ($node->hasChildNodes()) {
				$this->dom2xml($node);
			}
			else {
				if ($domNode->hasAttributes() && strlen($domNode->nodeValue)) {
					$domNode->setAttribute("nodeValue", $node->textContent);
					$node->nodeValue = "";
				}
			}
		}
	}

	// Slightly modified version from: https://stackoverflow.com/a/20506281
	public function xml2json($xml, $as_array = false, $pretty_print = false) {
		// Create a new DOM document and load XML into it
		$dom = new DOMDocument();
		$dom->loadXML($xml);

		// Read and format the XML string
		$this->dom2xml($dom);

		// Convert the XML string into an SimpleXMLElement object.
		$xmlObject = simplexml_load_string($dom->saveXML());

		// Encode the SimpleXMLElement object into a JSON string.
		if ($pretty_print === true) {
			$jsonString = str_replace(['@', '"\n"'], ['', '""'], json_encode($xmlObject, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
		}
		else {
			$jsonString = str_replace(['@', '"\n"'], ['', '""'], json_encode($xmlObject, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES));
		}

		// Convert it back into an associative array for the purposes of testing.
		$jsonArray = json_decode($jsonString, true);

		// Return an array or a string
		return ($as_array === true ? $jsonArray : $jsonString);
	}
}