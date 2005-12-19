<?php
/**
 * DokuWiki media passthrough file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 */

  if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
  require_once(DOKU_INC.'inc/init.php');
  require_once(DOKU_INC.'inc/common.php');
  require_once(DOKU_INC.'inc/pageutils.php');
  require_once(DOKU_INC.'inc/confutils.php');
  //close sesseion
  session_write_close();

  $mimetypes = getMimeTypes();

  //get input
  $MEDIA  = getID('media');
  $CACHE  = calc_cache($_REQUEST['cache']);
  $WIDTH  = $_REQUEST['w'];
  $HEIGHT = $_REQUEST['h'];
  list($EXT,$MIME) = mimetype($MEDIA);
  if($EXT === false){
    $EXT  = 'unknown';
    $MIME = 'application/octet-stream';
  }

  //media to local file
  if(preg_match('#^(https?|ftp)://#i',$MEDIA)){
    //handle external media
    $FILE = get_from_URL($MEDIA,$EXT,$CACHE);
    if(!$FILE){
      //download failed - redirect to original URL
      header('Location: '.$MEDIA);
      exit;
    }
  }else{
    $MEDIA = cleanID($MEDIA);
    if(empty($MEDIA)){
      header("HTTP/1.0 400 Bad Request");
      print 'Bad request';
      exit;
    }

    //check permissions (namespace only)
    if(auth_quickaclcheck(getNS($MEDIA).':X') < AUTH_READ){
      header("HTTP/1.0 401 Unauthorized");
      //fixme add some image for imagefiles
      print 'Unauthorized';
      exit;
    }
    $FILE  = mediaFN($MEDIA);
  } 
  
  //check file existance
  if(!@file_exists($FILE)){
    header("HTTP/1.0 404 Not Found");
    //FIXME add some default broken image
    print 'Not Found';
    exit;
  }

  //handle image resizing
  if((substr($MIME,0,5) == 'image') && $WIDTH){
    $FILE = get_resized($FILE,$EXT,$WIDTH,$HEIGHT);
  }


  //FIXME set sane cachecontrol headers
  //FIXME handle conditional and partial requests

  //send file
  header("Content-Type: $MIME");
  header('Last-Modified: '.date('r',filemtime($FILE)));
  header('Content-Length: '.filesize($FILE));
  header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0');

  //application mime type is downloadable
  if(substr($MIME,0,11) == 'application'){
    header('Content-Disposition: attachment; filename="'.basename($FILE).'"');
  }

  $fp = @fopen($FILE,"rb");
  if($fp){
    while (!feof($fp)) {
      @set_time_limit(); // large files can take a lot of time
      print fread($fp, 16*1024);
      flush();
    }
    fclose($fp);
  }else{
    header("HTTP/1.0 500 Internal Server Error");
    print "Could not read $FILE - bad permissions?";
  }

/* ------------------------------------------------------------------------ */

/**
 * Resizes the given image to the given size
 *
 * @author  Andreas Gohr <andi@splitbrain.org>
 */
function get_resized($file, $ext, $w, $h=0){
  global $conf;

  $info  = getimagesize($file);
  if(!$h) $h = round(($w * $info[1]) / $info[0]);


  //cache
  $local = getCacheName($file,'.media.'.$w.'x'.$h.'.'.$ext);
  $mtime = @filemtime($local); // 0 if not exists

  if( $mtime > filemtime($file) ||
      resize_imageIM($ext,$file,$info[0],$info[1],$local,$w,$h) ||
      resize_imageGD($ext,$file,$info[0],$info[1],$local,$w,$h) ){
    return $local;
  }
  //still here? resizing failed
  return $file;
}

/**
 * Returns the wanted cachetime in seconds
 *
 * Resolves named constants
 *
 * @author  Andreas Gohr <andi@splitbrain.org>
 */
function calc_cache($cache){
  global $conf;

  if(strtolower($cache) == 'nocache') return 0; //never cache
  if(strtolower($cache) == 'recache') return $conf['cachetime']; //use standard cache
  return -1; //cache endless
}

