<?php

DB_Conn::prepare('photo-cleanup-expired', 'DELETE FROM photos WHERE place_id is NULL AND (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(created_at)) > ?');

/**
 * This file was autogenerated by exporter.
 * @property integer id
 * @property string secret
 * @property integer place_id
 * @property string name
 * @property integer width
 * @property integer height
 * @property integer size
 * @property string data_hash
 * @property DateTime created_at

 * @method Photo open() Find and open a record based on primary key.
 * @method Photo create() Create a new record and return object.
 */
class Photo extends DB_Record
{
	public static $table = "photos";

	public static $fields = array(
	    'id' => array('pk' => true,'ai' => true,),
		'secret',
	    'place_id' => array('fk' => 'Place'),
	    'name' ,
	    'width' ,
	    'height' ,
		'size',
	    'data_hash',
		'created_at' => array('type' => 'datetime')
	);
	
	/**
	 * Open a temporary photo based on the secret
	 * @param string $secret The secret key that is attached to the photo.
	 * @return Photo The photo object found.
	 */
	public static function open_temporary($secret) {
		$q = Photo::open_query()
			->where('place_id IS NULL')
			->where('secret = ?');
		$photos = $q->execute($secret);
		
		if (count($photos) != 1)
			return null;
		return $photos[0];
	}
	
	/**
	 * Create a new (temporary) photo from an image file.
	 * @param string $name The name of the photo.
	 * @param string $fname	The path to the image file.
	 * @param Image $img [OUT] Return the image object used for this image.
	 */
	public static function create_from_file($name, $fname, & $img) {
		
		// Try to open image
		try {
			$img = @new Image($fname);
		}catch(Exception $e) {
			return null;
		}
		
		// Create image
		$img_data = $img->data(array('format' => IMAGETYPE_JPEG, 'quality' => 93));
		$data_hash = md5($img_data);
	
		// Create entry
		$photo = Photo::create(array(		
			'name' => $name,
			'secret' => md5($data_hash . rand(1, getrandmax())),
		 	'width' => $img->get_meta_info('width'),
			'height' => $img->get_meta_info('height'),
			'size' => strlen($img_data),
			'data_hash' => $data_hash,
		));
		if (!$photo)
			return null;
			
		// Save image
		file_put_contents($photo->fpath(), $img_data);
		return $photo;
	}
	
	/**
	 * Check if a thumbnail exists on the filesystem.
	 * @param integer $w The width of the thumbnail.
	 * @param integer $h The height of the thumbnail.
	 */
	public function has_thumb($w, $h) {
		return file_exists($this->thumb_fpath($w, $h));
	}
	
	/**
	 * Generate a thumbnail, if it does not exist.
	 * @param integer $w The width of the thumbnail.
	 * @param integer $h The height of the thumbnail.
	 */
	public function generate_thumb($w, $h) {
		if ($this->has_thumb($w, $h))
			return;
		$image = new Image($this->fpath());
		$image->resize($w, $h);
		file_put_contents($this->thumb_fpath($w, $h), $image->data());
	}
	
	/**
	 * Get the file name of a specific thumbnail.
	 * @param integer $w The width of the thumbnail.
	 * @param integer $h The height of the thumbnail.
	 */
	public function thumb_fname($w, $h) {
		return "{$this->data_hash}_thumb_{$w}x{$h}.jpg";
	}
	
	/**
	 * Get the file path of a specific thumbnail.
	 * @param integer $w The width of the thumbnail.
	 * @param integer $h The height of the thumbnail.
	 */
	public function thumb_fpath($w, $h) {
		if ($this->is_temporary())
			return '';
		return Registry::get('photos.directory') . '/' . $this->thumb_fname($w, $h);
	}
	
	/**
	 * The filename that is used to save original image
	 */
	public function fname() {
		return "{$this->data_hash}.jpg";
	}
	
	/**
	 * Get the full path that image is stored
	 */
	public function fpath() {
		return  $this->get_storage_folder() . '/' . $this->fname();
	}

	/**
	 * Clean up all temporary expired photos.
	 */
	public static function clean_up_expired() {
		$d = DB_Conn::execute_fetch_all('photo-cleanup-expired', array(Registry::get('photos.tmp.max-life')));
	}
	
	/**
	 * Attach this temporary photo to a place.
	 * @param Place $p	The place to be attached at.
	 * @throws RuntimeException
	 */
	public function attach_at_place(Place $p) {
		if ($this->place_id != null)
			throw new RuntimeException('Cannot move a photo which already has been moved.');

		$orig_path = $this->fpath();
		
		$this->place_id = $p->id;
		$this->save();
		
		// Move file
		return rename($orig_path, $this->fpath());
	}
	
	/**
	 * Return if this is a temporary photo that has not
	 * been attached to a place yet
	 */
	public function is_temporary() {
		return $this->place_id == null;
	}
	
	/**
	 * Depending in which stage the photo is (temporary, attached) a
	 * different folder is used for storage.
	 */
	public function get_storage_folder() {
		return $this->is_temporary()
			?Registry::get('photos.tmp-directory')
			:Registry::get('photos.directory');
	}
	
	/**
	 * Get an array formated in the collectopia API format.
	 * @return array Associative array with all photo ready to be jsoned
	 */
	public function to_api() {
		$original = $this->toArray();
		
		// Return only permitted values by the API
		$filtered = array_intersect_key($original, array(
			'id' => 0,
			'secret' => 0,
			'name' => 0,
			'width' => 0,
			'height' => 0,
			'size' => 0,	    	
		));
		
		// Filter per case
		if ($this->is_temporary())
			unset($filtered['id'], $filtered['place_id']);
		else
			unset($filtered['secret']);
		
		// Add some renamed fields
		$filtered['hash'] = $original['data_hash'];
		
		
		return $filtered;		    
	}
}

Place::one_to_many('Photo', 'place', 'photos');

Photo::events()->connect('op.pre.create', function ($e){ 
	// Initialize created_at to NOW
	$e->filtered_value["created_at"] = new DateTime();
});

Photo::events()->connect('op.pre.delete', function($e) {
	$p = $e->arguments["record"];
	
	// If it was a temporary it should be deleted if it was the last one
	if ($p->is_temporary()) {
		$total_uses = Photo::raw_query()->select(array('secret'))
			->where('place_id IS NULL')
			->where('data_hash = ?')
			->execute($p->data_hash);
		if (count($total_uses) <= 1)
			@unlink($p->fpath());
	}
});
