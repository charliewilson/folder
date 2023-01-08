<?php
namespace Folder\Controller;

use Folder\App;

use PDO;
use PDOException;
use DateTime;

use Intervention\Image\ImageManagerStatic as Image;


class PhotoController {
  
  private $app;
  
  function __construct(App $app) {
    $this->app = $app;
  }
  
  public function getAll($options = []): bool|array
  {
    
    $defaultOptions = [
      "includeDrafts" => false
    ];
    foreach($options as $option=>$value) {
      if (isset($defaultOptions[$option])) {
        $defaultOptions[$option] = $value;
      }
    }

    if ($defaultOptions["includeDrafts"]) {
      $statement = "SELECT * FROM `photos` ORDER BY `date_time` DESC";
    } else {
      $statement = "SELECT * FROM `photos` WHERE published = 1 ORDER BY `date_time` DESC";
    }
    $people = $this->app->db->prepare($statement);
    $people->execute();

    return $people->fetchAll(PDO::FETCH_CLASS,'\Folder\Model\Photo');
    
  }

  public function getPage($page = 1, $options = []): bool|array {

    $defaultOptions = [
      "includeDrafts" => false
    ];
    foreach($options as $option=>$value) {
      if (isset($defaultOptions[$option])) {
        $defaultOptions[$option] = $value;
      }
    }

    if ($defaultOptions["includeDrafts"] == true) {
      $statement = "SELECT * FROM `photos` ORDER BY `date_time` DESC LIMIT :offset, :limit";
    } else {
      $statement = "SELECT * FROM `photos` WHERE published = 1 ORDER BY `date_time` DESC LIMIT :offset, :limit";
    }

    $people = $this->app->db->prepare($statement);
    $people->execute([
      ":offset" => ($page - 1) * $this->app->appData->get()['postsPerPage'],
      ":limit" => $this->app->appData->get()['postsPerPage']
    ]);

    return $people->fetchAll(PDO::FETCH_CLASS,'\Folder\Model\Photo');

  }
  
  public function get($id, $options = []) {

    $defaultOptions = [
      "includeDrafts" => true
    ];
    foreach($options as $option=>$value) {
      if (isset($defaultOptions[$option])) {
        $defaultOptions[$option] = $value;
      }
    }

    if ($defaultOptions["includeDrafts"] == true) {
      $statement = "SELECT * FROM `photos` WHERE `id` = :id";
    } else {
      $statement = "SELECT * FROM `photos` WHERE `id` = :id AND `published` = 1";
    }

    $post = $this->app->db->prepare($statement);
    $post->execute([
      ":id" => filter_var($id, FILTER_SANITIZE_NUMBER_INT)
    ]);

    return $post->fetchObject('\Folder\Model\Photo');
  
  }

  public function getNeighbour($cursor_id, $direction = "newer", $options = []) {

    $defaultOptions = [
      "includeDrafts" => false
    ];
    foreach($options as $option=>$value) {
      if (isset($defaultOptions[$option])) {
        $defaultOptions[$option] = $value;
      }
    }

    $current = $this->get($cursor_id);

    if ($defaultOptions["includeDrafts"] == true) {
      if ($direction == "newer") {
        $statement = "SELECT * FROM `photos` WHERE `date_time` > :current ORDER BY `date_time` ASC LIMIT 1";
      } elseif ($direction == "older") {
        $statement = "SELECT * FROM `photos` WHERE `date_time` < :current ORDER BY `date_time` DESC LIMIT 1";
      }
    } else {
      if ($direction == "newer") {
        $statement = "SELECT * FROM `photos` WHERE `date_time` > :current AND `published` = 1 ORDER BY `date_time` ASC LIMIT 1";
      } elseif ($direction == "older") {
        $statement = "SELECT * FROM `photos` WHERE `date_time` < :current AND `published` = 1 ORDER BY `date_time` DESC LIMIT 1";
      }
    }

    $post = $this->app->db->prepare($statement);
    $post->execute([
      ":current" => $current->timestamp()['raw']
    ]);

    return $post->fetchObject('\Folder\Model\Photo');

  }

