<?php
/**
 * indexed
 *
 * Copyright (c) 2013 David Persson. All rights reserved.
 *
 * Use of this source code is governed by a BSD-style
 * license that can be found in the LICENSE file.
 */

namespace indexed;

use Exception;
use DomDocument;

class Siteindex extends Site {

	protected static $_namespaces = array(
		'index' => array(
			'prefix' => null,
			'version' => '0.9',
			'uri' => 'http://www.sitemaps.org/schemas/sitemap/{:version}',
			'schema' => 'http://www.sitemaps.org/schemas/sitemap/{:version}/site{:name}.xsd'
		)
	);

	/**
	 * Adds a sitemap to the siteindex.
	 *
	 * @param string $url An absolute or fully qualified URL for the item to be added.
	 * @param array $options Additional options for the item:
	 *                       - modified
	 *                       - title
	 *                         Used as a comment.
	 */
	public function sitemap($url, array $options = array()) {
		$defaults = array(
			'modified' => null,
			'title' => null
		);
		if (strpos($url, '://') === false) {
			$url = $this->_base . $url;
		}
		if (isset($this->_data[$url])) {
			throw new Exception("Will not overwrite sitemap with URL `{$url}`; already added.");
		}
		$this->_data[] = compact('url') + $options + $defaults;
	}

	public function generate() {
		if (count($this->_data) > static::MAX_ITEMS) {
			throw new Exception('Too many items.');
		}
		$result = $this->_generate();

		if (strlen($result) > static::MAX_SIZE) {
			throw new Exception('Result document exceeds allowed size.');
		}
		return $result;
	}

	// @link http://support.google.com/webmasters/bin/answer.py?hl=en&answer=75712
	protected function _generate() {
		$Document = new DomDocument('1.0', 'UTF-8');
		$namespaces = static::$_namespaces;

		$Set = $Document->createElementNs(
			$namespaces['index']['uri'], 'sitemapindex'
		);
		$Set->setAttributeNs(
			'http://www.w3.org/2001/XMLSchema-instance',
			'xsi:schemaLocation',
			"{$namespaces['index']['uri']} {$namespaces['index']['schema']}"
		);

		foreach ($this->_data as $item) {
			$Map = $Document->createElement('sitemap');

			if ($item['title']) {
				$Map->appendChild($Document->createComment($item['title']));
			}
			$Map->appendChild($this->_safeLocElement($item['url'], $Document));

			if ($item['modified']) {
				$Map->appendChild($Document->createElement('lastmod', date('c', strtotime($item['modified']))));
			}
			$Set->appendChild($Map);
		}
		$Document->appendChild($Set);

		$Document->formatOutput = $this->debug;
		return $Document->saveXml();
	}
}

?>