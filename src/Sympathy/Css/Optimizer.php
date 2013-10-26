<?php

namespace Sympathy\Css;

/**
 * This filter optimizes your CSS by removing duplicates
 *
 * Usage:
 *
 * $optimizer = new \Sympathy\Css\Optimizer;
 *
 * $inputCss = 'body { foo:bar; } body { baz:foo; } body { foo: new }';
 *
 * $outputCss = $optimizer->optimizeCss($inputCss);
 *
 * @author Michael Mayer <michael@liquidbytes.net>
 * @package Sympathy
 * @license MIT
 */

class Optimizer
{
    /**
     * @var array
     */
    protected $counts;

    /**
     * Returns information about the CSS optimization
     *
     * @return array
     * @throws \LogicException
     */
    public function getCounts()
    {
        if(!$this->counts) {
            throw new \LogicException ('Counts are available after calling optimizeCss()');
        }

        return $this->counts;
    }

    /**
     * Returns optimized CSS code
     *
     * @param string $originalCSS
     * @return string
     */
    public function optimizeCss($originalCSS)
    {
        $counts = array(
            'skipped' => 0,
            'merged' => 0,
            'properties' => 0,
            'selectors' => 0,
            'nested' => 0,
            'unoptimized' => 0
        );

        $propertyBlacklist = array();

        $optimizedCSS = '';
        $allSelectors = array();
        $untouchedBlocks = array();
        $propertyHashes = array();
        $optimizedRules = array();

        $blocks = explode('}', $originalCSS);

        for($blockNumber = 0; $blockNumber < count($blocks); $blockNumber++) {
            $block = trim($blocks[$blockNumber]);
            $parts = explode('{', $block);

            if($block == '') {
                // Nothing to do
                continue;
            }

            if(count($parts) != 2) {
                $nested = $block;

                while($blockNumber < count($blocks) && trim($blocks[$blockNumber]) != '') {
                    $blockNumber++;
                    $nested .= '}' . trim($blocks[$blockNumber]);
                }

                $nested .= '}';
                $untouchedBlocks[] = $nested;
                $counts['nested']++;
                continue;
            }

            if(strpos($block, '@') === 0) {
                $untouchedBlocks[] = $block . '}';
                $counts['unoptimized']++;
                continue;
            }

            $selectors = explode(',', $parts[0]);
            $properties = explode(';', $parts[1]);

            if(count($properties) == 0) {
                // Nothing to do
                $counts['skipped']++;
                continue;
            }

            $newProperties = array();
            $propertyName = '';
            $validProperty = false;

            foreach($properties as $property) {
                $property = trim($property);
                $strpos = strpos($property, ':');
                $hasPropertyName = ($strpos !== false);

                if($hasPropertyName) {
                    $propertyName = trim(substr($property, 0, $strpos));
                    $propertyValue = trim(substr($property, $strpos + 1));
                    $validProperty = !isset($propertyBlacklist[$propertyName]) || $propertyBlacklist[$propertyName] != $propertyValue;
                }

                if($validProperty && $propertyName) {
                    if($hasPropertyName) {
                        $newProperties[$propertyName] = $propertyName . ':' . $propertyValue;
                    } elseif($property != '') {
                        // Base64 image data
                        $newProperties[$propertyName] .= ';' . $property;
                    }
                }

                $counts['properties']++;
            }

            foreach($selectors as $selector) {
                $selector = trim($selector);
                $counts['selectors']++;

                if(isset($allSelectors[$selector])) {
                    $mergedProperties = array_merge($allSelectors[$selector], $newProperties);
                    $counts['merged']++;
                } else {
                    $mergedProperties = $newProperties;
                }

                ksort($mergedProperties);

                $allSelectors[$selector] = $mergedProperties;
            }
        }

        foreach($allSelectors as $selector => $properties) {
            $hash = md5(print_r($properties, true));

            if(!isset($propertyHashes[$hash])) {
                $propertyHashes[$hash] = array();
            }

            $propertyHashes[$hash][] = $selector;
        }

        foreach($propertyHashes as $selectors) {
            sort($selectors);
            $mainSelector = $selectors[0];
            $propertiesString = implode(';', $allSelectors[$mainSelector]);
            $selectorsString = implode(',', $selectors);
            $optimizedRules[$selectorsString] = $propertiesString;
        }

        foreach($untouchedBlocks as $untouchedBlock) {
            $optimizedCSS .= $untouchedBlock;
        }

        foreach($optimizedRules as $selectorsString => $propertiesString) {
            $optimizedCSS .= $selectorsString . '{' . $propertiesString .'}';
        }

        $this->counts = $counts;

        return $optimizedCSS;
    }
}