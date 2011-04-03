<?php

//! Specialization of search engine for 
class SearchIndex
{
	//! Instance of zend search engine
	public $engine = null;
	
	//! Construct wrapper for backend engine
	public function __construct($engine)
	{
		$this->engine = $engine;
	}
	
	public function addCity($city)
	{
		
	}
	
	//! Add a new place in index
	public function addPlace(Place $place)
	{
		$splace = new Zend_Search_Lucene_Document();
 
		$splace->addField(Zend_Search_Lucene_Field::UnIndexed('id', $place->id));
		$splace->addField(Zend_Search_Lucene_Field::Text('name', $place->name), 'utf8');
		$splace->addField(Zend_Search_Lucene_Field::Text('country', $place->country, 'utf8'));
		$splace->addField(Zend_Search_Lucene_Field::Text('city', $place->city, 'utf8'));
		$splace->addField(Zend_Search_Lucene_Field::Text('address', $place->address, 'utf8'));
		$splace->addField(Zend_Search_Lucene_Field::UnStored('description', $place->description, 'utf8'));
		 
		// Add document to the index
		$this->engine->addDocument($splace);
	}
	
	// Open an existing index or create it.
	static public function open()
	{
		$index_dir = Registry::get('search.index-directory');
		try{
			return new SearchIndex(Zend_Search_Lucene::open($index_dir));
		} catch(Zend_Search_Lucene_Exception $e) {
			return SearchIndex::create($index_dir);
		}
	}
	
	// Create index	
	public static function create()
	{
		$index_dir = Registry::get('search.index-directory');
		$engine = Zend_Search_Lucene::create($index_dir);
		$search = new self($engine);
		foreach(Place::open_all() as $p)
			$search->addPlace($p);
		$engine->optimize();		
		return $search;
	}
}