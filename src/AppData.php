<?php

namespace Folder;

class AppData
{

  public $appName, $version, $postsPerPage;

  function __construct()
  {
    $this->appName = 'folder';
    $this->version = '1.3';
    //instagram does 24 per load...
    $this->postsPerPage = 24;
  }

  public function get()
  {
    return [
      "name" => $this->appName,
      "version" => $this->version,
      "year" => date("Y"),
      "postsPerPage" => $this->postsPerPage
    ];
  }

}