<?php
class ElsevierBridge extends BridgeAbstract{
	public $maintainer = 'Pierre Mazière';
	public $name = 'Elsevier journals recent articles';
	public $uri = 'http://www.journals.elsevier.com/';
	public $description = 'Returns the recent articles published in Elsevier journals';

    public $parameters = array( array(
        'j'=>array(
            'name'=>'Journal name',
            'required'=>true,
            'exampleValue'=>'academic-pediactrics',
            'title'=>'Insert html-part of your journal'
        )
    ));

	// Extracts the list of names from an article as string
	private function ExtractArticleName ($article){
		$names = $article->find('small', 0);
		if($names)
			return trim($names->plaintext);
		return '';
	}

	// Extracts the timestamp from an article
	private function ExtractArticleTimestamp ($article){
		$time = $article->find('.article-info', 0);
		if($time){
			$timestring = trim($time->plaintext);
			/*
				The format depends on the age of an article:
				- Available online 29 July 2016
				- July 2016
				- May–June 2016
			*/
			if(preg_match('/\S*(\d+\s\S+\s\d{4})/ims', $timestring, $matches)){
				return strtotime($matches[0]);
			} elseif (preg_match('/[A-Za-z]+\-([A-Za-z]+\s\d{4})/ims', $timestring, $matches)){
				return strtotime($matches[0]);
			} elseif (preg_match('/([A-Za-z]+\s\d{4})/ims', $timestring, $matches)){
				return strtotime($matches[0]);
			} else {
				return 0;
			}
		}
		return 0;
	}

	// Extracts the content from an article
	private function ExtractArticleContent ($article){
		$content = $article->find('.article-content', 0);
		if($content){
			return trim($content->plaintext);
		}
		return '';
	}

	public function collectData(){
		$uri = $this->uri . $this->getInput('j') . '/recent-articles/';
		$html = $this->getSimpleHTMLDOM($uri) or $this->returnServerError('No results for Elsevier journal '.$this->getInput('j'));

		foreach($html->find('.pod-listing') as $article){
			$item = array();
			$item['uri'] = $article->find('.pod-listing-header>a',0)->getAttribute('href').'?np=y';
			$item['title'] = $article->find('.pod-listing-header>a',0)->plaintext;
			$item['author'] = $this->ExtractArticleName($article);
			$item['timestamp'] = $this->ExtractArticleTimestamp($article);
			$item['content'] = $this->ExtractArticleContent($article);
			$this->items[] = $item;
		}
	}

	public function getCacheDuration(){
		return 43200; // 12h
	}
}
?>
