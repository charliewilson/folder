<?php
namespace Folder\Model;

use JetBrains\PhpStorm\ArrayShape;
use DateTime;
use Parsedown;

class Photo {
  
  private int $id;
  private string $title;
  private string|null $description;
  private string|null $tags;
  private string $date_time;
  private string $path;
  private string|null $exif;
  private int $published;
  private string|null $people;
  private string|null $location;
  
  public function id(): int {
    return $this->id;
  }

  public function title(): string {
    return $this->title;
  }

  public function description(): array {
    if ($this->description) {
      return [
        "markdown" => $this->description,
        "html" => (new Parsedown())->text($this->description),
        "html_encoded" => htmlspecialchars((new Parsedown())->text($this->description)),
        "html_inline" => (new Parsedown())->line($this->description),
      ];
    } else {
      return [
        "markdown" => "",
        "html" => "",
        "html_encoded" => "",
        "html_inline" => ""
      ];
    }
  }

  public function tags(): array|bool {
    //explode and trim whitespace
    if ($this->tags != "") {
      return array_values(array_filter(array_map('trim', explode(',', $this->tags))));
    } else {
      return false;
    }
  }

  public function people(): array|bool {
    //explode and trim whitespace
    if ($this->people != "") {
      return array_values(array_filter(array_map('trim', explode(',', $this->people))));
    } else {
      return false;
    }
  }

  public function location(): string|null {
    return $this->location;
  }

  public function timestamp(): array {
    $ts = new DateTime($this->date_time);

    return [
      "raw" => $this->date_time,
      "short" => $ts->format("j/n/y"),
      "time" => $ts->format("g:ia"),
    ];
  }

  public function path(): string {
    return "/media/".$this->path.".jpg";
  }

  public function exif(): array{
    return unserialize($this->exif);
  }
  public function dimensions(): array {
    $exifWidth = $this->exif()['COMPUTED']['Width'];
    $exifHeight = $this->exif()['COMPUTED']['Height'];
    $rotation = (isset($this->exif()['IFD0']['Orientation'])) ? $this->exif()['IFD0']['Orientation'] : 1;

    // see: https://jdhao.github.io/2019/07/31/image_rotation_exif_info/
    if ($rotation > 4 ) {
      return [
        "width" => $exifHeight,
        "height" => $exifWidth
      ];
    } else {
      return [
        "width" => $exifWidth,
        "height" => $exifHeight
      ];
    }
  }
  
  public function published(): bool {
    return $this->published == 1;
  }

  #[ArrayShape(["thumb" => "string", "original" => "string"])]
  public function image(): array {
    return [
      "thumb" => "/media/thumbs/".$this->path.".jpg",
      "original" => "/media/originals/".$this->path.".jpg"
    ];
  }
  
}
