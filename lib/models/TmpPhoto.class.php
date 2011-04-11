<?php

DB_Conn::prepare('tmp-photo-cleanup', 'DELETE FROM tmp_photos WHERE (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(created_at)) > ?');

/**
 * This file was autogenerated by exporter.
 * @property string key
 * @property integer place_id
 * @property string name
 * @property string data_hash

 * @method TmpPhoto open() Find and open a record based on primary key.
 * @method TmpPhoto create() Create a new record and return object.
 */
class TmpPhoto extends DB_Record
{
	public static $table = "tmp_photos";

	public static $fields = array(
	    'key' => array('pk' => true,),
	    'name' ,
	    'data_hash' ,
		'created_at' => array('type' => 'datetime',),	
	);

	public function fpath() {
		return Registry::get('photos.tmp-directory') . '/' . $this->fname();
	}

	public function fname() {
		return "{$this->data_hash}.jpg";
	}

	public function move_at_place(Place $p) {

		$image = new Image($this->fpath());

		$photo = Photo::create(array(
		 	'place_id' => $p->id,
			'name' => $this->name,
		 	'width' => $image->get_meta_info('width'),
			'height' => $image->get_meta_info('height'),
			'data_hash' => $this->data_hash,
		));
		if (!$photo)
		return;

		// Move file
		rename($this->fpath(), $photo->fpath());
		
		// Delete record
		$this->delete();
		return $photo;
	}

	public static function cleanUpExpired() {
		DB_Conn::execute_fetch_all('tmp-photo-cleanup', array(Registry::get('photos.tmp.max-life'))); 
	}

}

TmpPhoto::events()->connect('op.pre.create', function ($e){ 
	$e->filtered_value["created_at"] = new DateTime();
});

TmpPhoto::events()->connect('op.pre.delete', function($e) {
	$p = $e->arguments["record"];
	$total_uses = TmpPhoto::raw_query()->select(array('key'))
		->where('data_hash = ?')
		->execute($p->data_hash);
	if (count($total_uses) <= 1)
		@unlink($p->fpath());
});