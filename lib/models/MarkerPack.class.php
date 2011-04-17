<?php

/**
 * This file was autogenerated by exporter.
 * @property string hash
 * @property integer total
 * @property float center_lng
 * @property float center_lat
 * @method MarkerPack open() Find and open a record based on primary key.
 * @method MarkerPack create() Create a new record and return object.
 */
class MarkerPack extends DB_Record
{
	public static $table = "marker_packs";

	public static $fields = array(
	    'hash' => array('pk' => true),
		'center_lng',
		'center_lat',
	    'total'
    );
    
    /**
     * Create a new pack on a specific location
     * @param float $lat The latitude of the location.
     * @param float $lng The longtitude of the location.
     */
    public static function create_at($lat, $lng) {
    	return $pack = MarkerPack::create(array(
    			'hash' => md5($lat . $lng . rand(0, getrandmax())),
    			'center_lng' => $lng,
    			'center_lat' => $lat,
    			'total' => 0
    		)); 
    }
    
    /**
     * Get the filename for this pack.
     */
    public function fname() {
    	return "{$this->hash}.png";
    }
    
	/**
     * Get the system filepath for this pack.
     */
    public function fpath() {
    	return Registry::get('markers.directory') . '/' . $this->fname();
    }

    /**
     * Append a marker set to this pack
     * @param Image $normal The normal marker image of this set.
     * @param Image $focused The focus marker image of this set.
     */
    public function append_markers_set(Image $normal, Image $focused) {
    	$x_step = 60;
    	$y_step = 56;
    	
    	// Check for it is the first image then create pack
    	if ($this->total == 0){
			$img = imagecreatetruecolor($x_step * 3, $y_step);
			imagealphablending($img, false);
    	} else {
    		if (!file_exists($this->fpath())) {
    			error_log('Cannot find marker pack image file.');    		
    			return false;
    		}
    		$orig = imagecreatefrompng($this->fpath());
    		$img = imagecreatetruecolor($x_step * 3, $y_step * ($this->total + 1));
			imagealphablending($img, false);
			imagecopy($img, $orig , 0, 0, 0, 0, imagesx($orig), imagesy($orig));
    	}
    	
    	// Append marker images at the end
    	imagecopy($img, $normal->gd_handle(), 0, $y_step * $this->total, 0, 0,
    		$normal->get_meta_info('width'), $normal->get_meta_info('height'));
    	imagecopy($img, $focused->gd_handle(), $x_step, $y_step * $this->total, 0, 0,
    		$focused->get_meta_info('width'), $focused->get_meta_info('height'));
    	
    	imagesavealpha($img, true);
    	imagepng($img, $this->fpath());
    	
    	// Update totals
    	$this->total += 1;
    	$this->save();
    	
    	// Return index
    	return $this->total - 1;
    }
    
    /**
     * Update a marker set inside this pack
     * @param $index The index of the marker set ti be updated.
     * @param Image $normal The new normal marker image.
     * @param Image $focused The new focus marker image.
     */
    public function update_markers_set($index, Image $normal, Image $focused) {
    	$x_step = 60;
    	$y_step = 56;
    	
    	// Check for it is the first image then create pack
    	if ($index >= $this->total){
    		error_log("Requested to update out-of-borders marker set at pack {$this->hash}.");
    		return false;
    	}
		
    	// Open image
    	$img = imagecreatefrompng($this->fpath());
    	imagealphablending($img, false);
    	
		// Clean up area
    	imagefilledrectangle($img, 0, $y_step * $index,
    		($x_step * 3) - 1, $y_step * ($index + 1) - 1,
    		imagecolorexactalpha($img, 0, 0, 0, 255));
    	// Update marker images at the index offset
    	imagecopy($img, $normal->gd_handle(), 0, $y_step * $index, 0, 0,
    		$normal->get_meta_info('width'), $normal->get_meta_info('height'));
    	imagecopy($img, $focused->gd_handle(), $x_step, $y_step * $index, 0, 0,
    		$focused->get_meta_info('width'), $focused->get_meta_info('height'));
    	
    	imagesavealpha($img, true);
    	imagepng($img, $this->fpath());
    	
    	return true;    	
    }
    
    /**
     * Calculate the distance of tihs pack and another location.
     * @param float $lat The latitude of the other location
     * @param float $lng The longtitude of the other location
     */
    public function distance($lat, $lng) {
    	return sqrt(pow($lng - $this->center_lng, 2) + pow($lat - $this->center_lat, 2));
    }
    
    /**
     * Search for the closest free one or create one.
     * @param integer $lat The latitude of the place to find a close pack.
     * @param integer $lng The longtitude of the place to find a close pack.
     */
    public static function find_free_one($lat, $lng) {
    	$markers = MarkerPack::open_query()
    		->where('total < ?')
    		->execute(Registry::get('markers.max-per-pack'));
    	
    	// Create a new one if there is no free
    	if (count($markers) == 0)
    		return MarkerPack::create_at($lat, $lng);	
    	
    	// We have to choose one
    	$closest = array('index' => null, 'distance' => 999999);
    	foreach($markers as $idx => $m) {
    		$dist = $m->distance($lat, $lng);
    		if ($dist < $closest['distance'])
    			$closest = array('index' => $idx, 'distance' => $dist);
    	}
    	if ($closest['distance'] <= Registry::get('markers.max-pack-radius'))
    		return $markers[$closest['index']];
    	
    	// We didn't find a close so we create one
    	return MarkerPack::create_at($lat, $lng);
    }
}

MarkerPack::one_to_many('Place', 'marker', 'places');
