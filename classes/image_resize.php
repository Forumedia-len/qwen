<?php

class ImageResize
{
  protected $width_standart;
  protected $height_standart;
  protected $sign;
  function __construct($width = 50, $height = 50, $sign = false)
  {
    $this->width_standart  = $width;
    $this->height_standart = $height;
    $this->sign            = $sign;
  }

  // Переделать изображение
  function createImage($filename)
  {
    // Получить оригинальные размеры
    list($real_width, $real_height) = getimagesize($filename);


    if ($real_width <= $this->width_standart && $real_height <= $this->height_standart) {
      $width         = $real_width;
      $height        = $real_height;
      $width_offset  = ($this->width_standart - $width) / 2;
      $height_offset = ($this->height_standart - $height) / 2;
    } else {
      list($width, $height, $width_offset, $height_offset) = $this->getSizeImage($real_width, $real_height);
    }

    // Переделать изображение
    $image   = imagecreatefromjpeg($filename);
    $image_p = imagecreatetruecolor($width, $height);
    imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $real_width, $real_height);//со сглаживанием

    $image_out = imagecreatetruecolor($this->width_standart, $this->height_standart);
    $white     = imagecolorallocate($image_out, 255, 255, 255);
    imagefill($image_out, 0, 0, $white);
    imagecopyresampled($image_out, $image_p, $width_offset, $height_offset, 0, 0, $width, $height, $width, $height);//со сглаживанием

    //Водяной знак
    if ($this->sign) {
      $image_sign = imagecreatefrompng($this->sign);
      list($rs_width, $rs_height) = getimagesize($this->sign);
      imagecopyresampled($image_out, $image_sign, 0, 0, 0, 0, $this->width_standart, $this->height_standart, $rs_width, $rs_height);//со сглаживанием
    }
    // Content type
    imagejpeg($image_out, $filename, 100);

    imageDestroy($image_out);
    imageDestroy($image_p);
    imageDestroy($image);
  }

  function getSizeImage($width, $height)
  {
    $width_offset  = 0;
    $height_offset = 0;

    if ($width >= $height) {
      $new_width  = $this->width_standart;
      $new_height = ($this->width_standart * $height) / $width;

      if ($new_height > $this->height_standart) {
        $new_width    = $new_height = $this->height_standart;
        $width_offset = ($this->width_standart - $new_width) / 2;
      }

      $height_offset = ($this->height_standart - $new_height) / 2;
    }

    if ($width < $height) {
      $new_height   = $this->height_standart;
      $new_width    = ($this->height_standart * $width) / $height;
      $width_offset = ($this->width_standart - $new_width) / 2;
    }

    return array($new_width, $new_height, $width_offset, $height_offset);
  }
}

?>