/**
 * Download a remote file and return local filename
 *
 * returns false if download fails. Uses cached file if available and
 * wanted
 *
 * @author  Andreas Gohr <andi@splitbrain.org>
 * @author  Pavel Vitis <Pavel.Vitis@seznam.cz>
 */
function get_from_URL($url,$ext,$cache){
  global $conf;

  $local = getCacheName(strtolower($url),".media.$ext");
  $mtime = @filemtime($local); // 0 if not exists

  //decide if download needed:
  if( $cache == 0 ||                             // never cache
      ($mtime != 0 && $cache != -1) ||           // exists but no endless cache
      ($mtime == 0) ||                           // not exists
      ($cache != -1 && $mtime < time()-$cache)   // expired
    ){
      if(io_download($url,$local)){
        return $local;
      }else{
        return false;
      }
  }
      
  //if cache exists use it else
  if($mtime) return $local;

  //else return false
  return false;
}

/**
 * resize images using external ImageMagick convert program
 *
 * @author Pavel Vitis <Pavel.Vitis@seznam.cz>
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function resize_imageIM($ext,$from,$from_w,$from_h,$to,$to_w,$to_h){
  global $conf;

  // check if convert is configured
  if(!$conf['im_convert']) return false;

  // prepare command
  $cmd  = $conf['im_convert'];
  $cmd .= ' -resize '.$to_w.'x'.$to_h.'!';
  $cmd .= " $from $to";

  @exec($cmd,$out,$retval);
  if ($retval == 0) return true;

  return false;
}

/**
 * resize images using PHP's libGD support
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function resize_imageGD($ext,$from,$from_w,$from_h,$to,$to_w,$to_h){
  global $conf;

  if($conf['gdlib'] < 1) return false; //no GDlib available or wanted

  // create an image of the given filetype
  if ($ext == 'jpg' || $ext == 'jpeg'){
    if(!function_exists("imagecreatefromjpeg")) return false;
    $image = @imagecreatefromjpeg($from);
  }elseif($ext == 'png') {
    if(!function_exists("imagecreatefrompng")) return false;
    $image = @imagecreatefrompng($from);

  }elseif($ext == 'gif') {
    if(!function_exists("imagecreatefromgif")) return false;
    $image = @imagecreatefromgif($from);
  }
  if(!$image) return false;

  if(($conf['gdlib']>1) && function_exists("imagecreatetruecolor")){
    $newimg = @imagecreatetruecolor ($to_w, $to_h);
  }
  if(!$newimg) $newimg = @imagecreate($to_w, $to_h);
  if(!$newimg) return false;

  //keep png alpha channel if possible
  if($ext == 'png' && $conf['gdlib']>1 && function_exists('imagesavealpha')){
    imagealphablending($newimg, false);
    imagesavealpha($newimg,true);
  }

  // create cachedir
  //io_makeFileDir($to); // not needed anymore, should exist

  //try resampling first
  if(function_exists("imagecopyresampled")){
    if(!@imagecopyresampled($newimg, $image, 0, 0, 0, 0, $to_w, $to_h, $from_w, $from_h)) {
      imagecopyresized($newimg, $image, 0, 0, 0, 0, $to_w, $to_h, $from_w, $from_h);
    }
  }else{
    imagecopyresized($newimg, $image, 0, 0, 0, 0, $to_w, $to_h, $from_w, $from_h);
  }

  if ($ext == 'jpg' || $ext == 'jpeg'){
    if(!function_exists("imagejpeg")) return false;
    return imagejpeg($newimg, $to, 70);
  }elseif($ext == 'png') {
    if(!function_exists("imagepng")) return false;
    return imagepng($newimg, $to);
  }elseif($ext == 'gif') {
    if(!function_exists("imagegif")) return false;
    return imagegif($newimg, $to);
  }

  return false;
}


//Setup VIM: ex: et ts=2 enc=utf-8 :
?>
