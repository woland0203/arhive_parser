<?php
namespace console\components\translator;



class Translator{
    protected $tarnslator;

    public function __construct()
    {
        $this->tarnslator = new Freeonlinetranslators();
    }

    public function translateHtml($html){
        $textParts = $this->extractText($html);
        $textPartsTranslated = $this->translateParts($textParts['text']);
        return $this->compileHtml($textParts['html'], $textPartsTranslated);
    }

    protected function extractText($html){
        $document = \phpQuery::newDocumentHTML($html);
        $cnt = 0;
        $text = [];
        foreach( $document->find('*')->contents() as $node){

            if($node->nodeType == 3){
                if(!empty( trim($node->textContent) )){
                    $text[$cnt] = $node->textContent;
                    pq($node)->replaceWith( $this->getPlaceholder($cnt) );
                    $cnt++;
                }
            }
        }
        return  [
            'text' => $text,
            'html' => $document->html()
        ];
    }

    protected function translateParts($text){
        $textToTranslate = [];
        $translated = [];
        $len = 0;
        $j = 0;
        foreach ($text as $i => $textElement){
            $len += mb_strlen($textElement);
            if( !isset($textToTranslate[$j]) ) {
                $textToTranslate[$j] = '';
            }
            $textToTranslate[$j] .= $textElement . PHP_EOL . '----------' . $i . '-----------' . PHP_EOL;

            if($len > 3000){
                $len = 0;
                $j++;
            }
        }

        foreach ($textToTranslate as $k => &$textToTranslateItem){
            $textToTranslate[$k] = $this->tarnslator->translate($textToTranslateItem);

            $parts = preg_split('|(----------\d+-----------)|', $textToTranslate[$k], -1, PREG_SPLIT_DELIM_CAPTURE);

            $key = null; $val = null;
            foreach ($parts as $part){
                if(preg_match('|----------(\d+)-----------|', $part, $match)){
                    $key = $match[1];
                }
                else{
                    $val = $part;
                }
                if($key && $val){
                    $translated[$key] = $val;
                    $key = null; $val = null;
                }
            }
        }
     //   print_r($translated);
        return $translated;
    }

    protected function compileHtml($html, $text)
    {
        foreach ($text as $i => $textElement) {
            $html = str_replace($this->getPlaceholder($i), $textElement, $html);
        }
        return $html;
    }


    protected function getPlaceholder($cnt){
        return '-----------_' . $cnt . '_------------';
    }
}