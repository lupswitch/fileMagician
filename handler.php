<?php define('_ACCESS_OK', true);
require_once 'init.php';

class fs
{
	protected $base = null;

	public function __construct($base) {
		$this->base = $this->real($base);
		if(!$this->base) { throw new Exception('Base directory does not exist'); }
	}

	protected function real($path) {
		$temp = realpath($path);
		if(!$temp) {
			//may be its a new file
			throw new Exception('Path does not exist: ' . $path);
		}
		if($this->base && strlen($this->base)) {
			if(strpos($temp, $this->base) !== 0) {
				throw new Exception('Path is not inside base ('.$this->base.'): ' . $temp);
			}
		}
		return $temp;
	}

	protected function path($id) {
		$id = str_replace('/', DIRECTORY_SEPARATOR, $id);
		$id = trim($id, DIRECTORY_SEPARATOR);
		$id = $this->real($this->base . DIRECTORY_SEPARATOR . $id);
		return $id;
	}

	protected function id($path) {
		$path = $this->real($path);
		$path = substr($path, strlen($this->base));
		$path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
		$path = trim($path, '/');
		return strlen($path) ? $path : '/';
	}

	public function lst($id, $with_root = false) {
		$dir = $this->path($id);
		$lst = @scandir($dir);
		if(!$lst) { throw new Exception('Could not list path: ' . $dir); }
		$res = array();
		foreach($lst as $item) {
			if($item == '.' || $item == '..' || $item === null) { continue; }
			// $tmp = preg_match('([^ a-zа-я-_0-9.]+)ui', $item);
			// if($tmp === false || $tmp === 1) { continue; }
			if(is_dir($dir . DIRECTORY_SEPARATOR . $item)) {
				$res[] = array('text' => $item, 'children' => true,  'id' => $this->id($dir . DIRECTORY_SEPARATOR . $item), 'icon' => 'folder');
			}
			else {
				$res[] = array('text' => $item, 'children' => false, 'id' => $this->id($dir . DIRECTORY_SEPARATOR . $item), 'type' => 'file', 'icon' => 'file file-'.substr($item, strrpos($item,'.') + 1));
			}
		}
		if($with_root && $this->id($dir) === '/') {
			$res = array(array('text' => basename($this->base), 'children' => $res, 'id' => '/', 'icon'=>'folder', 'state' => array('opened' => true, 'disabled' => true)));
		}
		return $res;
	}

