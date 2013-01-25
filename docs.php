<?php
/**************************************************/
/*** --------- Document Handler Class --------- ***/
/*** --------- Created By: Cody Moncur -------- ***/
/*** --------- Created On: Nov 26, 2012 ------- ***/
/*** --------- Last Updated: Dec 07, 2012 ----- ***/
/**************************************************/

/**************************************************/
/*** ------------- DEPENDENCIES --------------- ***/
/**************************************************/

//Database Handler Class




class Docs {
	/**********************************************/
	/*** --------------- MEMBERS -------------- ***/
	/**********************************************/

	private $files; //Array of files fetched from documents directory
	private $filtered = array(); //Array of files after filtration from constructor method
	private $directory; //Documents directory location
	private $db; //Database connection




	/**********************************************/
	/*** - CONSTRUCTOR AND DESTRUCTOR METHODS - ***/
	/**********************************************/

	/**
	*** Constructor
	*** Automatically called upon $docs = new Docs()
	***
	*** $display should be either "list" or "button"
	*** Example: $docs = new Docs(array("pdf", "doc", "txt", "rtf"), "list", "docs");
	**/
	function __construct($type, $display, $directory) {
		$this->directory = $directory;
		$this->db = new Db();
		$this->filter($type);
		if ($display == "button") $this->getFilesButton();
		elseif ($display == "list") $this->getFilesList();
	}




	/**********************************************/
	/*** ----------- PRIVATE METHODS ---------- ***/
	/**********************************************/

	/**
	*** Crop image
	*** Creates thumbnails from images within documents folder
	*** If image is smaller than 100px by 100px, copies image into destination folder
	**/
	private function cropImage($original, $destination) {
		list($or_w, $or_h) = getimagesize($original); //Original width/height
		if(($or_w > 100) || ($or_h > 100)) {
			$th_w = 100; //Thumbnail width
			$th_h = 100; //Thumbnail height
			$th_a = $th_w / $th_h; //Thumbnail aspect ratio
			$or_a = $or_w / $or_h; //Original aspect ratio

			if ($or_a >= $th_a) {
				$crop_h = $th_h;
				$crop_w = $or_w / ($or_h / $th_w);
			} else {
				$crop_w = $th_w;
				$crop_h = $or_h / ($or_w / $th_w);
			}

			$cen_h = 0 - ($crop_w - $th_w) / 2; //Horizontal center
			$cen_v = 0 - ($crop_h - $th_h) / 2; //Vertical center

			$th = imagecreatetruecolor($th_w, $th_h); //Create thumbnail
			imagecopyresampled(
				$th, 
				imagecreatefromstring(file_get_contents($original)), 
				$cen_h, $cen_v, 
				0, 0, 
				$crop_w, $crop_h, 
				$or_w, $or_h
			);
			imagejpeg($th, $destination);
		} else {
			copy($original, $destination);
		}
	}


	/**
	*** Filesize
	*** Returns filesize of given file.
	*** Example outputs: 658 b, 32.4 kb, 8.1 mb, 1.9 gb, 1.1 tb
	**/
	private function fileSize($file) {
		$size = filesize($file);
		if ($size < 1024) return $size . " b";
		elseif ($size < 1048576) return round($size / 1024, 1) . " kb";
		elseif ($size < 1073741824) return round($size / 1048576, 1) . " mb";
		elseif ($size < 1099511627776) return round($size / 1073741824, 1) . " gb";
		elseif ($size < 1125899906842624) return round($size / 1099511627776, 1) . " tb";
		elseif ($size < 1152921504606846976) return round($size / 1125899906842624, 1) . " pb";
		else return "> 1 eb";
	}


	/**
	*** Filter
	*** Filters by file type and pushes selected values to a fresh array 
	*** Removes "." and ".." values from files array
	*** Matches resulting array against MySQL database to ensure there is an entry for for each file.  If no entry exists,
	*** an entry is created in MySQL database
	*** 
	*** $type should be an ARRAY of file types that the invoker wants listed
	**/
	private function filter($type) {
		$this->files = scandir($this->directory);
		//Unset "." and ".." navigation values, unset hidden files, unset improperly named files, unset folders
		for ($i = 0; $i < count($this->files); $i++) {
			$x = explode(".", $this->files[$i]); //x[0] = filename, x[1] = filetype
			if (!isset($x[1])) $x[1] = false;
			elseif (in_array($x[1], $type)) array_push($this->filtered, $this->files[$i]); //Add filtered files to array
		}
	}

	/**
	*** Prepares files to be displayed as buttons
	*** Ensures that new files are properly recorded in the database
	**/
	private function getFilesButton() {
		for ($i = 0; $i < count($this->filtered); $i++) {
			$x = explode(".", $this->filtered[$i]); //x[0] = filename, x[1] = filetype

			//Create thumbnail if file is an image format
			if (($x[1] == "jpg") || ($x[1] == "gif") || ($x[1] == "png") || ($x[1] == "bmp")) $this->cropImage("docs/" . $this->filtered[$i], "docs/crop/" . $this->filtered[$i]);
			
			$e = $this->db->numRows("files", "file", $this->filtered[$i]); //Check to see if file already exists within database
			if ($e < 1) {
				$location = "docs/".$this->filtered[$i];
				$filesize = $this->fileSize($location);
				$this->db->insert("files", array("file", "filename", "filetype", "size", "uploaded", "updated"), array($this->filtered[$i], $x[0], $x[1], $filesize, time(), time()));
			}
		}
	}


	/**
	*** Display files as a list
	**/
	private function getFilesList() {
		for ($i = 0; $i < count($this->filtered); $i++) {
			echo $this->filtered[$i] . "<br>";
		}
	}




	/**********************************************/
	/*** ----------- PUBLIC METHODS ----------- ***/
	/**********************************************/

	//None
}
?>