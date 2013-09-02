<?php

define('SCALE', 20);
define('THRESHOLD', 1);
define('ILLUSTRATOR_COMPATIBLE', 1);
define('MAX_WIDTH', 56);
define('MAX_HEIGHT', 56);
define('TRIANGLE_MODE', 'both'); //'up', 'down', 'both'
define('USE_SYMBOLS', 1);

global $base_colors;
$base_colors = array(
  'alpha',
  'blue',
  'green',
  'red',
  );


$url = isset($_GET['q']) ? $_GET['q'] : 'sample1.jpg';
$file = 'tmp';
file_put_contents($file, file_get_contents($url));


$extension = get_file_extension($url);

switch ($extension) {
  case 'jpg':
  case 'jpeg':
  $im = imagecreatefromjpeg($file);
  break;
  case 'png':
  $im = imagecreatefrompng($file);
  break;
  case 'png':
  $im = imagecreatefromgif($file);
  break;
  default:
  print 'format not compatiple';
  break;
}

$w = imagesx($im); // image width
$h = imagesy($im); // image height

if($h > MAX_HEIGHT || $w > MAX_WIDTH) {
  if ($w >= $h) {
    $neww = MAX_WIDTH;
    $ratio = $neww/$w;
    $newh = (int) ($h * $ratio);
  } else {
    $newh = MAX_HEIGHT;
    $ratio = $newh/$h;
    $neww = (int) ($w * $ratio);
  }
  $newim = imagecreatetruecolor($neww,$newh);
  imagecopyresampled($newim, $im, 0, 0, 0, 0, $neww, $newh, $w, $h);
  $im = $newim;
  $w = $neww;
  $h = $newh;
}


if($w % 2 != 0 || $h % 2 != 0) {
  if($w % 2 != 0 ) {
    $w = $w-1;
  } else {
    $h = $h-1;
  }
  imagecopy($im, $im, 0, 0, 0, 0, $w, $h);
}

/*
header("Content-type: image/png");
imagepng($im);
exit;
*/

function get_file_extension($file_name) {
  return strtolower(substr(strrchr($file_name,'.'),1));
}



function t_dechex($i) {
  return str_pad(dechex($i), 2,'0' , STR_PAD_LEFT);
}

function make_color($color) {
  if (ILLUSTRATOR_COMPATIBLE) {
    $o = '#' . t_dechex($color['red']) . t_dechex($color['green']) . t_dechex($color['blue']);
  } else {
    $o = 'rgba(' . $color['red'] . ',' . $color['green'] . ',' . $color['blue'] . ', ' . alpha_convert($color['alpha']) . ')';
  }
  return $o;
}


function get_average_color($colors) {
  $output = array();
  global $base_colors;
  foreach ($base_colors as $color) {
    $output[$color] = 0;
  }
  foreach ($colors as $key => $value) {
    foreach ($base_colors as $color) {
      $output[$color] += $colors[$key][$color];
    }
  }
  foreach ($base_colors as $color) {
    $output[$color] = floor($output[$color]/count($colors));
  }
  return $output;
}

function get_strangest_color($colors, $normal) {
  global $base_colors;
  $differences = array();
  foreach ($colors as $key => $value) {
    $difference = 0;
    foreach ($base_colors as $color) {
      if ($value[$color] >= $normal[$color]) {
        $difference+= ($value[$color] - $normal[$color]);
      } else {
        $difference+= ($normal[$color] - $value[$color]);
      }
      $differences[$key] = $difference;
    }
  }
  //ChromePhp::log($normal, 'normal');
  //ChromePhp::log($colors, 'colors');
  //ChromePhp::log($differences, 'differences');
  asort($differences);

  if (THRESHOLD < end($differences)) {
    $keys = array_keys($differences);
    //ChromePhp::log($differences, 'sort');
    $output = end($keys);
    //ChromePhp::log($returner, 'return');
    return $output;
  }
  return FALSE;

  //ChromePhp::log($differences, 'sort');
}

$trixels = array();
$x = 0;
$y = 0;