	public function data($id) {
		if(strpos($id, ":")) {
			$id = array_map(array($this, 'id'), explode(':', $id));
			return array('type'=>'multiple', 'content'=> 'Multiple selected: ' . implode(' ', $id));
		}
		$dir = $this->path($id);
		if(is_dir($dir)) {
			return array('type'=>'folder', 'content'=> $id);
		}

		if(is_file($dir)) {
			$ext = strpos($dir, '.') !== FALSE ? substr($dir, strrpos($dir, '.') + 1) : '';
			$dat = array('type' => $ext, 'content' => '');
			switch($ext) {
				case 'txt':
				case 'text':
				case 'md':
				case 'ts':
				case 'js':
				case 'json':
				case 'css':
				case 'scss':
                case 'sass':
                case 'less':
				case 'html':
				case 'htm':
				case 'xml':
				case 'yml':
				case 'yaml':
				case 'c':
				case 'cpp':
				case 'h':
				case 'sql':
				case 'pgsql':
				case 'log':
				case 'py':
				case 'rb':
				case 'pl':
				case 'asp':
				case 'aspx':
				case 'java':
				case 'htaccess':
				case 'sh':
				case 'php':
				case 'blade':
				case 'tmpl':
				case 'twig':
				case 'env':
					clearstatcache();
					$filePer = substr(sprintf('%o', fileperms($dir)), -4);
					$filesize = filesize( $dir );
					$dat['info'] = array(
						'sizeByte'   => number_format($filesize),
						'size'       => $this->formatSizeUnits($filesize),
						'path'       => $dir,
						'hostUrl'   => $this->get_full_url_parent(),
						'permission' => substr(sprintf('%o', fileperms($dir)), -4),
						'permissionFull' => $this->convert_perms_to_rwx($filePer,$dir),
						'created'   => date ("Y-m-d H:i:s", filectime($dir)),
						'modified'   => date ("Y-m-d H:i:s", filemtime($dir)),
						'accessed'   => date ("Y-m-d H:i:s", fileatime($dir)),
					);
					$dat['content'] = base64_encode(file_get_contents($dir));
					break;
				case 'jpg':
				case 'jpeg':
				case 'gif':
				case 'png':
				case 'ico':
				case 'bmp':
				case 'webp':
					list($width, $height, $type, $attr) = getimagesize($dir);
					$filePer = substr(sprintf('%o', fileperms($dir)), -4);
					$filesize = filesize( $dir );
					$dat['info'] = array(
						'sizeByte'     => number_format($filesize),
						'size'       => $this->formatSizeUnits($filesize),
						'path'       => $dir,
						'hostUrl'   => defined('SERVER_IMG_PATH') ? SERVER_IMG_PATH : $this->get_full_url_parent(),
						'height'     => $height,
						'width'      => $width,
						'permission' => $filePer,
						'permissionFull' => $this->convert_perms_to_rwx($filePer,$dir),
						'created'    => date ("Y-m-d H:i:s", filectime($dir)),
						'modified'   => date ("Y-m-d H:i:s", filemtime($dir)),
						'accessed'   => date ("Y-m-d H:i:s", fileatime($dir)),
					);

					$dat['content'] = $id;
					// $dat['content'] = 'data:'.finfo_file(finfo_open(FILEINFO_MIME_TYPE), $dir).';base64,'.base64_encode(file_get_contents($dir));
					break;
					case 'zip':
					case 'ZIP':
					case 'gz':
					case 'GZ':
					case 'pdf':
					case 'PDF':
					case 'xls':
					case 'XLS':
					case 'csv':
					case 'CSV':
					case 'doc':
					case 'docx':
					case 'DOC':
					case 'DOCX':
					case 'DOCX':
					$filePer = substr(sprintf('%o', fileperms($dir)), -4);
					$filesize = filesize( $dir );
					$dat['info'] = array(
						'sizeByte'   => number_format($filesize),
						'size'       => $this->formatSizeUnits($filesize),
						'path'       => $dir,
						'hostUrl'   => $this->get_full_url_parent(),
						'permission' => $filePer,
						'permissionFull' => $this->convert_perms_to_rwx($filePer,$dir),
						'created'    => date ("Y-m-d H:i:s", filectime($dir)),
						'modified'   => date ("Y-m-d H:i:s", filemtime($dir)),
						'accessed'   => date ("Y-m-d H:i:s", fileatime($dir)),
					);
					$dat['content'] = 'https://docs.google.com/viewer?url='.$this->get_full_url_parent().$id;
					break;
				default:
					clearstatcache();
					$filePer = substr(sprintf('%o', fileperms($dir)), -4);
					$filesize = filesize( $dir );
					$dat['info'] = array(
						'sizeByte'   => number_format($filesize),
						'size'       => $this->formatSizeUnits($filesize),
						'path'       => $dir,
						'hostUrl'   => $this->get_full_url_parent(),
						'permission' => substr(sprintf('%o', fileperms($dir)), -4),
						'permissionFull' => $this->convert_perms_to_rwx($filePer,$dir),
						'created'   => date ("Y-m-d H:i:s", filectime($dir)),
						'modified'   => date ("Y-m-d H:i:s", filemtime($dir)),
						'accessed'   => date ("Y-m-d H:i:s", fileatime($dir)),
					);
					$dat['content'] = base64_encode(file_get_contents($dir));
					break;
					// $dat['content'] = 'File not recognized: '.$this->id($dir);
					// break;
			}
			return $dat;
		}
		throw new Exception('Not a valid selection: ' . $dir);
	}

	public function convert_perms_to_rwx($perms, $file){
	    $rwx = array(
	        '---',
	        '--x',
	        '-w-',
	        '-wx',
	        'r--',
	        'r-x',
	        'rw-',
	        'rwx'
	    );

	    $type   = is_dir($file) ? 'd' : '-';
	    $owner  = $perms[1];
	    $group  = $perms[2];
	    $public = $perms[3];
	    return $type.$rwx[$owner].$rwx[$group].$rwx[$public];
	}