  public function getTag($tag, $field = "tags", $options = []) {

    //Strip all non-alphanumeric
    $cleanTag = preg_replace("/[^A-Za-z0-9 &]/", '', $tag);

    $defaultOptions = [
      "includeDrafts" => false
    ];
    foreach($options as $option=>$value) {
      if (isset($defaultOptions[$option])) {
        $defaultOptions[$option] = $value;
      }
    }

    // location field needs to be an exact match otherwise it's too inclusive
    // i.e. "Hackney" would match "Hackney" and "Hackney Wick"
    if ($field == "location") {
      $kw = "=";
    } else {
      $kw = "LIKE";
    }

    if ($defaultOptions["includeDrafts"] == true) {
      $statement = "SELECT * FROM `photos` WHERE `$field` $kw :search ORDER BY `date_time` DESC";
    } else {
      $statement = "SELECT * FROM `photos` WHERE `$field` $kw :search AND `published` = 1 ORDER BY `date_time` DESC";
    }

    $photos = $this->app->db->prepare($statement);

    // location field doesn't have surrounding commas
    if ($field == "location") {
      $photos->execute([
        ":search" => $cleanTag
      ]);
    } else {
      $photos->execute([
        ":search" => '%,' . $cleanTag . ',%'
      ]);
    }

    return $photos->fetchAll(PDO::FETCH_CLASS,'\Folder\Model\Photo');

  }

  public function getFieldPage($tag, $field = "tag", $page = 1, $options = []): bool|array {

    //Strip all non-alphanumeric
    $cleanTag = preg_replace("/[^A-Za-z0-9 &]/", '', $tag);

    $defaultOptions = [
      "includeDrafts" => false
    ];
    foreach($options as $option=>$value) {
      if (isset($defaultOptions[$option])) {
        $defaultOptions[$option] = $value;
      }
    }

    // location field needs to be an exact match otherwise it's too inclusive
    // i.e. "Hackney" would match "Hackney" and "Hackney Wick"
    if ($field == "location") {
      $kw = "=";
    } else {
      $kw = "LIKE";
    }

    if ($defaultOptions["includeDrafts"] == true) {
      $statement = "SELECT * FROM `photos` WHERE `$field` $kw :search ORDER BY `date_time` DESC LIMIT :offset, :limit";
    } else {
      $statement = "SELECT * FROM `photos` WHERE `$field` $kw :search AND published = 1 ORDER BY `date_time` DESC LIMIT :offset, :limit";
    }

    $photos = $this->app->db->prepare($statement);

    // location field doesn't have surrounding commas
    if ($field == "location") {
      $search = $cleanTag;
    } else {
      $search = '%,' . $cleanTag . ',%';
    }

    $photos->execute([
      ":search" => $search,
      ":offset" => ($page - 1) * $this->app->appData->get()['postsPerPage'],
      ":limit" => $this->app->appData->get()['postsPerPage']
    ]);

    return $photos->fetchAll(PDO::FETCH_CLASS,'\Folder\Model\Photo');

  }

  public function getTaglist($field = "tags", $options = []) {

    $defaultOptions = [
      "includeDrafts" => false,
      "returnJSON" => false
    ];
    foreach($options as $option=>$value) {
      if (isset($defaultOptions[$option])) {
        $defaultOptions[$option] = $value;
      }
    }

    if ($defaultOptions["includeDrafts"] == true) {
      $statement = "SELECT `$field` FROM `photos` ORDER BY `date_time` DESC";
    } else {
      $statement = "SELECT `$field` FROM `photos` WHERE published = 1 ORDER BY `date_time` DESC";
    }
    $tags = $this->app->db->prepare($statement);
    $tags->execute();

    $raw = $tags->fetchAll(PDO::FETCH_ASSOC);

    //die(print_r($raw));

    $return = [];

    foreach ($raw as $tag) {
      if ($tag[$field]) {
        foreach (array_filter(array_map('trim', explode(',', $tag[$field]))) as $individualTag) {
          $return[] = $individualTag;
        }
      }
    }

    $final = array_count_values($return);
    array_multisort(
      array_values($final), SORT_DESC,
      array_keys($final), SORT_ASC,
      $final);

    if ($defaultOptions['returnJSON']) {
      //JSON formatting

      $json = [];
      foreach($final as $k=>$v) {
        $push['value'] = $k;
        $push['count'] = $v;
        $json[] = $push;
      }

      return json_encode($json, JSON_PRETTY_PRINT);

    } else {
      return $final;
    }

  }
  
