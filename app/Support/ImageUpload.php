<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use Illuminate\Http\UploadedFile;

class ImageUpload
{
    public function validationError(UploadedFile $file): ?string
    {
        if ($file->getMimeType() !== 'image/svg+xml') {
            return null;
        }
        $contents = file_get_contents($file->getRealPath());
        if (preg_match('/<!DOCTYPE|<!ENTITY/i', $contents)) {
            return 'SVG images must not contain document types or entities.';
        }
        $previous = libxml_use_internal_errors(true);
        try {
            $document = new DOMDocument;
            if (! $document->loadXML($contents, LIBXML_NONET) || $document->documentElement?->localName !== 'svg') {
                return 'The SVG image is not valid.';
            }
            if ((new \DOMXPath($document))->query('//processing-instruction()')->length > 0) {
                return 'SVG images must not reference external stylesheets.';
            }
            $allowed = ['svg', 'style', 'g', 'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon', 'defs', 'clippath', 'mask', 'lineargradient', 'radialgradient', 'stop', 'title', 'desc', 'use', 'symbol', 'text', 'tspan', 'pattern', 'filter', 'fegaussianblur', 'feoffset', 'feblend', 'fecolormatrix', 'femerge', 'femergenode', 'fecomponenttransfer', 'fefunca', 'fefuncb', 'fefuncg', 'fefuncr', 'fedropshadow', 'feflood', 'fecomposite', 'femorphology', 'feturbulence', 'fedisplacementmap'];
            foreach ($document->getElementsByTagName('*') as $element) {
                if (! $element instanceof DOMElement || ! in_array(strtolower($element->localName), $allowed, true) || ($element->namespaceURI && $element->namespaceURI !== 'http://www.w3.org/2000/svg')) {
                    return 'Upload a static SVG without scripts, embedded HTML or external content.';
                }
                if ($element->localName === 'style' && preg_match('/javascript\s*:|expression\s*\(|@import|url\s*\(\s*[\'"]?(?!#)[^\s\'"]/i', $element->textContent)) {
                    return 'SVG styles must not contain scripts or external references.';
                }
                foreach ($element->attributes as $attribute) {
                    $name = strtolower($attribute->localName);
                    $value = trim($attribute->value);
                    if (str_starts_with($name, 'on') || $name === 'base' || ($name === 'href' && ! str_starts_with($value, '#')) || preg_match('/javascript\s*:|expression\s*\(|@import|url\s*\(\s*[\'"]?(?!#)[^\s\'"]/i', $value)) {
                        return 'Upload a static SVG without scripts, event handlers or external references.';
                    }
                }
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return null;
    }

    /** @return array{width: ?int, height: ?int} */
    public function dimensions(UploadedFile $file): array
    {
        $dimensions = @getimagesize($file->getRealPath());

        return ['width' => $dimensions ? $dimensions[0] : null, 'height' => $dimensions ? $dimensions[1] : null];
    }
}