for ($old_y=0; $old_y < $h; $old_y += 2) {
  for ($old_x=0; $old_x < $w; $old_x += 2) {

   $nw = imagecolorsforindex($im, imagecolorat($im, $old_x, $old_y));
   $ne = imagecolorsforindex($im, imagecolorat($im, $old_x+1, $old_y));
   $se = imagecolorsforindex($im, imagecolorat($im, $old_x+1, $old_y+1));
   $sw = imagecolorsforindex($im, imagecolorat($im, $old_x, $old_y+1));

   $trixels[$x][$y] = array(
     'o-colors' => array(
       'nw' => $nw,
       'ne' => $ne,
       'se' => $se,
       'sw' => $sw,
       ),
     );
   $trixels[$x][$y]['a-color'] = get_average_color($trixels[$x][$y]['o-colors']);
   $trixels[$x][$y]['stranges-color'] = get_strangest_color($trixels[$x][$y]['o-colors'], $trixels[$x][$y]['a-color']);

   $tmp = array();
   foreach ($trixels[$x][$y]['o-colors'] as $key => $value) {
     if($key !=  $trixels[$x][$y]['stranges-color'] ) {
      $tmp[$key] = $value;
    }
  }
  $trixels[$x][$y]['a-normal-color'] = get_average_color($tmp);

  $x++;
}
$y++;
$x = 0;
}

//  ChromePhp::log($trixels);

function draw_rect($top = 0, $left = 0, $color = FALSE, $id = FALSE) {
  //ChromePhp::log($color);

  $attr = array();
  if ($color) {
    $attr[] = 'fill="' . make_color($color) . '"';
  };
  return '<rect x="' . $top*SCALE . '" y="' . $left*SCALE . '" width="' . SCALE . '" height="' . SCALE . '" ' . implode(' ', $attr) . '/>';
}

function alpha_convert($value) {
  return 1;
  $b = 255 / 100;
  $c = ($value+255) * $b;
  return $c / 100;
}


function draw_triangle($top, $left, $dir, $color) {
  $fill = make_color($color);
  //  ChromePhp::log($dir);

    if (TRIANGLE_MODE == 'up') {
      if($dir == 'ne') {
        $dir = 'nw';
      } elseif ($dir == 'sw') {
        $dir = 'se';
      }
    }
    elseif (TRIANGLE_MODE == 'down') {
      if($dir == 'nw') {
        $dir = 'ne';
      } elseif ($dir == 'se') {
        $dir = 'sw';
      }
    }

    switch ($dir) {
      case 'sw':
      $points = array(
        array(0,0),
        array(0,1),
        array(1,1)
      );
      break;
      case 'se':
      $points = array(
        array(0,1),
        array(1,1),
        array(1,0)
      );
      break;
      case 'ne':
      $points = array(
        array(0,0),
        array(1,0),
        array(1,1)
        );
      break;
      case 'nw':
      $points = array(
        array(0,1),
        array(0,0),
        array(1,0)
        );
      break;
    }


  foreach ($points as $key => $value) {
    $points[$key][1] = ($left+$points[$key][1])*SCALE;
    $points[$key][0] = ($top+$points[$key][0])*SCALE;
    $points[$key] = implode($points[$key], ' ');
  }

  return '<path d="M ' . implode($points, ' ') . ' z" fill="' . $fill . '"/>';
}

$defs = '';
if(USE_SYMBOLS) {
  $defs .= '<defs>';
  $defs .= draw_rect(0, 0, false, 'p');
  $defs .= '</defs>';
}


header("Content-Type: image/svg+xml");
echo '<?xml version="1.0" encoding="iso-8859-1"?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.0//EN" "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd">';

echo '<svg xmlns="http://www.w3.org/2000/svg" height="' . $h*SCALE/2 . '" width="' . $w*SCALE/2 . '">';

echo $defs;

foreach ($trixels as $x => $row) {
  print '<g>';
  foreach ($row as $y => $trixel) {
    print '<g>';
    print draw_rect($x, $y, $trixels[$x][$y]['a-normal-color']);
    if($trixels[$x][$y]['stranges-color']) {
      $strange_color = $trixels[$x][$y]['stranges-color'];
      print draw_triangle($x, $y, $strange_color , $trixels[$x][$y]['o-colors'][$strange_color]);
    }
    print '</g>';
  }
  print '</g>';
}


echo '</svg>';