  public function create($fields) {

    $timestamp = strip_tags($fields['timestamp']);
    $published = (array_key_exists("published", $fields)) ? 1 : 0;
    $title = strip_tags($fields['title']);
    $description = strip_tags($fields['description']);
    $tags = ','.implode(',', array_filter(array_map('trim', explode(',', strip_tags($fields['tags']))))).',';
    $location = ($fields['location'] != "") ? $fields['location'] : null;

    if ($fields['people'] != "") {
      $people = ','.implode(',', array_filter(array_map('trim', explode(',', strip_tags($fields['people']))))).',';
    } else {
      $people = null;
    }

    $filename = (new DateTime)->getTimestamp();

    if (isset($_FILES['image']['tmp_name'])) {

      //pull the exif before re-saving as JPG
      $exif = exif_read_data($_FILES['image']['tmp_name'], null, true);

      //Create image from temp
      $image = Image::make($_FILES['image']['tmp_name'])->orientate();

      //Save 'original' at 100 quality
      $image->save('media/originals/' . $filename . '.jpg', 100);

      //Save 800px 'display' version
      $image->resize(800, 800, function ($constraint) {
        $constraint->aspectRatio();
        $constraint->upsize();
      })->save('media/thumbs/' . $filename . '.jpg', 80);;

      try {
        $photo = $this->app->db->prepare("
        INSERT INTO `photos`
        (
         `id`,
         `title`,
         `description`,
         `tags`,
         `date_time`,
         `path`,
         `exif`,
         `published`,
         `people`,         
         `location`
        )
        VALUES
        (
        :id,
        :title,
        :description,
        :tags,
        :date_time,
        :path,
        :exif,
        :published,
        :people,
        :location 
        )
        ");
        $photo->execute([
          ":id" => NULL,
          ":title" => $title,
          ":description" => ($description != "") ? $description : NULL,
          ":tags" => ($tags != "") ? $tags : NULL,
          ":people" => ($people != "") ? $people : NULL,
          ":location" => ($location != "") ? $location : NULL,
          ":date_time" => $timestamp,
          ":path" => $filename,
          ":exif" => serialize($exif),
          ":published" => $published,
        ]);

        return true;
      } catch (PDOException $e) {
        return $e->getMessage();
      }

    } else {
      header("Location: /folder/newphoto?nophoto");
    }
  }
  
  public function update($id_raw, $fields) {

    $id = filter_var($id_raw, FILTER_SANITIZE_NUMBER_INT);
    $timestamp = strip_tags($fields['timestamp']);
    $title = strip_tags($fields['title']);
    $description = strip_tags($fields['description']);
    $tags = ','.implode(',', array_filter(array_map('trim', explode(',', strip_tags($fields['tags']))))).',';
    $location = ($fields['location'] != "") ? $fields['location'] : null;
    $published = (array_key_exists("published", $fields)) ? 1 : 0;

    if ($fields['people'] != "") {
      $people = ','.implode(',', array_filter(array_map('trim', explode(',', strip_tags($fields['people']))))).',';
    } else {
      $people = null;
    }
    
    try {
      $photo = $this->app->db->prepare("
    UPDATE `photos` SET
    `title` = :title,
    `description` = :description,
    `tags` = :tags,
    `date_time` = :timestamp,
    `published` = :published,
    `people` = :people,
    `location` = :location
    WHERE `id` = :id
    ");
      $photo->execute([
        ":id" => $id,
        ":title" => $title,
        ":description" => $description,
        ":tags" => $tags,
        ":people" => $people,
        ":location" => $location,
        ":timestamp" => $timestamp,
        ":published" => $published
      ]);
      
      return true;
    } catch (PDOException $e) {
      return $e->getMessage();
    }
  }
  
  public function delete($id) {
    try {

      $photo = $this->get($id);

      if (
        file_exists($_SERVER['DOCUMENT_ROOT'].$photo->image()['original']) &&
        file_exists($_SERVER['DOCUMENT_ROOT'].$photo->image()['thumb'])
      ) {
        unlink($_SERVER['DOCUMENT_ROOT'].$photo->image()['original']);
        unlink($_SERVER['DOCUMENT_ROOT'].$photo->image()['thumb']);
      }

      $people = $this->app->db->prepare("DELETE FROM `photos` WHERE `id` = :id");
      $people->execute([
        ":id" => filter_var($id, FILTER_SANITIZE_NUMBER_INT)
      ]);
      return true;
    } catch (PDOException $e) {
      return $e->getMessage();
    }
  }
}