	protected function get_full_url_parent() {
        $https = !empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'on') === 0 ||
            !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
                strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') === 0;
        $url = 
            ($https ? 'https://' : 'http://').
            (!empty($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'].'@' : '').
            (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ($_SERVER['SERVER_NAME'].
            ($https && $_SERVER['SERVER_PORT'] === 443 ||
            $_SERVER['SERVER_PORT'] === 80 ? '' : ':'.$_SERVER['SERVER_PORT']))).
            substr($_SERVER['SCRIPT_NAME'],0, strrpos($_SERVER['SCRIPT_NAME'], '/'));
        $urlAr = explode('/',$url);
        array_pop($urlAr);
        $final_path = implode('/', $urlAr);
        return $final_path.'/';
    }

	public function formatSizeUnits($bytes=null)
    {
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
	}

	public function save_file($id, $content='') {
		if(strpos($id, ":")) {
			$id = array_map(array($this, 'id'), explode(':', $id));
			return array('type'=>'multiple', 'content'=> 'Multiple selected: ' . implode(' ', $id));
		}
		$dir = $this->path($id);
		if(is_dir($dir)) {
			return array('type'=>'folder', 'content'=> $id);
		}

		if(is_file($dir)) {
			$ext = strpos($dir, '.') !== FALSE ? substr($dir, strrpos($dir, '.') + 1) : '';
			$dat = array('type' => $ext, 'content' => '');
			switch($ext) {
				case 'txt':
				case 'text':
				case 'md':
				case 'ts':
				case 'js':
				case 'json':
				case 'css':
				case 'scss':
                case 'sass':
                case 'less':
				case 'html':
				case 'htm':
				case 'xml':
				case 'yml':
				case 'yaml':
				case 'c':
				case 'cpp':
				case 'h':
				case 'sql':
				case 'pgsql':
				case 'log':
				case 'py':
				case 'rb':
				case 'pl':
				case 'asp':
				case 'aspx':
				case 'java':
				case 'htaccess':
				case 'sh':
				case 'php':
				case 'blade':
				case 'tmpl':
				case 'twig':
				case 'env':
					$foldersParams = explode('/', $dir);
					if(count($foldersParams)){
						$fileToSave = array_pop($foldersParams);
						$fullDir = implode('/',$foldersParams);
						if (!is_dir($fullDir) or !is_writable($fullDir)) {
						    die(json_encode(['error' => 'Folder '.$fullDir.' is not writable!']));
						}
					}
					
					if (is_file($dir) && !is_writable($dir)){
						die(json_encode(['error' => 'File '.$dir.' is not writable!']));
					}
                    
					file_put_contents($dir, base64_decode($content)) or die(json_encode(['error' => 'Could not save the file!'.$dir]));
					die(json_encode(['success' => 'File has been saved!']));
					break;
				case 'jpg':
				case 'jpeg':
				case 'gif':
				case 'png':
				case 'ico':
				case 'bmp':
				case 'webp':
					$dat['content'] = 'data:'.finfo_file(finfo_open(FILEINFO_MIME_TYPE), $dir).';base64,'.base64_encode(file_get_contents($dir));
					break;
				default:
					$dat['content'] = 'File not recognized: '.$this->id($dir);
					break;
			}
		} else{
			// todo: let it save a new file which doesn't exist on the server
			$ext = strpos($dir, '.') !== FALSE ? substr($dir, strrpos($dir, '.') + 1) : '';
			$dat = array('type' => $ext, 'content' => '');
			$foldersParams = explode('/', $dir);
			if(count($foldersParams)){
				$fileToSave = array_pop($foldersParams);
				$fullDir = implode('/',$foldersParams);
				if (!is_dir($fullDir) or !is_writable($fullDir)) {
					die(json_encode(['error' => 'Folder '.$fullDir.' is not writable!']));
				}
			}
			
			if (is_file($dir) && !is_writable($dir)){
				die(json_encode(['error' => 'File '.$dir.' is not writable!']));
			}
			
			file_put_contents($dir, base64_decode($content)) or die(json_encode(['error' => 'Could not save the file!'.$dir]));
			die(json_encode(['success' => 'File has been saved!']));
		}

		throw new Exception('Not a valid selection: ' . $dir);
	}

	public function create($id, $name, $mkdir = false) {
		$dir = $this->path($id);
		if(preg_match('([^ a-zа-я-_0-9.]+)ui', $name) || !strlen($name)) {
			throw new Exception('Invalid name: ' . $name);
		}
		if($mkdir) {
			mkdir($dir . DIRECTORY_SEPARATOR . $name);
		}
		else {
			file_put_contents($dir . DIRECTORY_SEPARATOR . $name, '');
		}
		return array('id' => $this->id($dir . DIRECTORY_SEPARATOR . $name));
	}

	public function rename($id, $name) {
		$dir = $this->path($id);
		if($dir === $this->base) {
			throw new Exception('Cannot rename root');
		}
		if(preg_match('([^ a-zа-я-_0-9.]+)ui', $name) || !strlen($name)) {
			throw new Exception('Invalid name: ' . $name);
		}
		$new = explode(DIRECTORY_SEPARATOR, $dir);
		array_pop($new);
		array_push($new, $name);
		$new = implode(DIRECTORY_SEPARATOR, $new);
		if($dir !== $new) {
			if(is_file($new) || is_dir($new)) { throw new Exception('Path already exists: ' . $new); }
			rename($dir, $new);
		}
		return array('id' => $this->id($new));
	}

	public function remove($id) {
		$dir = $this->path($id);
		if($dir === $this->base) {
			throw new Exception('Cannot remove root');
		}
		if(is_dir($dir)) {
			foreach(array_diff(scandir($dir), array(".", "..")) as $f) {
				$this->remove($this->id($dir . DIRECTORY_SEPARATOR . $f));
			}
			rmdir($dir);
		}
		if(is_file($dir)) {
			unlink($dir);
		}
		return array('status' => 'OK');
	}

	public function move($id, $par) {
		$dir = $this->path($id);
		$par = $this->path($par);
		$new = explode(DIRECTORY_SEPARATOR, $dir);
		$new = array_pop($new);
		$new = $par . DIRECTORY_SEPARATOR . $new;
		rename($dir, $new);
		return array('id' => $this->id($new));
	}

	public function copy($id, $par) {
		$dir = $this->path($id);
		$par = $this->path($par);
		$new = explode(DIRECTORY_SEPARATOR, $dir);
		$new = array_pop($new);
		$new = $par . DIRECTORY_SEPARATOR . $new;
		if(is_file($new) || is_dir($new)) { throw new Exception('Path already exists: ' . $new); }

		if(is_dir($dir)) {
			mkdir($new);
			foreach(array_diff(scandir($dir), array(".", "..")) as $f) {
				$this->copy($this->id($dir . DIRECTORY_SEPARATOR . $f), $this->id($new));
			}
		}
		if(is_file($dir)) {
			copy($dir, $new);
		}
		return array('id' => $this->id($new));
	}

	public function folderExists($folder) {
	    // Get canonicalized absolute pathname
	    $path = realpath($folder);
	    return ($path !== false AND is_dir($path)) ? true : false;
	}

	public function extract($id, $dir='') {
		try {
			setlocale(LC_ALL,'en_US.UTF-8');
			$file = $this->base.DIRECTORY_SEPARATOR.$dir.DIRECTORY_SEPARATOR.$id;
			$extension = pathinfo($file, PATHINFO_EXTENSION);
			$folderName = strstr($id,'.', true);

			if( substr($file, -6)=='sql.gz'){
			    $file_name = $file;
				$buffer_size = 4096;// read 4kb at a time
				$out_file_name = str_replace('.gz', '', $file_name); 

				// Open our files (in binary mode)
				$file = gzopen($file_name, 'rb');
				$out_file = fopen($out_file_name, 'wb'); 

				// Keep repeating until the end of the input file
				while (!gzeof($file)) {
				    // Read buffer-size bytes
				    // Both fwrite and gzread and binary-safe
				    fwrite($out_file, gzread($file, $buffer_size));
				}

				// Files are done, close files
				fclose($out_file);
				gzclose($file);
			} elseif($extension=='gz'){
			    $phar = new PharData($file);
			    $phar->decompress();
			} elseif($extension=='tar'){
			    $phar = new PharData($file);
			    //if folder already exists show the error message
			    if( $d = $this->folderExists($this->base.DIRECTORY_SEPARATOR.$dir.DIRECTORY_SEPARATOR.$folderName)===true){
			    	return array('error' => 'folder already exists');
			    }

			    $res = $phar->extractTo($this->base.DIRECTORY_SEPARATOR.$dir.DIRECTORY_SEPARATOR.$folderName);
			} elseif($extension=='zip'){
				$path = pathinfo(realpath($file), PATHINFO_DIRNAME);
				$zip = new ZipArchive;
				$res = $zip->open($file);

				if ($res === TRUE) {
				  $zip->extractTo($path);
				  $zip->close();
				  return array('success' => 'extracted');
				} else {
					return array('error' => 'cannot open the file');
				}
			}else{

			}
		} catch (Exception $e) {
		    echo '<pre>';print_r($e);die();
		}

		return array('success' => 'extracted');
	}
}

if(isset($_POST) && isset($_POST['img'])){
	$img = $_POST['img'];
	$img = str_replace('data:image/png;base64,', '', $img);
	$img = str_replace('data:image/jpg;base64,', '', $img);
	$img = str_replace('data:image/gif;base64,', '', $img);
	$img = str_replace('data:image/jpeg;base64,', '', $img);
	$img = str_replace(' ', '+', $img);
	$data = base64_decode($img);
	$_POST['name'] = str_replace($_SERVER['HTTP_HOST'], '', $_POST['name']);
	$_POST['name'] = str_replace(['http:///','https:///','http://www./'],'',$_POST['name']);
	$file = substr($_POST['name'],0,strrpos($_POST['name'], '.',true));
	$success = file_put_contents(ROOT.$file.strstr($_POST['name'], '.',false), $data);
	print $success ? 'file saved' : 'Unable to save the file.';
	exit;
} elseif(isset($_GET['action'])) {
	$fs = new fs( dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);
	$node = isset($_POST['id']) && $_POST['id'] !== '#' ? $_POST['id'] : '/';
	$rslt = $fs->save_file($node, $_POST['content']);

	echo json_encode(array('saved' => true));exit;
} elseif(isset($_GET['operation'])) {
	$fs = new fs( dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);
	try {
		$rslt = null;
		switch($_GET['operation']) {
			case 'extract':
				$rslt = $fs->extract($_POST['id'], $_POST['directory']);
				break;
			case 'get_node':
				$node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : '/';
				$rslt = $fs->lst($node, (isset($_GET['id']) && $_GET['id'] === '#'));
				break;
			case "get_content":
				$node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : '/';
				$rslt = $fs->data($node);
				break;
			case 'create_node':
				$node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : '/';
				$rslt = $fs->create($node, isset($_GET['text']) ? $_GET['text'] : '', (!isset($_GET['type']) || $_GET['type'] !== 'file'));
				break;
			case 'rename_node':
				$node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : '/';
				$rslt = $fs->rename($node, isset($_GET['text']) ? $_GET['text'] : '');
				break;
			case 'delete_node':
				$node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : '/';
				$rslt = $fs->remove($node);
				break;
			case 'move_node':
				$node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : '/';
				$pan = isset($_GET['parent']) && $_GET['parent'] !== '#' ? $_GET['parent'] : '/';
				$rslt = $fs->move($node, $pan);
				break;
			case 'copy_node':
				$node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : '/';
				$pan = isset($_GET['parent']) && $_GET['parent'] !== '#' ? $_GET['parent'] : '/';
				$rslt = $fs->copy($node, $pan);
				break;
			default:
				throw new Exception('Unsupported operation: ' . $_GET['operation']);
				break;
		}
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($rslt);
	}
	catch (Exception $e) {
		header($_SERVER["SERVER_PROTOCOL"] . ' 500 Server Error');
		header('Status:  500 Server Error');
		echo $e->getMessage();
	}
	die();
}