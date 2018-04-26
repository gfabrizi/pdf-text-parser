<?php

namespace Skuola\PdfTextParser;

use Symfony\Component\DomCrawler\Crawler;

class Converter
{
    private $rows = [];

    public function __construct(?string $data, ?string $path = null, ?int $startPage = 0, ?int $numOfPages = 1)
    {
        if (null === $data && null === $path) {
            throw new \InvalidArgumentException('You must pass data or a file path.');
        }
        if (null !== $path && false === $data = @file_get_contents($path)) {
            throw new \UnexpectedValueException('Cannot read file.');
        }
        $crawler = new Crawler($data);
        $pages = $crawler->filter('body > doc > page');
        $ws = [];
        foreach ($pages as $number => $domElement) {
            if ($number >= $startPage && $number < $startPage + $numOfPages) {
                foreach ($domElement->childNodes as $word) {
                    $ws[] = [
                        'text' => Cleaner::clear($word->nodeValue),
                        'xmin' => (float) $word->attributes->getNamedItem('xmin')->value,
                        'ymin' => (float) $word->attributes->getNamedItem('ymin')->value,
                        'xmax' => (float) $word->attributes->getNamedItem('xmax')->value,
                        'ymax' => (float) $word->attributes->getNamedItem('ymax')->value,
                    ];
                }
            }
        }
        foreach ($ws as $n => $w) {
            if ($n < 1 || $w['xmin'] < $ws[$n - 1]['xmax']) {
                $row = new Row();
                $this->rows[] = $row;
            }
            new Word($w['text'], $row, $w['xmin'], $w['xmax'], $w['ymin'], $w['ymax']);
        }
    }

    public function getAsText(): string
    {
        $output = '';
        foreach ($this->rows as $row) {
            $output .= $row.PHP_EOL;
        }

        return $output;
    }

    public function getAsHtml(): string
    {
        $output = '';
        foreach ($this->rows as $row) {
            $output .= $row->isTitle() ? '<h2>'.$row.'</h2>' : '<p>'.$row.'</p>';
        }

        return $output;
    }
}